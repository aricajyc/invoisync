<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ValidationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_code',
        'rule_name',
        'rule_description',
        'rule_type',
        'validation_expression',
        'error_message_template',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function validationResults()
    {
        return $this->hasMany(ValidationResult::class);
    }
}
