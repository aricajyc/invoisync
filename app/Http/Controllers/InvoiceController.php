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
            'total_amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'line_items' => 'required|integer',
        ]);

        $inputJson = json_encode($validated);
        
        // Escape the JSON string for command line argument safely
        // In Windows/PowerShell, escaping quotes for JSON args can be tricky.
        // Using symfony process directly handles arguments better usually, but here we construct the command.
        // Let's use standard Process facade with array arguments which is safer.
        
        $scriptPath = base_path('ml_service/detect_anomaly.py');
        
        // Check if python or python3 is available
        $pythonCommand = 'python'; // Default to python for Windows usually
        
        $result = Process::run([$pythonCommand, $scriptPath, $inputJson]);

        if ($result->failed()) {
             // Fallback to python3 if python failed (common in some envs) OR return error
             // For now return error with output for debugging
             return response()->json([
                 'status' => 'error',
                 'error' => 'ML Service failed: ' . $result->errorOutput() . ' ' . $result->output(),
             ], 500);
        }

        try {
            $output = json_decode($result->output(), true);
            return response()->json($output);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to parse ML output',
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
            ->paginate(10)
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
        ]);

        $invoices = $request->input('invoices');
        $successful = 0;
        $failed = 0;

        foreach ($invoices as $row) {
            try {
                $invoiceService->createInvoice($row['data'], $request->user());
                $successful++;
            } catch (\Exception $e) {
                // Ignore single row failure or log it
                $failed++;
            }
        }

        \App\Models\UserActivity::create([
            'user_id' => $request->user()->id,
            'action' => 'Bulk Commit',
            'description' => "Committed bulk upload: {$successful} successful, {$failed} failed.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('invoices.index')
            ->with('status', "Bulk import complete. ({$successful} imported, {$failed} failed)");
    }
}