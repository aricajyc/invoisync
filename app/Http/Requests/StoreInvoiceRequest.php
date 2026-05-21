<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ==================== INVOICE IDENTIFICATION ====================
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_type' => ['required', 'in:01,02,03,04'],
            'invoice_date_time' => ['required', 'date_format:Y-m-d H:i:s,Y-m-d\TH:i:s,Y-m-d\TH:i'],
            'original_einvoice_reference' => ['nullable', 'string', 'max:255', 
                Rule::requiredIf(fn() => in_array($this->invoice_type, ['02', '03', '04']))
            ],
            'frequency_of_billing' => ['nullable', 'in:01,02,03,04,05,06'],
            'billing_period_start_date' => ['nullable', 'date', 'before_or_equal:billing_period_end_date'],
            'billing_period_end_date' => ['nullable', 'date', 'after_or_equal:billing_period_start_date'],
            
            // ==================== SUPPLIER DETAILS (MANDATORY) ====================
            'supplier_name' => ['required', 'string', 'max:300'],
            'supplier_tin' => ['required', 'string', 'max:20', 'regex:/^[A-Z]{1,2}[0-9A-Z]{1,18}$/'],
            'supplier_registration_number' => ['required', 'string', 'max:50'],
            'supplier_sst_registration_number' => ['nullable', 'string', 'max:20'],
            'supplier_tourism_tax_number' => ['nullable', 'string', 'max:20'],
            'supplier_email' => ['required', 'email', 'max:255'],
            'supplier_msic_code' => ['required', 'string', 'size:5', 'regex:/^[0-9]{5}$/'],
            'supplier_business_activity_description' => ['required', 'string', 'max:300'],
            'supplier_address_line1' => ['required', 'string', 'max:255'],
            'supplier_address_line2' => ['nullable', 'string', 'max:255'],
            'supplier_address_line3' => ['nullable', 'string', 'max:255'],
            'supplier_postal_code' => ['nullable', 'string', 'max:20'],
            'supplier_city' => ['nullable', 'string', 'max:100'],
            'supplier_state' => ['required', 'string', 'max:100'],
            'supplier_country' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'supplier_contact_number' => ['required', 'string', 'max:20', 'regex:/^[+0-9\s\-()]+$/'],
            
            // ==================== BUYER DETAILS (MANDATORY) ====================
            'buyer_name' => ['required', 'string', 'max:300'],
            'buyer_tin' => ['required', 'string', 'max:20'],
            'buyer_registration_number' => ['required', 'string', 'max:50'],
            'buyer_sst_registration_number' => ['nullable', 'string', 'max:20'],
            'buyer_email' => ['required', 'email', 'max:255'],
            'buyer_address_line1' => ['required', 'string', 'max:255'],
            'buyer_address_line2' => ['nullable', 'string', 'max:255'],
            'buyer_address_line3' => ['nullable', 'string', 'max:255'],
            'buyer_postal_code' => ['nullable', 'string', 'max:20'],
            'buyer_city' => ['nullable', 'string', 'max:100'],
            'buyer_state' => ['required', 'string', 'max:100'],
            'buyer_country' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'buyer_contact_number' => ['nullable', 'string', 'max:20', 'regex:/^[+0-9\s\-()]+$/'],
            
            // ==================== PAYMENT INFORMATION (OPTIONAL) ====================
            'payment_mode' => ['nullable', 'in:01,02,03,04,05,06,07'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'payment_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'payment_date' => ['nullable', 'date'],
            'payment_reference_number' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            
            // ==================== SHIPPING INFORMATION (ANNEXURE) ====================
            'shipping_recipient_name' => ['nullable', 'string', 'max:300'],
            'shipping_recipient_tin' => ['nullable', 'string', 'max:20'],
            'shipping_recipient_registration' => ['nullable', 'string', 'max:50'],
            'shipping_address_line1' => ['nullable', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_address_line3' => ['nullable', 'string', 'max:255'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_country' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            
            // ==================== OTHER REFERENCES ====================
            'bill_reference_number' => ['nullable', 'string', 'max:100'],
            
            // ==================== CUSTOMS INFORMATION ====================
            'customs_form_reference' => ['nullable', 'string', 'max:100'],
            'incoterms' => ['nullable', 'string', 'max:10'],
            'free_trade_agreement_info' => ['nullable', 'string', 'max:255'],
            'authorisation_number_for_certified_exporter' => ['nullable', 'string', 'max:100'],
            
            // ==================== CURRENCY ====================
            'currency_code' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'currency_exchange_rate' => ['nullable', 'numeric', 'min:0', 'max:999999.999999'],
            
            // ==================== TOTALS ====================
            'total_discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'total_fee_charge_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            
            // ==================== LINE ITEMS (MANDATORY) ====================
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.classification_code' => ['required', 'string', 'max:10'],
            'line_items.*.product_service_description' => ['required', 'string', 'max:1000'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999999999.9999'],
            'line_items.*.unit_of_measure' => ['required', 'string', 'max:10'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'line_items.*.subtotal' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'line_items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'line_items.*.tax_type' => ['required', 'in:01,02,03,04,05,06'],
            'line_items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'line_items.*.tax_exemption_reason' => ['nullable', 'in:01,02,03,04,05,06,07'],
            'line_items.*.tax_exempted_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.charge_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.country_of_origin' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'line_items.*.product_tariff_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_tin.regex' => 'Supplier TIN format is invalid. It should start with 1-2 letters followed by alphanumeric characters (e.g., C2584563222, IG51167122090)',
            'supplier_msic_code.size' => 'Supplier MSIC code must be exactly 5 digits',
            'supplier_msic_code.regex' => 'Supplier MSIC code must contain only numbers',
            'supplier_country.size' => 'Country code must be ISO 3166-1 alpha-3 format (e.g., MYS)',
            'buyer_tin.max' => 'Buyer TIN must not exceed 20 characters (use EI00000000010 if not available)',
            'currency_code.regex' => 'Currency code must be ISO 4217 format (e.g., MYR, USD)',
            'line_items.required' => 'At least one line item is required',
            'line_items.*.classification_code.required' => 'Classification code is mandatory for each line item',
            'line_items.*.tax_type.in' => 'Tax type must be: 01-Sales Tax, 02-Service Tax, 03-Tourism Tax, 04-HRD, 05-WHT, 06-Others',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_tin' => 'Supplier Tax Identification Number',
            'supplier_msic_code' => 'Supplier MSIC Code',
            'buyer_tin' => 'Buyer Tax Identification Number',
            'line_items.*.classification_code' => 'Product Classification Code',
            'line_items.*.product_service_description' => 'Product/Service Description',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: Validate totals match line items
            $this->validateTotalsMatch($validator);
            
            // Custom validation: Currency exchange rate required if not MYR
            $this->validateCurrencyExchangeRate($validator);
            
            // Custom validation: Tax exemption reason required if tax rate is 0
            $this->validateTaxExemptionReason($validator);
        });
    }

    protected function validateTotalsMatch($validator): void
    {
        if (!$this->has('line_items')) {
            return;
        }

        $calculatedSubtotal = 0;
        $calculatedTax = 0;

        foreach ($this->line_items as $item) {
            $lineSubtotal = $item['subtotal'] ?? 0;
            $discount = $item['discount_amount'] ?? 0;
            $taxRate = $item['tax_rate'] ?? 0;
            
            $taxableAmount = $lineSubtotal - $discount;
            $taxAmount = $taxableAmount * ($taxRate / 100);
            
            $calculatedSubtotal += $taxableAmount;
            $calculatedTax += $taxAmount;
        }

        // Allow small rounding differences (0.01)
        if (abs($calculatedSubtotal - ($this->total_excluding_tax ?? 0)) > 0.01) {
            $validator->errors()->add(
                'total_excluding_tax',
                'Total excluding tax does not match sum of line items'
            );
        }

        if (abs($calculatedTax - ($this->total_tax_amount ?? 0)) > 0.01) {
            $validator->errors()->add(
                'total_tax_amount',
                'Total tax amount does not match sum of line item taxes'
            );
        }
    }

    protected function validateCurrencyExchangeRate($validator): void
    {
        if ($this->currency_code !== 'MYR' && empty($this->currency_exchange_rate)) {
            $validator->errors()->add(
                'currency_exchange_rate',
                'Currency exchange rate is required when currency is not MYR'
            );
        }
    }

    protected function validateTaxExemptionReason($validator): void
    {
        if (!$this->has('line_items')) {
            return;
        }

        foreach ($this->line_items as $index => $item) {
            if (($item['tax_rate'] ?? 0) == 0 && empty($item['tax_exemption_reason'])) {
                $validator->errors()->add(
                    "line_items.{$index}.tax_exemption_reason",
                    'Tax exemption reason is required when tax rate is 0%'
                );
            }
        }
    }
}
