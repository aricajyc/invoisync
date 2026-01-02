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
        $businessProfileId = $this->route('business_profile')?->id;
        
        return [
            'business_name' => ['required', 'string', 'max:300'],
            'business_registration_number' => [
                'required',
                'string',
                'max:50',
                'unique:business_profiles,business_registration_number,' . $businessProfileId
            ],
            'tax_identification_number' => [
                'required',
                'string',
                'size:20',
                'regex:/^[A-Z][0-9]{19}$/',
                'unique:business_profiles,tax_identification_number,' . $businessProfileId
            ],
            'business_type' => ['required', 'string', 'max:100'],
            'business_address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:20', 'regex:/^[+0-9\s\-()]+$/'],
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
