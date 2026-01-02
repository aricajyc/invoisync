<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->invoice_type,
            'invoice_type_name' => $this->invoice_type_name,
            'invoice_date_time' => $this->invoice_date_time?->toIso8601String(),
            'original_einvoice_reference' => $this->original_einvoice_reference,
            
            // Billing Information
            'frequency_of_billing' => $this->frequency_of_billing,
            'billing_period_start_date' => $this->billing_period_start_date?->toDateString(),
            'billing_period_end_date' => $this->billing_period_end_date?->toDateString(),
            
            // Supplier Details
            'supplier' => [
                'name' => $this->supplier_name,
                'tin' => $this->supplier_tin,
                'registration_number' => $this->supplier_registration_number,
                'sst_registration_number' => $this->supplier_sst_registration_number,
                'tourism_tax_number' => $this->supplier_tourism_tax_number,
                'email' => $this->supplier_email,
                'msic_code' => $this->supplier_msic_code,
                'business_activity' => $this->supplier_business_activity_description,
                'address' => $this->supplier_full_address,
                'contact_number' => $this->supplier_contact_number,
            ],
            
            // Buyer Details
            'buyer' => [
                'name' => $this->buyer_name,
                'tin' => $this->buyer_tin,
                'registration_number' => $this->buyer_registration_number,
                'sst_registration_number' => $this->buyer_sst_registration_number,
                'email' => $this->buyer_email,
                'address' => $this->buyer_full_address,
                'contact_number' => $this->buyer_contact_number,
            ],
            
            // Payment Information
            'payment' => $this->when($this->payment_mode, [
                'mode' => $this->payment_mode,
                'terms' => $this->payment_terms,
                'amount' => $this->payment_amount,
                'date' => $this->payment_date?->toDateString(),
                'reference_number' => $this->payment_reference_number,
                'bank_account' => $this->bank_account_number,
            ]),
            
            // Shipping Information
            'shipping' => $this->when($this->has_shipping_info, [
                'recipient_name' => $this->shipping_recipient_name,
                'recipient_tin' => $this->shipping_recipient_tin,
                'recipient_registration' => $this->shipping_recipient_registration,
                'address_line1' => $this->shipping_address_line1,
                'address_line2' => $this->shipping_address_line2,
                'address_line3' => $this->shipping_address_line3,
                'postal_code' => $this->shipping_postal_code,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'country' => $this->shipping_country,
            ]),
            
            // Customs Information
            'customs' => $this->when($this->has_customs_info, [
                'form_reference' => $this->customs_form_reference,
                'incoterms' => $this->incoterms,
                'fta_info' => $this->free_trade_agreement_info,
                'authorization_number' => $this->authorisation_number_for_certified_exporter,
            ]),
            
            // Financial Totals
            'financials' => [
                'currency_code' => $this->currency_code,
                'currency_exchange_rate' => $this->currency_exchange_rate,
                'total_excluding_tax' => number_format($this->total_excluding_tax, 2),
                'total_tax_amount' => number_format($this->total_tax_amount, 2),
                'total_including_tax' => number_format($this->total_including_tax, 2),
                'total_discount_value' => number_format($this->total_discount_value, 2),
                'total_fee_charge_amount' => number_format($this->total_fee_charge_amount, 2),
                'total_payable_amount' => number_format($this->total_payable_amount, 2),
            ],
            
            // MyInvois Fields
            'myinvois' => [
                'uid' => $this->myinvois_uid,
                'irbm_unique_identifier' => $this->irbm_unique_identifier,
                'qr_code_data' => $this->qr_code_data,
                'validation_date_time' => $this->validation_date_time?->toIso8601String(),
            ],
            
            // Status & Metadata
            'status' => $this->status,
            'is_draft' => $this->is_draft,
            'is_validated' => $this->is_validated,
            'is_submitted' => $this->is_submitted,
            'is_editable' => $this->isEditable(),
            'can_submit' => $this->canSubmit(),
            'can_cancel' => $this->canCancel(),
            
            // Relationships
            'line_items' => InvoiceLineItemResource::collection($this->whenLoaded('lineItems')),
            'tax_summaries' => TaxSummaryResource::collection($this->whenLoaded('taxSummaries')),
            'validation_results' => ValidationResultResource::collection($this->whenLoaded('validationResults')),
            'myinvois_submission' => new MyinvoisSubmissionResource($this->whenLoaded('myinvoisSubmission')),
            'anomaly_detections' => AnomalyDetectionResource::collection($this->whenLoaded('anomalyDetections')),
            
            // Timestamps
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}