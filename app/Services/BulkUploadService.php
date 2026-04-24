<?php

namespace App\Services;

use App\Models\BulkUploadBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
                    $nestedData = $this->formatFlatToNested($invoiceData);
                    
                    // Create invoice
                    $invoice = $this->invoiceService->createInvoice($nestedData, $user);
                    
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
     * Parse spreadsheet for frontend preview (In-Browser Approach)
     */
    public function parseForPreview(UploadedFile $file): array
    {
        // Save file locally to avoid temp directory permission issues in Windows/Laragon.
        // Maintaining the original extension helps IOFactory correctly guess the file type.
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';
        $tempDir = 'temp_uploads';
        $tempFileName = uniqid('preview_') . '.' . $extension;
        
        // We use file_get_contents() on the raw pathname because getRealPath() 
        // can return false on Windows/Laragon for system temp files, which breaks storeAs().
        Storage::disk('local')->put($tempDir . '/' . $tempFileName, file_get_contents($file->getPathname()));
        $filePath = Storage::disk('local')->path($tempDir . '/' . $tempFileName);
        
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Calculate boundaries to prevent processing millions of empty but formatted cells
            $highestRow = $worksheet->getHighestDataRow();
            $highestColumn = $worksheet->getHighestDataColumn();
            $range = 'A1:' . $highestColumn . $highestRow;
            
            // Extract only the range with actual data
            $rows = $worksheet->rangeToArray($range, null, true, true, false);
            
            if (empty($rows)) {
                return [];
            }
            
            $headers = array_shift($rows);
            $parsedRows = [];
            
            foreach ($rows as $index => $row) {
                $isEmpty = empty(array_filter($row, fn($val) => $val !== null && trim($val) !== ''));
                if ($isEmpty) continue;
                
                // Normalize row length to match headers so array_combine doesn't fail
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), null);
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                
                try {
                    $invoiceData = $this->mapRowToInvoiceData($headers, $row);
                    
                    $validator = Validator::make($invoiceData, [
                        'invoice_number' => 'required',
                        'invoice_date_time' => 'required',
                        'invoice_type' => 'required|in:01,02,03,04,11,12,13,14',
                        'currency_code' => 'required|in:MYR',
                        'total_excluding_tax' => 'required|numeric',
                        'total_tax_amount' => 'required|numeric',
                        'total_including_tax' => 'required|numeric',
                        'buyer_name' => 'required',
                        'buyer_tin' => 'required',
                        'buyer_registration_type' => 'required|in:BRN,NRIC,PASSPORT,ARMY',
                        'buyer_registration_number' => 'required',
                        'buyer_contact_number' => ['required', 'regex:/^\+60.*/'],
                        'item_classification_code' => 'required',
                        'item_product_service_description' => 'required',
                        'item_quantity' => 'required|numeric',
                        'item_unit_price' => 'required|numeric|min:0',
                        'item_tax_type' => 'required',
                        'item_tax_rate' => 'required|numeric|in:0,6,8',
                    ], [
                        'buyer_contact_number.regex' => 'Contact number must start with +60',
                        'item_unit_price.min' => 'Unit price must be a positive value',
                    ]);

                    $validator->after(function ($validator) use ($invoiceData) {
                        $totalExc = (float)($invoiceData['total_excluding_tax'] ?? 0);
                        $tax = (float)($invoiceData['total_tax_amount'] ?? 0);
                        $totalInc = (float)($invoiceData['total_including_tax'] ?? 0);
                        
                        if (abs(($totalExc + $tax) - $totalInc) > 0.01) {
                            $validator->errors()->add('total_including_tax', 'Total must equal excluding tax + tax amount');
                        }
                    });

                    $validationErrors = [];
                    if ($validator->fails()) {
                        foreach ($validator->errors()->toArray() as $field => $messages) {
                            $validationErrors[$field] = implode(', ', $messages);
                        }
                    }

                    $parsedRows[] = [
                        'id' => uniqid('row_'),
                        'data' => $invoiceData,
                        'is_valid' => empty($validationErrors),
                        'errors' => $validationErrors
                    ];
                } catch (\Exception $e) {
                    $parsedRows[] = [
                        'id' => uniqid('row_'),
                        'data' => array_combine($headers, $row),
                        'is_valid' => false,
                        'errors' => ['mapping' => $e->getMessage()]
                    ];
                }
            }
            
            return $parsedRows;
        } finally {
            if (Storage::disk('local')->exists($tempDir . '/' . $tempFileName)) {
                Storage::disk('local')->delete($tempDir . '/' . $tempFileName);
            }
        }
    }
    
    /**
     * Map spreadsheet row to invoice data
     */
    protected function mapRowToInvoiceData(array $headers, array $row): array
    {
        // Trim headers to avoid mismatch from accidental trailing spaces in Excel
        $headers = array_map(fn($h) => trim((string)$h), $headers);
        $data = array_combine($headers, $row);
        
        $invoiceDate = $data['invoiceDate'] ?? now();
        if (is_numeric($invoiceDate)) {
            $invoiceDate = Date::excelToDateTimeObject($invoiceDate)->format('Y-m-d H:i:s');
        }

        // Map columns to invoice fields safely
        return [
            'invoice_number' => $data['invoiceCodeNumber'] ?? null,
            'invoice_date_time' => $invoiceDate,
            'invoice_type' => $data['invoiceTypeCode'] ?? null,
            'original_einvoice_reference' => $data['originalInvoiceCodeNumber'] ?? null,
            
            //Currency
            'currency_code' => $data['invoiceCurrencyCode'] ?? null,
            'currency_exchange_rate' => $data['exchangeRate'] ?? null,

            //Total
            'total_excluding_tax' => round((float)($data['totalExcludingTax'] ?? 0), 2),
            'total_tax_amount' => round((float)($data['totalTaxAmount'] ?? 0), 2),
            'total_including_tax' => round((float)($data['totalIncludingTax'] ?? 0), 2),

            //Buyer
            'buyer_tin' => $data['buyerTin'] ?? null,
            'buyer_registration_type' => $data['buyerIdType'] ?? null,
            'buyer_registration_number' => $data['buyerIdValue'] ?? 'NA',
            'buyer_sst_registration_number' => $data['buyerSst'] ?? null,
            'buyer_name' => $data['buyerName'] ?? 'NA',
            'buyer_contact_number' => $data['buyerContactNumber'] ?? null,
            'buyer_country' => $data['buyerCountry'] ?? 'MYS',
            'buyer_state' => $data['buyerState'] ?? 'NA',
            'buyer_city' => $data['buyerCityName'] ?? 'NA',
            'buyer_address_line0' => $data['buyerAddressLine0'] ?? 'NA',
            'buyer_address_line1' => $data['buyerAddressLine1'] ?? null,
            'buyer_address_line2' => $data['buyerAddressLine2'] ?? null,
            'buyer_postal_code' => $data['buyerPostalCode'] ?? null,
            'buyer_email' => $data['buyerEmail'] ?? null,
            
            // Supplier
            'supplier_tin' => $data['supplierTin'] ?? null,
            'supplier_registration_type' => $data['supplierIdType'] ?? null,
            'supplier_registration_number' => $data['supplierIdValue'] ?? null,
            'supplier_name' => $data['supplierName'] ?? null,
            'supplier_contact_number' => $data['supplierContactNumber'] ?? null,
            'supplier_country' => $data['supplierCountry'] ?? 'MYS',
            'supplier_state' => $data['supplierState'],
            'supplier_city' => $data['supplierCityName'],
            'supplier_address_line0' => $data['supplierAddressLine0'],
            'supplier_address_line1' => $data['supplierAddressLine1'] ?? null,
            'supplier_address_line2' => $data['supplierAddressLine2'] ?? null,
            'supplier_postal_code' => $data['supplierPostalZone'],
            'supplier_email' => $data['supplierEmail'] ?? null,
            'supplier_msic_code' => $data['supplierIndustryCode'] ?? null,
            'supplier_sst_registration_number' => $data['supplierSst'] ?? null,
            'supplier_business_activity_description' => $data['supplierBusinessActivityDescription'] ?? null,
            
            // Line items
            'item_product_service_description' => $data['itemName'],
            'item_classification_code' => $data['itemClassificationCode'] ?? '022',
            'item_unit_of_measure' => $data['measurement'],
            'item_quantity' => round((float)($data['quantity'] ?? 1), 2),
            'item_unit_price' => round((float)($data['unitPrice'] ?? 0), 2),
            'item_subtotal' => round((float)($data['itemTotalExcludingTax'] ?? 0), 2),
            'item_total_excluding_tax_per_line' => round((float)($data['itemTotalExcludingTax'] ?? 0), 2),
            'item_total_including_tax_per_line' => round((float)($data['itemSubtotal'] ?? 0), 2),
            'item_tax_amount' => round((float)($data['itemTaxAmount'] ?? 0), 2),
            'item_tax_type' => $data['taxType'] ?? '06',
            'item_tax_rate' => $data['taxRate'] ?? 0,
        ];
    }
    
    /**
     * Reconstruct the line_items nested array for the invoice creation payload
     */
    public function formatFlatToNested(array $flatData): array
    {
        $nested = $flatData;
        $nested['line_items'] = [
            [
                'product_service_description' => $flatData['item_product_service_description'] ?? '',
                'classification_code' => $flatData['item_classification_code'] ?? '022',
                'unit_of_measure' => $flatData['item_unit_of_measure'] ?? '',
                'quantity' => $flatData['item_quantity'] ?? 1,
                'unit_price' => $flatData['item_unit_price'] ?? 0,
                'subtotal' => $flatData['item_subtotal'] ?? 0,
                'total_excluding_tax_per_line' => $flatData['item_total_excluding_tax_per_line'] ?? 0,
                'total_including_tax_per_line' => $flatData['item_total_including_tax_per_line'] ?? 0,
                'tax_amount' => $flatData['item_tax_amount'] ?? 0,
                'tax_type' => $flatData['item_tax_type'] ?? '06',
                'tax_rate' => $flatData['item_tax_rate'] ?? 0,
            ]
        ];
        
        // Remove flat keys to clean payload
        foreach ($flatData as $key => $val) {
            if (str_starts_with($key, 'item_')) {
                unset($nested[$key]);
            }
        }
        
        return $nested;
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