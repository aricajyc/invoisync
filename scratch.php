<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profile = App\Models\BusinessProfile::whereNotNull('myinvois_client_id')->first();
$myInvois = new \Laraditz\MyInvois\MyInvois(is_sandbox: true, client_id: $profile->myinvois_client_id, client_secret: $profile->myinvois_client_secret);
$myInvois->auth()->token(client_id: $profile->myinvois_client_id, client_secret: $profile->myinvois_client_secret, grant_type: 'client_credentials', scope: 'InvoicingAPI');

// INV-202607006 UUID from submission? Wait, let's query the DB for the UID
$inv = App\Models\Invoice::where('invoice_number', 'INV-202607006')->first();
if ($inv && $inv->myinvois_uid) {
    try {
        $details = $myInvois->document()->details(uuid: $inv->myinvois_uid);
        echo "DETAILS:\n";
        print_r($details);
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No UUID found\n";
}
