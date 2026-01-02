<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubmissionAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'submission_month',
        'total_submissions',
        'accepted_submissions',
        'rejected_submissions',
        'validation_errors',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
