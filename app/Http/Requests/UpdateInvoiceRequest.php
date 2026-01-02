<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');
        
        // Only allow editing draft or rejected invoices
        return $invoice && in_array($invoice->status, ['draft', 'rejected']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        
        // Make certain fields optional for updates
        $optionalFields = [
            'invoice_type',
            'invoice_date_time',
            'line_items',
        ];
        
        foreach ($optionalFields as $field) {
            if (isset($rules[$field])) {
                $rules[$field] = array_filter($rules[$field], fn($rule) => $rule !== 'required');
                if (empty($rules[$field])) {
                    $rules[$field] = ['sometimes'];
                } else {
                    array_unshift($rules[$field], 'sometimes');
                }
            }
        }
        
        return $rules;
    }
}
