<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceTemplateRequest extends FormRequest
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
            'template_name' => ['required', 'string', 'max:255'],
            'template_data' => ['required', 'array'],
            'template_data.supplier_name' => ['required', 'string'],
            'template_data.supplier_tin' => ['required', 'string', 'size:20'],
            'template_data.payment_terms' => ['nullable', 'string'],
            'template_data.default_line_items' => ['nullable', 'array'],
            'is_default' => ['boolean'],
        ];
    }
}
