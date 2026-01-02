<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_name',
        'template_code',
        'content_html',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
