<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MyinvoisSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Laraditz\MyInvois\Data\Invoice as SdkInvoice;
use Laraditz\MyInvois\Data\InvoiceTypeCode;
use Laraditz\MyInvois\Data\Data;
use Laraditz\MyInvois\Data\Party;
use Laraditz\MyInvois\Data\AccountingSupplierParty;
use Laraditz\MyInvois\Data\AccountingCustomerParty;
use Laraditz\MyInvois\Data\PostalAddress;
use Laraditz\MyInvois\Data\PartyIdentification;
use Laraditz\MyInvois\Data\PartyLegalEntity;
use Laraditz\MyInvois\Data\Contact;
use Laraditz\MyInvois\Data\TaxTotal;
use Laraditz\MyInvois\Data\TaxSubtotal;
use Laraditz\MyInvois\Data\TaxCategory;
use Laraditz\MyInvois\Data\TaxScheme;
use Laraditz\MyInvois\Data\LegalMonetaryTotal;
use Laraditz\MyInvois\Data\InvoiceLine;
use Laraditz\MyInvois\Data\Item;
use Laraditz\MyInvois\Data\Price;
use Laraditz\MyInvois\Data\Money;
use Laraditz\MyInvois\Data\AddressLine;
use Laraditz\MyInvois\Data\Country;
use Laraditz\MyInvois\Data\IdentificationCode;
use Laraditz\MyInvois\Data\CommodityClassification;
use Laraditz\MyInvois\Data\ItemPriceExtension;
use Laraditz\MyInvois\Enums\Format;
use Laraditz\MyInvois\Exceptions\MyInvoisApiError;

class MyInvoisService
{
    protected string $clientId;
    protected string $clientSecret;
    protected \Laraditz\MyInvois\MyInvois $myInvois;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        
        $this->myInvois = new \Laraditz\MyInvois\MyInvois(
            is_sandbox: config('myinvois.sandbox_mode', false),
            client_id: $this->clientId,
            client_secret: $this->clientSecret
        );
        
