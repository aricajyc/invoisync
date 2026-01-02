<?php

namespace App\Services;

use App\Models\BulkUploadBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class BulkUploadService
{
    protected InvoiceService $invoiceService;
    
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }
    
    /**
     * Process bulk upload file
     */
    public function processBulkUpload(UploadedFile $file, User $user, ?string $batchReference = null): BulkUploadBatch
    {
        // Generate batch reference if not provided
        if (!$batchReference) {
            $batchReference = 'BATCH-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        }
        
        // Store file
        $filePath = $file->store('bulk-uploads', 'local');
        
        // Create batch record
        $batch = BulkUploadBatch::create([
            'user_id' => $user->id,
            'batch_reference' => $batchReference,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'upload_date' => now(),
        ]);
        
        // Process asynchronously
        dispatch(function () use ($batch, $user) {
            $this->processFile($batch, $user);
        })->afterResponse();
        
        return $batch;
    }
    
    /**
     * Process uploaded file
     */
    protected function processFile(BulkUploadBatch $batch, User $user): void
    {
        $batch->update([
            'status' => 'processing',
            'processing_started_at' => now(),
        ]);
        
        try {
            $filePath = Storage::path($batch->file_path);
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row
            $headers = array_shift($rows);
            
            $batch->update(['total_records' => count($rows)]);
            
            $successful = 0;
            $failed = 0;
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because header is row 1 and array is 0-indexed
                
                try {
                    $invoiceData = $this->mapRowToInvoiceData($headers, $row);
                    
                    // Create invoice
                    $invoice = $this->invoiceService->createInvoice($invoiceData, $user);
                    
                    $successful++;
                    
                } catch (\Exception $e) {
                    $failed++;
                    
                    // Log error
                    $batch->errors()->create([
                        'row_number' => $rowNumber,
                        'field_name' => null,
                        'error_type' => 'processing_error',
                        'error_message' => $e->getMessage(),
                        'severity' => 'critical',
                    ]);
                    
                    Log::error('Bulk upload row failed', [
                        'batch_id' => $batch->id,
                        'row_number' => $rowNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                // Update progress
                $batch->update([
                    'processed_records' => $successful + $failed,
                    'successful_records' => $successful,
                    'failed_records' => $failed,
                ]);
            }
            
            $batch->update([
                'status' => 'completed',
                'processing_completed_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'processing_completed_at' => now(),
            ]);
            
            Log::error('Bulk upload batch failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Map spreadsheet row to invoice data
     */
    protected function mapRowToInvoiceData(array $headers, array $row): array
    {
        $data = array_combine($headers, $row);
        
        // Map columns to invoice fields
        return [
            'invoice_type' => $data['Invoice Type'] ?? '01',
            'invoice_date_time' => $data['Invoice Date'] ?? now(),
            
            // Supplier
            'supplier_name' => $data['Supplier Name'],
            'supplier_tin' => $data['Supplier TIN'],
            'supplier_email' => $data['Supplier Email'],
            // ... map more fields
            
            // Buyer
            'buyer_name' => $data['Buyer Name'],
            'buyer_tin' => $data['Buyer TIN'] ?? 'EI00000000010',
            // ... map more fields
            
            // Line items
            'line_items' => [
                [
                    'classification_code' => $data['Classification Code'],
                    'product_service_description' => $data['Description'],
                    'quantity' => $data['Quantity'],
                    'unit_price' => $data['Unit Price'],
                    // ... map more fields
                ],
            ],
        ];
    }
    
    /**
     * Generate bulk upload template
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = [
            'Invoice Type', 'Invoice Date', 'Supplier Name', 'Supplier TIN',
            'Supplier Email', 'Buyer Name', 'Buyer TIN', 'Classification Code',
            'Description', 'Quantity', 'Unit Price', 'Tax Type', 'Tax Rate'
        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Add sample row
        $sampleData = [
            '01', now()->toDateString(), 'My Company Sdn Bhd', 'C1234567890123456789',
            'info@company.com', 'Customer Sdn Bhd', 'C9876543210987654321', '001',
            'Web Development Services', '1', '10000.00', '02', '6.00'
        ];
        
        $sheet->fromArray($sampleData, null, 'A2');
        
        // Save to file
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempPath = storage_path('app/temp/invoice-template.xlsx');
        $writer->save($tempPath);
        
        return $tempPath;
    }
}