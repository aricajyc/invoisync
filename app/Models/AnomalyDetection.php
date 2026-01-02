<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnomalyDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'anomaly_type',
        'description',
        'severity',
        'detected_at',
        'is_resolved',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'is_resolved' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
