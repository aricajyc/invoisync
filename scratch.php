<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profile = \App\Models\BusinessProfile::where('user_id', 2)->first();
print_r($profile ? $profile->toArray() : "No Profile");
