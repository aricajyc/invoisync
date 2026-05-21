<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MyinvoisSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'submission_reference',
        'submission_type',
        'request_payload',
        'response_payload',
        'myinvois_uid',
        'qr_code_url',
        'status',
        'rejection_reason',
        'retry_count',
        'submitted_at',
        'response_received_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'submitted_at' => 'datetime',
        'response_received_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class, 'submission_id');
    }

    // ==================== SCOPES ====================
    
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->whereIn('status', ['rejected', 'invalid']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ==================== ACCESSORS ====================
    
    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->submitted_at || !$this->response_received_at) {
            return null;
        }
        
        return $this->submitted_at->diffInMilliseconds($this->response_received_at);
    }
}
