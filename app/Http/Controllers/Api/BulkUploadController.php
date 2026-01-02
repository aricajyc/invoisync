<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUploadRequest;
use App\Http\Resources\BulkUploadBatchResource;
use App\Models\BulkUploadBatch;
use App\Services\BulkUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkUploadController extends Controller
{
    public function __construct(
        protected BulkUploadService $bulkUploadService
    ) {}

    /**
     * Upload bulk invoices file
     * 
     * @group Bulk Upload (B2B)
     */
    public function upload(BulkUploadRequest $request): JsonResponse
    {
        try {
            $batch = $this->bulkUploadService->processBulkUpload(
                $request->file('file'),
                auth()->user(),
                $request->input('batch_reference')
            );

            return response()->json([
                'message' => 'File uploaded successfully. Processing started.',
                'data' => new BulkUploadBatchResource($batch),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bulk upload batch status
     * 
     * @group Bulk Upload (B2B)
     */
    public function show(BulkUploadBatch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        $batch->load(['errors', 'invoices']);

        return response()->json([
            'data' => new BulkUploadBatchResource($batch),
        ]);
    }

    /**
     * List all bulk upload batches
     * 
     * @group Bulk Upload (B2B)
     */
    public function index(Request $request): JsonResponse
    {
        $batches = BulkUploadBatch::where('user_id', auth()->id())
            ->with('errors')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => BulkUploadBatchResource::collection($batches),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
            ],
        ]);
    }

    /**
     * Download error report for batch
     * 
     * @group Bulk Upload (B2B)
     */
    public function downloadErrorReport(BulkUploadBatch $batch)
    {
        $this->authorize('view', $batch);

        try {
            $report = $this->bulkUploadService->generateErrorReport($batch);

            return $report->download("batch-{$batch->batch_reference}-errors.xlsx");

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate error report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed records in batch
     * 
     * @group Bulk Upload (B2B)
     */
    public function retry(BulkUploadBatch $batch): JsonResponse
    {
        $this->authorize('update', $batch);

        if ($batch->status !== 'failed' && $batch->failed_records === 0) {
            return response()->json([
                'message' => 'No failed records to retry',
            ], 422);
        }

        try {
            $newBatch = $this->bulkUploadService->retryFailedRecords($batch);

            return response()->json([
                'message' => 'Retry started successfully',
                'data' => new BulkUploadBatchResource($newBatch),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retry batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample template
     * 
     * @group Bulk Upload (B2B)
     */
    public function downloadTemplate(): JsonResponse
    {
        try {
            $templatePath = $this->bulkUploadService->generateTemplate();

            return response()->download($templatePath, 'invoice-bulk-upload-template.xlsx');

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}