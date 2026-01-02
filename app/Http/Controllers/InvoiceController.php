<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\ValidateInvoiceRequest;
use App\Http\Requests\SubmitToMyInvoisRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceCollection;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\MyInvoisService;
use App\Services\ValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected MyInvoisService $myinvoisService,
        protected ValidationService $validationService
    ) {}

    /**
     * Display a listing of invoices
     * 
     * @group Invoice Management
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam status string Filter by status. Example: draft
     * @queryParam invoice_type string Filter by type. Example: 01
     * @queryParam date_from date Filter from date. Example: 2025-01-01
     * @queryParam date_to date Filter to date. Example: 2025-12-31
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['lineItems', 'taxSummaries', 'user'])
            ->where('user_id', auth()->id());

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $invoices = $query->paginate($request->get('per_page', 15));

        return response()->json(new InvoiceCollection($invoices));
    }

    /**
     * Store a newly created invoice
     * 
     * @group Invoice Management
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = $this->invoiceService->createInvoice(
                $request->validated(),
                auth()->user()
            );

            DB::commit();

            return response()->json([
                'message' => 'Invoice created successfully',
                'data' => new InvoiceResource($invoice->load(['lineItems', 'taxSummaries'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified invoice
     * 
     * @group Invoice Management
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'lineItems',
            'taxSummaries',
            'validationResults.rule',
            'myinvoisSubmission',
            'anomalyDetections'
        ]);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }

    /**
     * Update the specified invoice
     * 
     * @group Invoice Management
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->isEditable()) {
            return response()->json([
                'message' => 'Invoice cannot be edited in current status',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updatedInvoice = $this->invoiceService->updateInvoice(
                $invoice,
                $request->validated()
            );

            DB::commit();

            return response()->json([
                'message' => 'Invoice updated successfully',
                'data' => new InvoiceResource($updatedInvoice->load(['lineItems', 'taxSummaries'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified invoice
     * 
     * @group Invoice Management
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        if (!$invoice->isEditable()) {
            return response()->json([
                'message' => 'Invoice cannot be deleted in current status',
            ], 422);
        }

        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Validate invoice against MyInvois rules
     * 
     * @group Invoice Validation
     */
    public function validate(ValidateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('validate', $invoice);

        try {
            $validationType = $request->input('validation_type', 'comprehensive');
            
            $results = $this->validationService->validateInvoice($invoice, $validationType);

            $hasErrors = $results->where('result_type', 'fail')->count() > 0;

            if (!$hasErrors) {
                $invoice->update(['status' => 'validated']);
            }

            return response()->json([
                'message' => $hasErrors ? 'Validation completed with errors' : 'Invoice validated successfully',
                'data' => [
                    'invoice' => new InvoiceResource($invoice),
                    'validation_results' => $results,
                    'has_errors' => $hasErrors,
                    'error_count' => $results->where('result_type', 'fail')->count(),
                    'warning_count' => $results->where('result_type', 'warning')->count(),
                ],
            ], $hasErrors ? 422 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit invoice to MyInvois
     * 
     * @group MyInvois Integration
     */
    public function submitToMyInvois(SubmitToMyInvoisRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('submit', $invoice);

        if (!$invoice->canSubmit()) {
            return response()->json([
                'message' => 'Invoice cannot be submitted in current status',
            ], 422);
        }

        try {
            $submission = $this->myinvoisService->submitInvoice(
                $invoice,
                $request->input('digital_signature')
            );

            if ($submission->status === 'accepted') {
                return response()->json([
                    'message' => 'Invoice submitted successfully to MyInvois',
                    'data' => [
                        'invoice' => new InvoiceResource($invoice->fresh()),
                        'myinvois_uid' => $submission->myinvois_uid,
                        'qr_code_url' => $submission->qr_code_url,
                    ],
                ]);
            } else {
                return response()->json([
                    'message' => 'Invoice submission rejected by MyInvois',
                    'error' => $submission->rejection_reason,
                    'data' => [
                        'submission' => $submission,
                    ],
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit invoice to MyInvois',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel validated invoice
     * 
     * @group Invoice Management
     */
    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->authorize('cancel', $invoice);

        if (!$invoice->canCancel()) {
            return response()->json([
                'message' => 'Invoice cannot be cancelled. 72-hour window has passed or invoice is not validated.',
            ], 422);
        }

        try {
            // If submitted to MyInvois, call cancellation API
            if ($invoice->myinvois_uid) {
                $this->myinvoisService->cancelInvoice($invoice);
            }

            $invoice->update(['status' => 'cancelled']);

            return response()->json([
                'message' => 'Invoice cancelled successfully',
                'data' => new InvoiceResource($invoice),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate an invoice
     * 
     * @group Invoice Management
     */
    public function duplicate(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        try {
            $newInvoice = $this->invoiceService->duplicateInvoice($invoice);

            return response()->json([
                'message' => 'Invoice duplicated successfully',
                'data' => new InvoiceResource($newInvoice->load(['lineItems', 'taxSummaries'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to duplicate invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get invoice statistics
     * 
     * @group Invoice Management
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $stats = [
            'total_invoices' => Invoice::where('user_id', $userId)
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'draft_count' => Invoice::where('user_id', $userId)
                ->draft()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'validated_count' => Invoice::where('user_id', $userId)
                ->validated()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'submitted_count' => Invoice::where('user_id', $userId)
                ->submitted()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'rejected_count' => Invoice::where('user_id', $userId)
                ->rejected()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'total_amount' => Invoice::where('user_id', $userId)
                ->dateRange($dateFrom, $dateTo)
                ->sum('total_payable_amount'),
            'average_amount' => Invoice::where('user_id', $userId)
                ->dateRange($dateFrom, $dateTo)
                ->avg('total_payable_amount'),
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Export invoice to PDF
     * 
     * @group Invoice Management
     */
    public function exportPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $pdf = $this->invoiceService->generatePdf($invoice);

            return $pdf->download("invoice-{$invoice->invoice_number}.pdf");

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR code for validated invoice
     * 
     * @group Invoice Management
     */
    public function getQrCode(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        if (empty($invoice->qr_code_data)) {
            return response()->json([
                'message' => 'QR code not available for this invoice',
            ], 404);
        }

        return response()->json([
            'data' => [
                'qr_code_data' => $invoice->qr_code_data,
                'myinvois_uid' => $invoice->myinvois_uid,
            ],
        ]);
    }
}