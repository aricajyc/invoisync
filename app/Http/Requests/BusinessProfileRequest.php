<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessProfileRequest extends FormRequest
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
        $businessProfileId = $this->user()->businessProfile?->id;
        
        return [
            'business_name' => ['required', 'string', 'max:150'],
            'business_registration_number' => [
                'required',
                'string',
                'max:20',
                'unique:business_profiles,business_registration_number,' . $businessProfileId
            ],
            'tax_identification_number' => [
                'required',
                'string',
                'max:20', // Changed from size:15 to max:20 to allow 14 chars as requested and other formats
                // 'regex:/^[A-Z][0-9]{19}$/', // Keeping regex off or strictly following user IRBM spec? Safe to require string first.
                'unique:business_profiles,tax_identification_number,' . $businessProfileId
            ],
            'sst_registration_number' => ['nullable', 'string', 'max:30'],
            'tourism_tax_registration_number' => ['nullable', 'string', 'max:30'],
            'msic_code' => ['required', 'string', 'max:10'], // Could add 'exists' check if we loaded codes into DB, but using file for now.
            'business_activity_description' => ['required', 'string', 'max:300'],
            'address_line_0' => ['required', 'string', 'max:150'],
            'address_line_1' => ['nullable', 'string', 'max:150'],
            'address_line_2' => ['nullable', 'string', 'max:150'],
            'postal_zone' => ['required', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:50'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:3'], // MYS is 3 chars
            'contact_email' => ['required', 'email', 'max:150'],
            'contact_phone' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_identification_number.regex' => 'TIN must start with a letter followed by 19 digits',
            'tax_identification_number.unique' => 'This TIN is already registered',
            'country.regex' => 'Country code must be ISO 3166-1 alpha-2 format (e.g., MY)',
        ];
    }
}
