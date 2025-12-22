<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'tax_type',
        'taxable_amount',
        'tax_rate',
        'tax_amount',
        'tax_exempted_amount',
    ];

    protected $casts = [
        'taxable_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_exempted_amount' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ==================== ACCESSORS ====================
    
    public function getTaxTypeNameAttribute(): string
    {
        return match($this->tax_type) {
            '01' => 'Sales Tax',
            '02' => 'Service Tax',
            '03' => 'Tourism Tax',
            '04' => 'HRD Levy',
            '05' => 'Withholding Tax',
            '06' => 'Others',
            default => 'Unknown',
        };
    }
}
