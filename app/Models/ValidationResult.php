<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ValidationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'rule_id',
        'result_type',
        'validation_message',
        'suggested_fix',
        'is_resolved',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'is_resolved' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function rule()
    {
        return $this->belongsTo(ValidationRule::class);
    }

    // ==================== SCOPES ====================
    
    public function scopeFailed($query)
    {
        return $query->where('result_type', 'fail');
    }

    public function scopeWarnings($query)
    {
        return $query->where('result_type', 'warning');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
}
