<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BulkUploadError extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'line_number',
        'raw_data',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    public function bulkUploadBatch()
    {
        return $this->belongsTo(BulkUploadBatch::class, 'batch_id');
    }
}
