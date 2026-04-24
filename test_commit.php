<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(2); // user from logs
if (!$user) {
    // maybe user 1 has a profile?
    $user = \App\Models\User::first();
    if (!$user) die("No user found\n");
}

dump('Has profile: ' . ($user->businessProfile !== null));

$flatData = [
    'invoice_number' => 'TEST-002',
    'invoice_type' => '01',
    'invoice_date_time' => now(),
    'buyer_name' => 'John Doe',
    'buyer_registration_number' => '123456',
    'buyer_state' => '14',
    'buyer_city' => 'Kuala Lumpur',
    'buyer_address_line0' => 'Test Address',
    
    'supplier_state' => '14',
    'supplier_city' => 'Kuala Lumpur',
    'supplier_address_line0' => 'Test Address',
    'supplier_postal_code' => '50000',

    'item_product_service_description' => 'Test Item',
    'item_unit_of_measure' => 'EA',
    'item_quantity' => 1,
    'item_unit_price' => 10,
    'item_total_excluding_tax_per_line' => 10,
    'item_total_including_tax_per_line' => 10,
];

try {
    $nestedData = app(\App\Services\BulkUploadService::class)->formatFlatToNested($flatData);
    app(\App\Services\InvoiceService::class)->createInvoice($nestedData, $user);
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
