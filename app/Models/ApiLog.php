<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'endpoint',
        'method',
        'status_code',
        'request_body',
        'response_body',
        'ip_address',
        'duration_ms',
    ];

    // ==================== RELATIONSHIPS ====================

    public function submission()
    {
        return $this->belongsTo(MyinvoisSubmission::class, 'submission_id');
    }
}
