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
     * Prepare invoice payload for MyInvois API
     */
    protected function prepareInvoicePayload(Invoice $invoice): array
    {
        $lineItems = [];
        foreach ($invoice->lineItems as $item) {
            $lineItems[] = [
                'lineNumber' => $item->line_number,
                'classification' => [
                    'code' => $item->classification_code,
                    'description' => $item->product_service_description,
                ],
                'quantity' => (float) $item->quantity,
                'unitOfMeasure' => $item->unit_of_measure,
                'unitPrice' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'discount' => [
                    'rate' => (float) ($item->discount_rate ?? 0),
                    'amount' => (float) ($item->discount_amount ?? 0),
                ],
                'tax' => [
                    'type' => $item->tax_type,
                    'rate' => (float) $item->tax_rate,
                    'amount' => (float) $item->tax_amount,
                    'exemptionReason' => $item->tax_exemption_reason,
                ],
                'totals' => [
                    'excludingTax' => (float) $item->total_excluding_tax_per_line,
                    'includingTax' => (float) $item->total_including_tax_per_line,
                ],
            ];
        }
        
        $payload = [
            'version' => '1.0',
            'invoiceType' => $invoice->invoice_type,
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDateTime' => $invoice->invoice_date_time->toIso8601String(),
            'supplier' => [
                'name' => $invoice->supplier_name,
                'tin' => $invoice->supplier_tin,
                'registrationNumber' => $invoice->supplier_registration_number,
                'sstRegistrationNumber' => $invoice->supplier_sst_registration_number,
                'tourismTaxNumber' => $invoice->supplier_tourism_tax_number,
                'email' => $invoice->supplier_email,
                'msicCode' => $invoice->supplier_msic_code,
                'businessActivity' => $invoice->supplier_business_activity_description,
                'address' => [
                    'line1' => $invoice->supplier_address_line1,
                    'line2' => $invoice->supplier_address_line2,
                    'line3' => $invoice->supplier_address_line3,
                    'postalCode' => $invoice->supplier_postal_code,
                    'city' => $invoice->supplier_city,
                    'state' => $invoice->supplier_state,
                    'country' => $invoice->supplier_country,
                ],
                'contactNumber' => $invoice->supplier_contact_number,
            ],
            'buyer' => [
                'name' => $invoice->buyer_name,
                'tin' => $invoice->buyer_tin,
                'registrationNumber' => $invoice->buyer_registration_number,
                'sstRegistrationNumber' => $invoice->buyer_sst_registration_number,
                'email' => $invoice->buyer_email,
                'address' => [
                    'line1' => $invoice->buyer_address_line1,
                    'line2' => $invoice->buyer_address_line2,
                    'line3' => $invoice->buyer_address_line3,
                    'postalCode' => $invoice->buyer_postal_code,
                    'city' => $invoice->buyer_city,
                    'state' => $invoice->buyer_state,
                    'country' => $invoice->buyer_country,
                ],
                'contactNumber' => $invoice->buyer_contact_number,
            ],
            'lineItems' => $lineItems,
            'totals' => [
                'excludingTax' => (float) $invoice->total_excluding_tax,
                'taxAmount' => (float) $invoice->total_tax_amount,
                'includingTax' => (float) $invoice->total_including_tax,
                'discountValue' => (float) $invoice->total_discount_value,
                'feeChargeAmount' => (float) $invoice->total_fee_charge_amount,
                'payableAmount' => (float) $invoice->total_payable_amount,
            ],
            'currency' => [
                'code' => $invoice->currency_code,
                'exchangeRate' => $invoice->currency_exchange_rate ? (float) $invoice->currency_exchange_rate : null,
            ],
        ];
        
        // Add optional fields if present
        if ($invoice->has_shipping_info) {
            $payload['shipping'] = [
                'recipientName' => $invoice->shipping_recipient_name,
                'recipientTin' => $invoice->shipping_recipient_tin,
                'address' => [
                    'line1' => $invoice->shipping_address_line1,
                    'line2' => $invoice->shipping_address_line2,
                    'line3' => $invoice->shipping_address_line3,
                    'postalCode' => $invoice->shipping_postal_code,
                    'city' => $invoice->shipping_city,
                    'state' => $invoice->shipping_state,
                    'country' => $invoice->shipping_country,
                ],
            ];
        }
        
        if ($invoice->has_customs_info) {
            $payload['customs'] = [
                'formReference' => $invoice->customs_form_reference,
                'incoterms' => $invoice->incoterms,
                'ftaInfo' => $invoice->free_trade_agreement_info,
            ];
        }
        
        return $payload;
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