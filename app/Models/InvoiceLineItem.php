<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'line_number',
        'classification_code',
        'product_service_description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'subtotal',
        'discount_rate',
        'discount_amount',
        'tax_type',
        'tax_rate',
        'tax_amount',
        'tax_exemption_reason',
        'tax_exempted_amount',
        'charge_fee_amount',
        'country_of_origin',
        'product_tariff_code',
        'total_excluding_tax_per_line',
        'total_including_tax_per_line',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_exempted_amount' => 'decimal:2',
        'charge_fee_amount' => 'decimal:2',
        'total_excluding_tax_per_line' => 'decimal:2',
        'total_including_tax_per_line' => 'decimal:2',
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

    public function getClassificationCodeNameAttribute(): string
    {
        return match($this->classification_code) {
            '001' => 'Normal Product/Service',
            '002' => 'Product/Service subject to specific tax treatment',
            '003' => 'SST Exempt',
            '004' => 'Zero-rated',
            '005' => 'Not subject to SST',
            '006' => 'Tourism Tax',
            '007' => 'Service Tax on Digital',
            '008' => 'Others',
            default => 'Unknown',
        };
    }

    // ==================== METHODS ====================
    
    public function calculateTotals(): void
    {
        // Calculate subtotal after discount
        $discountAmount = $this->discount_amount ?? 
            ($this->subtotal * ($this->discount_rate ?? 0) / 100);
        
        $this->total_excluding_tax_per_line = $this->subtotal - $discountAmount;
        
        // Calculate tax
        $this->tax_amount = $this->total_excluding_tax_per_line * ($this->tax_rate / 100);
        
        // Calculate total including tax
        $this->total_including_tax_per_line = $this->total_excluding_tax_per_line + $this->tax_amount;
        
        // Add charge/fee if any
        if ($this->charge_fee_amount) {
            $this->total_including_tax_per_line += $this->charge_fee_amount;
        }
    }

    // ==================== BOOT ====================
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($lineItem) {
            if (is_null($lineItem->line_number)) {
                $lineItem->line_number = $lineItem->invoice->lineItems()->max('line_number') + 1;
            }
        });
        
        static::saving(function ($lineItem) {
            $lineItem->calculateTotals();
        });
    }
}
