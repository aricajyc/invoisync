<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\BusinessProfile;
use App\Services\MyInvoisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMyInvoisStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find invoices that are submitted but not yet validated or rejected
        // 'submitted' and 'approved' are used somewhat interchangeably in our logic (approved = accepted by gateway).
        // Let's check invoices with status 'submitted' or 'approved' and have a myinvois_uid.
        $invoices = Invoice::whereIn('status', ['submitted', 'approved'])
            ->whereNotNull('myinvois_uid')
            ->get();

        foreach ($invoices as $invoice) {
            $businessProfile = BusinessProfile::where('user_id', $invoice->user_id)->first();
            
            if ($businessProfile && $businessProfile->myinvois_client_id && $businessProfile->myinvois_client_secret) {
                try {
                    $myInvoisService = new MyInvoisService(
                        $businessProfile->myinvois_client_id, 
                        $businessProfile->myinvois_client_secret
                    );
                    
                    $myInvoisService->syncInvoiceStatus($invoice);
                } catch (\Exception $e) {
                    Log::error("Scheduled sync failed for Invoice {$invoice->id}: " . $e->getMessage());
                }
            }
        }
    }
}