        // Fix: Override the global instance so Laraditz sub-services use our custom credentials
        app()->instance('myinvois', $this->myInvois);
    }
    
    protected function myr(float $v): Money 
    { 
        return new Money(value: number_format($v, 2, '.', ''), currencyID: 'MYR'); 
    }
    
    protected function sanitizePhone(?string $phone): string 
    {
        if (!$phone) return 'NA';
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Submit invoice to MyInvois
     */
    public function submitInvoice(Invoice $invoice): MyinvoisSubmission
    {
        // 1. Authenticate with LHDN
        $this->authenticate();
        
        // 2. Prepare SDK DTO payload
        $invoiceData = $this->prepareInvoicePayload($invoice);
        
        // 3. Create submission record
        $submission = MyinvoisSubmission::create([
            'invoice_id' => $invoice->id,
            'submission_reference' => $this->generateSubmissionReference(),
            'submission_type' => 'single',
            'status' => 'pending',
            'request_payload' => ['notice' => 'Payload managed by laraditz/my-invois SDK'],
        ]);
        
        try {
            // 4. Submit to MyInvois API
            // Clear any previous failed submission from the SDK's tracking table
            // so it doesn't get skipped by the SDK's internal duplication check
            \Illuminate\Support\Facades\DB::table('myinvois_documents')
                ->where('code_number', $invoice->invoice_number)
                ->delete();

            $result = $this->myInvois->document()->submit(
                documents: [$invoiceData], 
                format: \Laraditz\MyInvois\Enums\Format::XML
            );
            
            // Note: The package automatically creates ApiLogs in myinvois_requests table.
            
            // 5. Handle response
            if ($result['success'] ?? false) {
                $submissionUid = $result['data']['submissionUid'] ?? null;
                $acceptedDoc = $result['data']['acceptedDocuments'][0] ?? null;
                $rejectedDoc = $result['data']['rejectedDocuments'][0] ?? null;
                
                if ($acceptedDoc) {
                    $submission->update([
                        'status' => 'accepted',
                        'response_payload' => $result['data'],
                        'myinvois_uid' => $acceptedDoc['uuid'] ?? null,
                        'submitted_at' => now(),
                        'response_received_at' => now(),
                    ]);
                    
                    $invoice->update([
                        'status' => 'submitted',
                        'myinvois_uid' => $acceptedDoc['uuid'] ?? null,
                        'irbm_unique_identifier' => $submissionUid,
                        'validation_date_time' => now(),
                        'submitted_at' => now(),
                    ]);
                } else if ($rejectedDoc) {
                    $errorDetails = $rejectedDoc['error']['details'] ?? [];
                    $rejectionReason = collect($errorDetails)->pluck('message')->filter()->implode(' | ') ?: 'Rejected by LHDN';
                    
                    $this->markAsRejected($submission, $invoice, $result, $rejectionReason);
                }
            } else {
                $this->markAsRejected($submission, $invoice, $result, 'Unknown API error');
            }
            
        } catch (MyInvoisApiError $e) {
            $errorData = $e->getErrors();
            $rejectionReason = 'Submission rejected by MyInvois';
            if (isset($errorData['details']) && is_array($errorData['details'])) {
                $rejectionReason = collect($errorData['details'])->pluck('message')->filter()->implode(' | ');
            } elseif (isset($errorData['message'])) {
                $rejectionReason = $errorData['message'];
            }
            
            Log::error('MyInvois API error', ['invoice_id' => $invoice->id, 'error' => $e->getMessage(), 'details' => $errorData]);
            $this->markAsRejected($submission, $invoice, $errorData, $rejectionReason);
            
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            preg_match('/\{.*\}/s', $msg, $matches);
            if ($matches) {
                $errorData = json_decode($matches[0], true);
                $details = $errorData['error']['details'] ?? [];
                $rejectionReason = collect($details)->pluck('message')->filter()->implode(' | ') ?: 'Validation Error';
            } else {
                $errorData = ['error' => $msg];
                $rejectionReason = $msg;
            }
            
            Log::error('MyInvois submission failed', ['invoice_id' => $invoice->id, 'error' => $msg]);
            $this->markAsRejected($submission, $invoice, $errorData, $rejectionReason);
        }
        
        return $submission->fresh();
    }
    
    protected function markAsRejected($submission, $invoice, $errorData, $rejectionReason)
    {
        $submission->update([
            'status' => 'rejected',
            'response_payload' => $errorData,
            'rejection_reason' => $rejectionReason,
            'submitted_at' => now(),
            'response_received_at' => now(),
        ]);
        
        $invoice->update([
            'status' => 'rejected',
        ]);
    }
    
    /**
     * Sync invoice status from MyInvois
     */
    public function syncInvoiceStatus(Invoice $invoice): bool
    {
        if (!$invoice->myinvois_uid) {
            return false;
        }

        try {
            $this->authenticate();
            
            $result = $this->myInvois->document()->details(uuid: $invoice->myinvois_uid);
            
            if ($result['success'] ?? false) {
                $status = $result['data']['status'] ?? null;
                $dateTimeValidated = $result['data']['dateTimeValidated'] ?? $result['data']['dateTimeReceived'] ?? null;
                
                // Format the date for MySQL if present
                $validationDateTime = $dateTimeValidated ? \Carbon\Carbon::parse($dateTimeValidated)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') : null;
                
                $submission = $invoice->myinvoisSubmission;
                
                if ($status === 'Valid') {
                    $updateData = ['status' => 'validated'];
                    if ($validationDateTime) $updateData['validation_date_time'] = $validationDateTime;
                    $invoice->update($updateData);
                    
                    if ($submission) {
                        $submission->update(['status' => 'accepted']);
                    }
                } elseif ($status === 'Invalid') {
                    // Extract errors
                    $errors = [];
                    $validationSteps = $result['data']['validationResults']['validationSteps'] ?? [];
                    foreach ($validationSteps as $step) {
                        if (isset($step['status']) && $step['status'] === 'Invalid' && isset($step['error'])) {
                            if (isset($step['error']['innerError']) && is_array($step['error']['innerError'])) {
                                foreach ($step['error']['innerError'] as $inner) {
                                    $errors[] = $inner['error'] ?? 'Validation Error';
                                }
                            } else {
                                $errors[] = $step['error']['error'] ?? 'Validation Error';
                            }
                        }
                    }
                    
                    $rejectionReason = !empty($errors) ? implode(" | ", $errors) : 'Invalid document';
                    
                    $updateData = ['status' => 'invalid'];
                    if ($validationDateTime) $updateData['validation_date_time'] = $validationDateTime;
                    $invoice->update($updateData);
                    
                    if ($submission) {
                        $submission->update([
                            'status' => 'invalid',
                            'rejection_reason' => $rejectionReason,
                        ]);
                    }
                } elseif ($status === 'Cancelled') {
                    $updateData = ['status' => 'cancelled'];
                    if ($validationDateTime) $updateData['validation_date_time'] = $validationDateTime;
                    $invoice->update($updateData);
                    // For submission, we can leave it as accepted or update if we add cancelled to enum. 
                    // To be safe, we just update the invoice status, since the submission was technically accepted.
                }
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('MyInvois sync failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cancel validated invoice
     */
    public function cancelInvoice(Invoice $invoice): bool
    {
        try {
            $this->authenticate();
            
            $result = $this->myInvois->document()->cancel(
                params: ['uuid' => $invoice->myinvois_uid],
                payload: [
                    'status' => 'cancelled',
                    'reason' => 'User requested cancellation'
                ]
            );
            
            if ($result['success'] ?? false) {
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
     * Validate invoice format (dry run) - Deprecated as LHDN V1.0 API has no explicit validation endpoint
     */
    public function validateInvoiceFormat(Invoice $invoice): array
    {
        return [
            'valid' => true,
            'message' => 'Format validation is handled during submission in v1.0 SDK',
        ];
    }
    
    /**
     * Map internal Invoice model to Laraditz SDK DTOs
     */
    protected function prepareInvoicePayload(Invoice $invoice): SdkInvoice
    {
        $taxSchemeOTH = new TaxScheme(ID: new Data(value: 'OTH', attributes: ['schemeID' => 'UN/ECE 5153', 'schemeAgencyID' => '6']));
        $myrCountry   = new Country(IdentificationCode: new IdentificationCode(value: $invoice->supplier_country ?: 'MYS', listID: 'ISO3166-1', listAgencyID: '6'));
        $buyerCountry = new Country(IdentificationCode: new IdentificationCode(value: $invoice->buyer_country ?: 'MYS', listID: 'ISO3166-1', listAgencyID: '6'));

        // Supplier
        $supplierParty = new Party(
            IndustryClassificationCode: new Data(value: $invoice->supplier_msic_code ?: '47222', attributes: ['name' => $invoice->supplier_business_activity_description ?: 'Retail']),
            PartyIdentification: [
                new PartyIdentification(ID: new Data(value: $invoice->supplier_tin, attributes: ['schemeID' => 'TIN'])),
                new PartyIdentification(ID: new Data(value: $invoice->supplier_registration_number, attributes: ['schemeID' => str_starts_with($invoice->supplier_tin, 'IG') ? 'NRIC' : 'BRN'])),
            ],
            PostalAddress: new PostalAddress(CityName: $invoice->supplier_city ?: 'NA', PostalZone: $invoice->supplier_postal_code ?: 'NA', CountrySubentityCode: $invoice->supplier_state ?: '14',
                AddressLine: [new AddressLine(Line: $invoice->supplier_address_line1 ?: 'NA')], Country: $myrCountry),
            PartyLegalEntity: new PartyLegalEntity(RegistrationName: new Data(value: $invoice->supplier_name)),
            Contact: new Contact(Telephone: $this->sanitizePhone($invoice->supplier_contact_number), ElectronicMail: $invoice->supplier_email ?: 'test@test.com'),
        );

        // Buyer
        $buyerParty = new Party(
            PartyIdentification: [
                new PartyIdentification(ID: new Data(value: $invoice->buyer_tin, attributes: ['schemeID' => 'TIN'])),
                new PartyIdentification(ID: new Data(value: $invoice->buyer_registration_number ?: 'NA', attributes: ['schemeID' => str_starts_with($invoice->buyer_tin, 'IG') ? 'NRIC' : 'BRN'])),
            ],
            PostalAddress: new PostalAddress(CityName: $invoice->buyer_city ?: 'NA', PostalZone: $invoice->buyer_postal_code ?: 'NA', CountrySubentityCode: $invoice->buyer_state ?: '14',
                AddressLine: [new AddressLine(Line: $invoice->buyer_address_line1 ?: 'NA')], Country: $buyerCountry),
            PartyLegalEntity: new PartyLegalEntity(RegistrationName: new Data(value: $invoice->buyer_name)),
            Contact: new Contact(Telephone: $this->sanitizePhone($invoice->buyer_contact_number), ElectronicMail: $invoice->buyer_email ?: 'buyer@test.com'),
        );

        // Map Lines
        $sdkLines = [];
        foreach ($invoice->lineItems as $item) {
            $classCode = $item->classification_code;
            if (empty($classCode) || $classCode === '00000' || strlen(trim($classCode)) !== 3) {
                $classCode = '022';
            }
            
            $taxType = $item->tax_type ?: '01';
            $taxType = str_pad(trim($taxType), 2, '0', STR_PAD_LEFT);

            $sdkLines[] = new InvoiceLine(
                ID: (string) $item->line_number,
                InvoicedQuantity: new Data(value: (string) $item->quantity, attributes: ['unitCode' => $item->unit_of_measure ?: 'C62']),
                LineExtensionAmount: $this->myr($item->total_excluding_tax_per_line),
                TaxTotal: new TaxTotal(TaxAmount: $this->myr($item->tax_amount),
                    TaxSubtotal: new TaxSubtotal(TaxableAmount: $this->myr($item->total_excluding_tax_per_line), TaxAmount: $this->myr($item->tax_amount), Percent: (string) $item->tax_rate,
                        TaxCategory: new TaxCategory(ID: $taxType, TaxExemptionReason: $item->tax_exemption_reason, TaxScheme: $taxSchemeOTH))),
                Item: new Item(Description: $item->product_service_description,
                    CommodityClassification: [new CommodityClassification(ItemClassificationCode: new Data(value: $classCode, attributes: ['listID' => 'CLASS']))]),
                Price: new Price(PriceAmount: $this->myr($item->unit_price)),
                ItemPriceExtension: new ItemPriceExtension(Amount: $this->myr($item->total_excluding_tax_per_line)),
            );
        }
        
        $issueDateTime = clone $invoice->invoice_date_time;
        // Fix future date issue by setting to UTC
        $issueDateTime->setTimezone('UTC');

        // Main Invoice Data
        return new SdkInvoice(
            ID: $invoice->invoice_number,
            IssueDate: $issueDateTime, 
            IssueTime: clone $issueDateTime, // Package formats this automatically to H:i:s\Z
            InvoiceTypeCode: new InvoiceTypeCode(value: $invoice->invoice_type, listVersionID: '1.0'),
            DocumentCurrencyCode: $invoice->currency_code, TaxCurrencyCode: $invoice->currency_code,
            AccountingSupplierParty: new AccountingSupplierParty(Party: $supplierParty),
            AccountingCustomerParty: new AccountingCustomerParty(Party: $buyerParty),
            TaxTotal: new TaxTotal(TaxAmount: $this->myr($invoice->total_tax_amount), TaxSubtotal: new TaxSubtotal(TaxableAmount: $this->myr($invoice->total_excluding_tax), TaxAmount: $this->myr($invoice->total_tax_amount), Percent: '0', TaxCategory: new TaxCategory(ID: '01', TaxScheme: $taxSchemeOTH))),
            LegalMonetaryTotal: new LegalMonetaryTotal(
                LineExtensionAmount: $this->myr($invoice->total_excluding_tax), TaxExclusiveAmount: $this->myr($invoice->total_excluding_tax), TaxInclusiveAmount: $this->myr($invoice->total_including_tax),
                AllowanceTotalAmount: $this->myr($invoice->total_discount_value), ChargeTotalAmount: $this->myr($invoice->total_fee_charge_amount), PayableAmount: $this->myr($invoice->total_payable_amount)
            ),
            InvoiceLine: $sdkLines,
        );
    }
    
    /**
     * Authenticate with MyInvois using SDK
     */
    protected function authenticate(): void
    {
        $this->myInvois->auth()->token(
            client_id: $this->clientId, 
            client_secret: $this->clientSecret, 
            grant_type: 'client_credentials', 
            scope: 'InvoicingAPI'
        );
    }
    
    /**
     * Generate submission reference
     */
    protected function generateSubmissionReference(): string
    {
        return 'SUB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }
}