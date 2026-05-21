<?php

return [
    'base_url' => env('MYINVOIS_BASE_URL', 'https://api.myinvois.hasil.gov.my'),
    'identity_url' => env('MYINVOIS_IDENTITY_URL', 'https://identity.myinvois.hasil.gov.my'),
    'api_key' => env('MYINVOIS_API_KEY'),
    'client_id' => env('MYINVOIS_CLIENT_ID'),
    'client_secret' => env('MYINVOIS_CLIENT_SECRET'),
    'sandbox_mode' => env('MYINVOIS_SANDBOX_MODE', true),
    
    'timeout' => 30, // seconds
    'retry_attempts' => 3,
    
    'valid_msic_codes' => collect(
        file_exists(public_path('codes/MSICSubCategoryCodes.json')) 
            ? json_decode(file_get_contents(public_path('codes/MSICSubCategoryCodes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_unit_types' => collect(
        file_exists(public_path('codes/UnitTypes.json'))
            ? json_decode(file_get_contents(public_path('codes/UnitTypes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_tax_types' => collect(
        file_exists(public_path('codes/TaxTypes.json'))
            ? json_decode(file_get_contents(public_path('codes/TaxTypes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_state_codes' => collect(
        file_exists(public_path('codes/StateCodes.json'))
            ? json_decode(file_get_contents(public_path('codes/StateCodes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_payment_methods' => collect(
        file_exists(public_path('codes/PaymentMethods.json'))
            ? json_decode(file_get_contents(public_path('codes/PaymentMethods.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_invoice_types' => collect(
        file_exists(public_path('codes/EInvoiceTypes.json'))
            ? json_decode(file_get_contents(public_path('codes/EInvoiceTypes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_currency_codes' => collect(
        file_exists(public_path('codes/CurrencyCodes.json'))
            ? json_decode(file_get_contents(public_path('codes/CurrencyCodes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_country_codes' => collect(
        file_exists(public_path('codes/CountryCodes.json'))
            ? json_decode(file_get_contents(public_path('codes/CountryCodes.json')), true)
            : []
    )->pluck('Code')->toArray(),

    'valid_classification_codes' => collect(
        file_exists(public_path('codes/ClassificationCodes.json'))
            ? json_decode(file_get_contents(public_path('codes/ClassificationCodes.json')), true)
            : []
    )->pluck('Code')->toArray(),
];