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
        'http_method',
        'http_status_code',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'response_time_ms',
        'error_message',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    public function submission()
    {
        return $this->belongsTo(MyinvoisSubmission::class, 'submission_id');
    }
}
