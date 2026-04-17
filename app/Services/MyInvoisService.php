<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MyinvoisSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MyInvoisService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $accessToken = null;
    
    public function __construct()
    {
        $this->baseUrl = config('myinvois.base_url');
        $this->apiKey = config('myinvois.api_key');
        $this->clientId = config('myinvois.client_id');
        $this->clientSecret = config('myinvois.client_secret');
    }
    
    /**
     * Submit invoice to MyInvois
     */
    public function submitInvoice(Invoice $invoice, string $digitalSignature): MyinvoisSubmission
    {
        // Prepare payload
        $payload = $this->prepareInvoicePayload($invoice);
        $payload['digitalSignature'] = $digitalSignature;
        
        // Create submission record
        $submission = MyinvoisSubmission::create([
            'invoice_id' => $invoice->id,
            'submission_reference' => $this->generateSubmissionReference(),
            'submission_type' => 'single',
            'request_payload' => $payload,
            'status' => 'pending',
        ]);
        
        try {
            // Get access token
            $this->authenticate();
            
            // Submit to MyInvois API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->baseUrl . '/api/v1/invoice/submit', $payload);
            
            // Log API call
            $this->logApiCall($submission, 'POST', '/api/v1/invoice/submit', $response);
            
            // Handle response
            if ($response->successful()) {
                $responseData = $response->json();
                
                $submission->update([
                    'status' => 'accepted',
                    'response_payload' => $responseData,
                    'myinvois_uid' => $responseData['uid'] ?? null,
                    'qr_code_url' => $responseData['qrCodeUrl'] ?? null,
                    'submitted_at' => now(),
                    'response_received_at' => now(),
                ]);
                
                // Update invoice
                $invoice->update([
                    'status' => 'approved',
                    'myinvois_uid' => $responseData['uid'] ?? null,
                    'qr_code_data' => $responseData['qrCode'] ?? null,
                    'irbm_unique_identifier' => $responseData['uid'] ?? null,
                    'validation_date_time' => now(),
                    'submitted_at' => now(),
                ]);
                
            } else {
                $errorData = $response->json();
                
                $submission->update([
                    'status' => 'rejected',
                    'response_payload' => $errorData,
                    'rejection_reason' => $errorData['message'] ?? 'Submission rejected by MyInvois',
                    'submitted_at' => now(),
                    'response_received_at' => now(),
                ]);
                
                // Update invoice
                $invoice->update([
                    'status' => 'rejected',
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('MyInvois submission failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            
            $submission->update([
                'status' => 'rejected',
                'rejection_reason' => $e->getMessage(),
                'response_received_at' => now(),
            ]);
            
            $invoice->update([
                'status' => 'rejected',
            ]);
        }
        
        return $submission->fresh();
    }
    
    /**
     * Cancel validated invoice
     */
    public function cancelInvoice(Invoice $invoice): bool
    {
        try {
            $this->authenticate();
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/api/v1/invoice/cancel', [
                'uid' => $invoice->myinvois_uid,
                'reason' => 'User requested cancellation',
            ]);
            
            if ($response->successful()) {
                $invoice->update(['status' => 'cancelled']);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('MyInvois cancellation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Validate invoice format (dry run)
     */
    public function validateInvoiceFormat(Invoice $invoice): array
    {
        try {
            $this->authenticate();
            
            $payload = $this->prepareInvoicePayload($invoice);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/api/v1/invoice/validate', $payload);
            
            if ($response->successful()) {
                return [
                    'valid' => true,
                    'message' => 'Invoice format is valid',
                ];
            } else {
                $errors = $response->json()['errors'] ?? [];
                return [
                    'valid' => false,
                    'errors' => $errors,
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Prepare invoice payload for MyInvois API in strict UBL format
     */
    protected function prepareInvoicePayload(Invoice $invoice): array
    {
        $utcDateTime = clone $invoice->invoice_date_time;
        $utcDateTime->setTimezone('UTC');

        // Supplier Identifications structure
        $supplierIdentifications = [
            [ 'ID' => [['_' => $invoice->supplier_tin, 'schemeID' => 'TIN']] ],
            [ 'ID' => [['_' => $invoice->supplier_registration_number, 'schemeID' => 'BRN']] ],
        ];
        if (!empty($invoice->supplier_sst_registration_number)) {
            $supplierIdentifications[] = [ 'ID' => [['_' => $invoice->supplier_sst_registration_number, 'schemeID' => 'SST']] ];
        } else {
            $supplierIdentifications[] = [ 'ID' => [['_' => 'NA', 'schemeID' => 'SST']] ];
        }
        if (!empty($invoice->supplier_tourism_tax_number)) {
            $supplierIdentifications[] = [ 'ID' => [['_' => $invoice->supplier_tourism_tax_number, 'schemeID' => 'TTX']] ];
        } else {
            $supplierIdentifications[] = [ 'ID' => [['_' => 'NA', 'schemeID' => 'TTX']] ];
        }

        // Buyer Identifications structure
        $buyerIdentifications = [
            [ 'ID' => [['_' => $invoice->buyer_tin, 'schemeID' => 'TIN']] ],
            [ 'ID' => [['_' => $invoice->buyer_registration_number ?: 'NA', 'schemeID' => 'BRN']] ],
        ];
        if (!empty($invoice->buyer_sst_registration_number)) {
            $buyerIdentifications[] = [ 'ID' => [['_' => $invoice->buyer_sst_registration_number, 'schemeID' => 'SST']] ];
        } else {
            $buyerIdentifications[] = [ 'ID' => [['_' => 'NA', 'schemeID' => 'SST']] ];
        }
        $buyerIdentifications[] = [ 'ID' => [['_' => 'NA', 'schemeID' => 'TTX']] ];

        // Line Items UBL Mapping
        $invoiceLines = [];
        foreach ($invoice->lineItems as $item) {
            $invoiceLines[] = [
                'ID' => [['_' => (string) $item->line_number]],
                'InvoicedQuantity' => [['_' => (float) $item->quantity, 'unitCode' => $item->unit_of_measure]],
                'LineExtensionAmount' => [['_' => (float) $item->total_excluding_tax_per_line, 'currencyID' => $invoice->currency_code]],
                'AllowanceCharge' => [
                    [
                        'ChargeIndicator' => [['_' => false]],
                        'AllowanceChargeReason' => [['_' => 'Discount']],
                        'Amount' => [['_' => (float) ($item->discount_amount ?? 0), 'currencyID' => $invoice->currency_code]]
                    ],
                    [
                        'ChargeIndicator' => [['_' => true]],
                        'AllowanceChargeReason' => [['_' => 'Charge']],
                        'Amount' => [['_' => (float) ($item->charge_fee_amount ?? 0), 'currencyID' => $invoice->currency_code]]
                    ]
                ],
                'TaxTotal' => [
                    [
                        'TaxAmount' => [['_' => (float) $item->tax_amount, 'currencyID' => $invoice->currency_code]],
                        'TaxSubtotal' => [
                            [
                                'TaxableAmount' => [['_' => (float) $item->total_excluding_tax_per_line, 'currencyID' => $invoice->currency_code]],
                                'TaxAmount' => [['_' => (float) $item->tax_amount, 'currencyID' => $invoice->currency_code]],
                                'Percent' => [['_' => (float) $item->tax_rate]],
                                'TaxCategory' => [
                                    [
                                        'ID' => [['_' => $item->tax_type]],
                                        'TaxExemptionReason' => $item->tax_exemption_reason ? [['_' => $item->tax_exemption_reason]] : [],
                                        'TaxScheme' => [
                                            [
                                                'ID' => [['_' => 'OTH', 'schemeID' => 'UN/ECE 5153', 'schemeAgencyID' => '6']]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'Item' => [
                    [
                        'CommodityClassification' => [
                            [
                                'ItemClassificationCode' => [['_' => $item->product_tariff_code ?: 'NA', 'listID' => 'PTC']]
                            ],
                            [
                                'ItemClassificationCode' => [['_' => $item->classification_code, 'listID' => 'CLASS']]
                            ]
                        ],
                        'Description' => [['_' => $item->product_service_description]],
                        'OriginCountry' => [
                            [
                                'IdentificationCode' => [['_' => $item->country_of_origin ?: 'MYS']]
                            ]
                        ]
                    ]
                ],
                'Price' => [
                    [
                        'PriceAmount' => [['_' => (float) $item->unit_price, 'currencyID' => $invoice->currency_code]]
                    ]
                ],
                'ItemPriceExtension' => [
                    [
                        'Amount' => [['_' => (float) $item->subtotal, 'currencyID' => $invoice->currency_code]]
                    ]
                ]
            ];
        }

        // Tax totals mapping
        $taxTotals = [
            [
                'TaxAmount' => [['_' => (float) $invoice->total_tax_amount, 'currencyID' => $invoice->currency_code]],
                'TaxSubtotal' => [
                    [
                        'TaxableAmount' => [['_' => (float) $invoice->total_excluding_tax, 'currencyID' => $invoice->currency_code]],
                        'TaxAmount' => [['_' => (float) $invoice->total_tax_amount, 'currencyID' => $invoice->currency_code]],
                        'TaxCategory' => [
                            [
                                'ID' => [['_' => '01']], // Typical global indicator
                                'TaxScheme' => [
                                    [
                                        'ID' => [['_' => 'OTH', 'schemeID' => 'UN/ECE 5153', 'schemeAgencyID' => '6']]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $ublPayload = [
            '_D' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            '_A' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            '_B' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'Invoice' => [
                [
                    'ID' => [['_' => $invoice->invoice_number]],
                    'IssueDate' => [['_' => $utcDateTime->format('Y-m-d')]],
                    'IssueTime' => [['_' => $utcDateTime->format('H:i:s\Z')]],
                    'InvoiceTypeCode' => [['_' => $invoice->invoice_type, 'listVersionID' => '1.1']],
                    'DocumentCurrencyCode' => [['_' => $invoice->currency_code]],
                    'TaxCurrencyCode' => [['_' => $invoice->currency_code]],
                    'AdditionalDocumentReference' => [
                        [
                            'ID' => [['_' => $invoice->customs_form_reference ?: 'NA']],
                            'DocumentType' => [['_' => 'CustomsImportForm']]
                        ]
                    ],
                    'AccountingSupplierParty' => [
                        [
                            'Party' => [
                                [
                                    'IndustryClassificationCode' => [['_' => $invoice->supplier_msic_code, 'name' => $invoice->supplier_business_activity_description]],
                                    'PartyIdentification' => $supplierIdentifications,
                                    'PostalAddress' => [
                                        [
                                            'CityName' => [['_' => $invoice->supplier_city ?: 'NA']],
                                            'PostalZone' => [['_' => $invoice->supplier_postal_code ?: 'NA']],
                                            'CountrySubentityCode' => [['_' => $invoice->supplier_state]],
                                            'AddressLine' => [
                                                ['Line' => [['_' => $invoice->supplier_address_line1]]],
                                                ['Line' => [['_' => $invoice->supplier_address_line2 ?: 'NA']]],
                                                ['Line' => [['_' => $invoice->supplier_address_line3 ?: 'NA']]]
                                            ],
                                            'Country' => [
                                                [
                                                    'IdentificationCode' => [['_' => $invoice->supplier_country, 'listID' => 'ISO3166-1', 'listAgencyID' => '6']]
                                                ]
                                            ]
                                        ]
                                    ],
                                    'PartyLegalEntity' => [
                                        [
                                            'RegistrationName' => [['_' => $invoice->supplier_name]]
                                        ]
                                    ],
                                    'Contact' => [
                                        [
                                            'Telephone' => [['_' => $invoice->supplier_contact_number]],
                                            'ElectronicMail' => [['_' => $invoice->supplier_email]]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'AccountingCustomerParty' => [
                        [
                            'Party' => [
                                [
                                    'PostalAddress' => [
                                        [
                                            'CityName' => [['_' => $invoice->buyer_city ?: 'NA']],
                                            'PostalZone' => [['_' => $invoice->buyer_postal_code ?: 'NA']],
                                            'CountrySubentityCode' => [['_' => $invoice->buyer_state]],
                                            'AddressLine' => [
                                                ['Line' => [['_' => $invoice->buyer_address_line1]]],
                                                ['Line' => [['_' => $invoice->buyer_address_line2 ?: 'NA']]],
                                                ['Line' => [['_' => $invoice->buyer_address_line3 ?: 'NA']]]
                                            ],
                                            'Country' => [
                                                [
                                                    'IdentificationCode' => [['_' => $invoice->buyer_country, 'listID' => 'ISO3166-1', 'listAgencyID' => '6']]
                                                ]
                                            ]
                                        ]
                                    ],
                                    'PartyLegalEntity' => [
                                        [
                                            'RegistrationName' => [['_' => $invoice->buyer_name]]
                                        ]
                                    ],
                                    'PartyIdentification' => $buyerIdentifications,
                                    'Contact' => [
                                        [
                                            'Telephone' => [['_' => $invoice->buyer_contact_number ?: 'NA']],
                                            'ElectronicMail' => [['_' => $invoice->buyer_email ?: 'NA']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'PaymentMeans' => [
                        [
                            'PaymentMeansCode' => [['_' => $invoice->payment_mode ?: '01']],
                            'PayeeFinancialAccount' => [
                                [
                                    'ID' => [['_' => $invoice->bank_account_number ?: 'NA']]
                                ]
                            ]
                        ]
                    ],
                    'PaymentTerms' => [
                        [
                            'Note' => [['_' => $invoice->payment_terms ?: 'NA']]
                        ]
                    ],
                    'AllowanceCharge' => [
                        [
                            'ChargeIndicator' => [['_' => false]],
                            'AllowanceChargeReason' => [['_' => 'Overall Discount']],
                            'Amount' => [['_' => (float) $invoice->total_discount_value, 'currencyID' => $invoice->currency_code]]
                        ],
                        [
                            'ChargeIndicator' => [['_' => true]],
                            'AllowanceChargeReason' => [['_' => 'Overall Charge']],
                            'Amount' => [['_' => (float) $invoice->total_fee_charge_amount, 'currencyID' => $invoice->currency_code]]
                        ]
                    ],
                    'TaxTotal' => $taxTotals,
                    'LegalMonetaryTotal' => [
                        [
                            'LineExtensionAmount' => [['_' => (float) $invoice->total_excluding_tax, 'currencyID' => $invoice->currency_code]],
                            'TaxExclusiveAmount' => [['_' => (float) $invoice->total_excluding_tax, 'currencyID' => $invoice->currency_code]],
                            'TaxInclusiveAmount' => [['_' => (float) $invoice->total_including_tax, 'currencyID' => $invoice->currency_code]],
                            'AllowanceTotalAmount' => [['_' => (float) $invoice->total_discount_value, 'currencyID' => $invoice->currency_code]],
                            'ChargeTotalAmount' => [['_' => (float) $invoice->total_fee_charge_amount, 'currencyID' => $invoice->currency_code]],
                            'PayableAmount' => [['_' => (float) $invoice->total_payable_amount, 'currencyID' => $invoice->currency_code]]
                        ]
                    ],
                    'InvoiceLine' => $invoiceLines
                ]
            ]
        ];

        // Add Billing Reference for Note Types
        if (in_array($invoice->invoice_type, ['02', '03', '04']) && $invoice->original_einvoice_reference) {
            $ublPayload['Invoice'][0]['BillingReference'] = [
                [
                    'AdditionalDocumentReference' => [
                        [
                            'ID' => [['_' => $invoice->original_einvoice_reference]]
                        ]
                    ]
                ]
            ];
        }

        // Add Delivery/Shipping info
        if ($invoice->has_shipping_info) {
            $ublPayload['Invoice'][0]['Delivery'] = [
                [
                    'DeliveryParty' => [
                        [
                            'PartyLegalEntity' => [
                                [
                                    'RegistrationName' => [['_' => $invoice->shipping_recipient_name]]
                                ]
                            ],
                            'PostalAddress' => [
                                [
                                    'CityName' => [['_' => $invoice->shipping_city ?: 'NA']],
                                    'PostalZone' => [['_' => $invoice->shipping_postal_code ?: 'NA']],
                                    'CountrySubentityCode' => [['_' => $invoice->shipping_state ?: 'NA']],
                                    'AddressLine' => [
                                        ['Line' => [['_' => $invoice->shipping_address_line1]]],
                                        ['Line' => [['_' => $invoice->shipping_address_line2 ?: 'NA']]],
                                        ['Line' => [['_' => $invoice->shipping_address_line3 ?: 'NA']]]
                                    ],
                                    'Country' => [
                                        [
                                            'IdentificationCode' => [['_' => $invoice->shipping_country ?: 'MYS', 'listID' => 'ISO3166-1', 'listAgencyID' => '6']]
                                        ]
                                    ]
                                ]
                            ],
                            'PartyIdentification' => [
                                [
                                    'ID' => [['_' => $invoice->shipping_recipient_tin ?: 'EI00000000010', 'schemeID' => 'TIN']]
                                ],
                                [
                                    'ID' => [['_' => $invoice->shipping_recipient_registration ?: 'NA', 'schemeID' => 'BRN']]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $ublPayload;
    }
    
    /**
     * Authenticate with MyInvois
     */
    protected function authenticate(): void
    {
        if ($this->accessToken) {
            return; // Already authenticated
        }
        
        $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
        
        if ($response->successful()) {
            $this->accessToken = $response->json()['access_token'];
        } else {
            throw new \Exception('Failed to authenticate with MyInvois');
        }
    }
    
    /**
     * Log API call
     */
    protected function logApiCall(MyinvoisSubmission $submission, string $method, string $endpoint, $response): void
    {
        $submission->apiLogs()->create([
            'endpoint' => $endpoint,
            'http_method' => $method,
            'http_status_code' => $response->status(),
            'request_headers' => $response->transferStats?->getRequest()->getHeaders() ?? [],
            'response_headers' => $response->headers(),
            'response_body' => $response->json(),
            'response_time_ms' => $response->transferStats?->getTransferTime() * 1000 ?? 0,
            'error_message' => $response->failed() ? $response->body() : null,
        ]);
    }
    
    /**
     * Generate submission reference
     */
    protected function generateSubmissionReference(): string
    {
        return 'SUB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }
}