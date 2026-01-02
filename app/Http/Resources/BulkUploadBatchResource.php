<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulkUploadBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_reference' => $this->batch_reference,
            'original_filename' => $this->original_filename,
            'status' => $this->status,
            
            'statistics' => [
                'total_records' => $this->total_records,
                'processed_records' => $this->processed_records,
                'successful_records' => $this->successful_records,
                'failed_records' => $this->failed_records,
                'success_rate' => $this->success_rate,
            ],
            
            'timing' => [
                'upload_date' => $this->upload_date->toIso8601String(),
                'processing_started_at' => $this->processing_started_at?->toIso8601String(),
                'processing_completed_at' => $this->processing_completed_at?->toIso8601String(),
                'processing_time_seconds' => $this->processing_time,
            ],
            
            'errors' => BulkUploadErrorResource::collection($this->whenLoaded('errors')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}