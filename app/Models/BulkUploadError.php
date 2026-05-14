<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BulkUploadError extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'row_number',
        'field_name',
        'error_type',
        'error_message',
        'suggested_correction',
        'severity',
        'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function bulkUploadBatch()
    {
        return $this->belongsTo(BulkUploadBatch::class, 'batch_id');
    }
}
