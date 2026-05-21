<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        // Search by invoice number or buyer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%");
            });
        }

        $invoices = $query->latest()->paginate($request->per_page ?? 15);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation would typically happen via FormRequest or here
        // For brevity assuming basic data presence or delegated to Service/Model
        $validated = $request->validate([
            'invoice_type' => 'required|string',
            'buyer_name' => 'required|string',
            'line_items' => 'required|array|min:1',
            // Add other necessary validations
        ]);

        // Merge all request data for service processing
        $data = $request->all(); // Or $request->only(...)

        $invoice = $this->invoiceService->createInvoice($data, $request->user());

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['lineItems', 'taxSummaries']);

        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be updated'], 422);
        }

        $invoice = $this->invoiceService->updateInvoice($invoice, $request->all());

        return new InvoiceResource($invoice);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        Gate::authorize('delete', $invoice);

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be deleted'], 422);
        }

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    /**
     * Duplicate the specified invoice.
     */
    public function duplicate(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $newInvoice = $this->invoiceService->duplicateInvoice($invoice);

        return (new InvoiceResource($newInvoice))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Validate the invoice (Mock/Placeholder for now).
     */
    public function validateInvoice(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);
        // Logic to validate invoice
        return response()->json(['message' => 'Invoice validated']);
    }

    /**
     * Submit to MyInvois (Mock/Placeholder).
     */
    public function submitToMyInvois(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);
        // Logic to submit
        return response()->json(['message' => 'Invoice submitted']);
    }

    /**
     * Cancel the invoice (Mock/Placeholder).
     */
    public function cancel(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);
        // Logic to cancel
        return response()->json(['message' => 'Invoice cancelled']);
    }

    /**
     * Get statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_issued' => Invoice::where('user_id', $user->id)->count(),
            'total_draft' => Invoice::where('user_id', $user->id)->where('status', 'draft')->count(),
            'total_validated' => Invoice::where('user_id', $user->id)->where('status', 'validated')->count(),
            'total_rejected' => Invoice::where('user_id', $user->id)->whereIn('status', ['rejected', 'invalid'])->count(),
        ];

        return response()->json($stats);
    }
    
    /**
     * Export PDF.
     */
    public function exportPdf(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);
        
        $pdf = $this->invoiceService->generatePdf($invoice);
        
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
    
    // Add missing methods referenced in routes aliases if any
    public function getQrCode(Invoice $invoice) {
        // ...
        return response()->json(['qr_code' => '...']);
    }
}
