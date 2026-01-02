<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitToMyInvoisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');
        
        // Only submit validated invoices
        return $invoice && 
               $invoice->status === 'validated' && 
               empty($invoice->myinvois_uid);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'submission_type' => ['required', 'in:single,bulk'],
            'digital_signature' => ['required', 'string'],
        ];
    }
}
