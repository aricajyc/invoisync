<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BulkUploadBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_reference',
        'original_filename',
        'file_path',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'status',
        'upload_date',
        'processing_started_at',
        'processing_completed_at',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errors()
    {
        return $this->hasMany(BulkUploadError::class, 'batch_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'bulk_upload_batch_id');
    }

    // ==================== SCOPES ====================
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ==================== ACCESSORS ====================
    
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }
        
        return ($this->successful_records / $this->total_records) * 100;
    }

    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->processing_started_at || !$this->processing_completed_at) {
            return null;
        }
        
        return $this->processing_started_at->diffInSeconds($this->processing_completed_at);
    }
}
