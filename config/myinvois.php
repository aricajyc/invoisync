<?php

return [
    'base_url' => env('MYINVOIS_BASE_URL', 'https://api.myinvois.hasil.gov.my'),
    'api_key' => env('MYINVOIS_API_KEY'),
    'client_id' => env('MYINVOIS_CLIENT_ID'),
    'client_secret' => env('MYINVOIS_CLIENT_SECRET'),
    'sandbox_mode' => env('MYINVOIS_SANDBOX_MODE', true),
    
    'timeout' => 30, // seconds
    'retry_attempts' => 3,
    
    'valid_msic_codes' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/MSICSubCategoryCodes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_unit_types' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/UnitTypes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_tax_types' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/TaxTypes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_state_codes' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/StateCodes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_payment_methods' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/PaymentMethods.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_invoice_types' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/EInvoiceTypes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_currency_codes' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/CurrencyCodes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_country_codes' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/CountryCodes.json')),
            true
        )
    )->pluck('Code')->toArray(),

    'valid_classification_codes' => collect(
        json_decode(
            file_get_contents(storage_path('app/codes/ClassificationCodes.json')),
            true
        )
    )->pluck('Code')->toArray(),
];