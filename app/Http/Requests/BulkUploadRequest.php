<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only B2B users can bulk upload
        return auth()->user()->user_type === 'B2B';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240', // 10MB max
            ],
            'batch_reference' => ['nullable', 'string', 'max:100', 'unique:bulk_upload_batches,batch_reference'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a CSV or Excel file',
            'file.mimes' => 'File must be CSV or Excel format (.csv, .xlsx, .xls)',
            'file.max' => 'File size must not exceed 10MB',
        ];
    }
}
