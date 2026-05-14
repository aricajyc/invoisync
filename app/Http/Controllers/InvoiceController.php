<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Support\Facades\Process;

class InvoiceController extends Controller
{
    /**
     * Detect anomalies in invoice data using ML script.
     */
    public function detectAnomaly(Request $request)
    {
        $validated = $request->validate([
            'invoiceCodeNumber' => 'nullable|string',
            'invoiceDate' => 'required',
            'invoiceTypeCode' => 'required|string',
            'invoiceCurrencyCode' => 'required|string',
            'totalExcludingTax' => 'required|numeric',
            'totalTaxAmount' => 'required|numeric',
            'totalIncludingTax' => 'required|numeric',
            'unitPrice' => 'required|numeric',
            'itemTotalExcludingTax' => 'required|numeric',
            'itemSubtotal' => 'required|numeric',
            'taxType' => 'required|string',
            'buyerCountry' => 'required|string',
        ]);

        try {
            $apiKey = env('ML_API_KEY');
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-Key' => $apiKey
            ])->post(env('ML_API_URL') . '/validate', $validated);

            if ($response->failed()) {
                 return response()->json([
                     'status' => 'error',
                     'error' => 'ML Service failed: ' . $response->body(),
                 ], 500);
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to connect to ML Service: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::where('user_id', $request->user()->id);

        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }
        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('myinvois_uid')) {
            $query->where('myinvois_uid', trim($request->myinvois_uid));
        }
        if ($request->filled('original_einvoice_reference')) {
            $query->where('original_einvoice_reference', trim($request->original_einvoice_reference));
        }
        if ($request->filled('supplier_tin')) {
            $query->where('supplier_tin', trim($request->supplier_tin));
        }
        if ($request->filled('issued_date_from')) {
            $query->whereDate('invoice_date_time', '>=', $request->issued_date_from);
        }
        if ($request->filled('issued_date_to')) {
            $query->whereDate('invoice_date_time', '<=', $request->issued_date_to);
        }

        $invoices = $query->latest()
            ->paginate(20)
            ->withQueryString();

        $filters = $request->only([
            'invoice_number', 'invoice_type', 'status', 
            'myinvois_uid', 'original_einvoice_reference', 'supplier_tin',
            'issued_date_from', 'issued_date_to'
        ]);

        if (array_filter($filters)) {
            UserActivity::create([
                'user_id' => $request->user()->id,
                'action' => 'Searched Invoices',
                'description' => 'Applied filters: ' . json_encode(array_filter($filters)),
                'ip_address' => $request->ip(),
            ]);
        }

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Invoices/Create', [
            'businessProfile' => $request->user()->businessProfile,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreInvoiceRequest $request, \App\Services\InvoiceService $invoiceService)
    {
        try {
            $invoice = $invoiceService->createInvoice(
                $request->validated(),
                $request->user()
            );

            UserActivity::create([
                'user_id' => $request->user()->id,
                'action' => 'Created Invoice',
                'description' => "Created invoice {$invoice->invoice_number}",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('invoices.index')
                ->with('status', 'Invoice created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Invoice $invoice): Response
    {
        // Ensure user owns the invoice
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        $invoice->load(['lineItems']);

        return Inertia::render('Invoices/Create', [
            'businessProfile' => $request->user()->businessProfile,
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\StoreInvoiceRequest $request, Invoice $invoice, \App\Services\InvoiceService $invoiceService)
    {
        // Ensure user owns the invoice
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $invoiceService->updateInvoice(
                $invoice,
                $request->validated()
            );

            UserActivity::create([
                'user_id' => $request->user()->id,
                'action' => 'Updated Invoice',
                'description' => "Updated invoice {$invoice->invoice_number}",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('invoices.index')
                ->with('status', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update invoice: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle bulk uploading of invoices.
     * Stores parsed data in cache with a UUID key, redirects to GET route.
     * Using cache (not session) avoids Inertia redirect timing issues.
     */
    public function bulkUpload(Request $request, \App\Services\BulkUploadService $bulkService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt,xls'
        ]);

        try {
            $parsedData = $bulkService->parseForPreview($request->file('file'));

            // Store in cache with a unique key (TTL: 15 minutes)
            $cacheKey = 'bulk_preview_' . \Illuminate\Support\Str::uuid();
            cache()->put($cacheKey, [
                'data'     => $parsedData,
                'filename' => $request->file('file')->getClientOriginalName(),
            ], now()->addMinutes(15));

            return redirect()->route('invoices.bulk-review', ['key' => $cacheKey]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to parse file: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the bulk review page (GET).
     * Reads parsed data from cache using the UUID key from the query string.
     */
    public function bulkReview(Request $request)
    {
        $cacheKey = $request->query('key');
        $cached   = $cacheKey ? cache()->get($cacheKey) : null;

        if (!$cached) {
            return redirect()->route('invoices.index')
                ->with('error', 'No upload data found. Please upload a file first.');
        }

        // Remove from cache once read
        cache()->forget($cacheKey);

        return Inertia::render('Invoices/BulkReview', [
            'parsedData' => $cached['data'],
            'filename'   => $cached['filename'],
        ]);
    }

    /**
     * Commit the verified bulk upload data into invoices.
     */
    public function bulkCommit(Request $request, \App\Services\InvoiceService $invoiceService)
    {
        $request->validate([
            'invoices' => 'required|array',
            'invoices.*.data' => 'required|array',
            'filename' => 'nullable|string',
        ]);

        $invoices = $request->input('invoices');
        $filename = $request->input('filename', 'bulk_upload_' . date('Ymd_His') . '.csv');
        $totalRecords = count($invoices);
        
        $batch = \App\Models\BulkUploadBatch::create([
            'user_id' => $request->user()->id,
            'batch_reference' => 'BATCH-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'original_filename' => $filename,
            'file_path' => 'N/A', // Since we used cache, we don't have the permanent file path here
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'status' => 'processing',
            'upload_date' => now(),
            'processing_started_at' => now(),
        ]);

        $successful = 0;
        $failed = 0;

        $bulkService = app(\App\Services\BulkUploadService::class);
        foreach ($invoices as $index => $row) {
            try {
                $nestedData = $bulkService->formatFlatToNested($row['data']);
                // Assign batch id so invoice is linked to this batch
                $nestedData['bulk_upload_batch_id'] = $batch->id;
                
                $invoiceService->createInvoice($nestedData, $request->user());
                $successful++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Bulk commit row failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                
                // Record the error
                \App\Models\BulkUploadError::create([
                    'batch_id' => $batch->id,
                    'row_number' => $index + 2, // Assuming row 1 is header
                    'field_name' => 'general',
                    'error_type' => 'processing_error',
                    'error_message' => substr($e->getMessage(), 0, 500),
                    'severity' => 'high',
                ]);
                
                $failed++;
            }
            
            $batch->increment('processed_records');
        }

        $batch->update([
            'successful_records' => $successful,
            'failed_records' => $failed,
            'status' => $failed > 0 ? ($successful > 0 ? 'completed_with_errors' : 'failed') : 'completed',
            'processing_completed_at' => now(),
        ]);

        \App\Models\UserActivity::create([
            'user_id' => $request->user()->id,
            'action' => 'Bulk Commit',
            'description' => "Committed bulk upload batch {$batch->batch_reference}: {$successful} successful, {$failed} failed.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('invoices.index')
            ->with('status', "Bulk import complete. Batch Reference: {$batch->batch_reference}. ({$successful} imported, {$failed} failed)");
    }
}