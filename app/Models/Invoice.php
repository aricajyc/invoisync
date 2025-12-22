<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'invoice_number',
        'invoice_type',
        'invoice_date_time',
        'original_einvoice_reference',
        'frequency_of_billing',
        'billing_period_start_date',
        'billing_period_end_date',
        
        // Supplier Details
        'supplier_name',
        'supplier_tin',
        'supplier_registration_number',
        'supplier_sst_registration_number',
        'supplier_tourism_tax_number',
        'supplier_email',
        'supplier_msic_code',
        'supplier_business_activity_description',
        'supplier_address_line1',
        'supplier_address_line2',
        'supplier_address_line3',
        'supplier_postal_code',
        'supplier_city',
        'supplier_state',
        'supplier_country',
        'supplier_contact_number',
        
        // Buyer Details
        'buyer_name',
        'buyer_tin',
        'buyer_registration_number',
        'buyer_sst_registration_number',
        'buyer_email',
        'buyer_address_line1',
        'buyer_address_line2',
        'buyer_address_line3',
        'buyer_postal_code',
        'buyer_city',
        'buyer_state',
        'buyer_country',
        'buyer_contact_number',
        
        // Payment Information
        'payment_mode',
        'payment_terms',
        'payment_amount',
        'payment_date',
        'payment_reference_number',
        'bank_account_number',
        
        // Shipping Information
        'shipping_recipient_name',
        'shipping_recipient_tin',
        'shipping_recipient_registration',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_address_line3',
        'shipping_postal_code',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        
        // Other References
        'bill_reference_number',
        
        // Customs Information
        'customs_form_reference',
        'incoterms',
        'free_trade_agreement_info',
        'authorisation_number_for_certified_exporter',
        
        // Totals
        'currency_code',
        'currency_exchange_rate',
        'total_excluding_tax',
        'total_including_tax',
        'total_payable_amount',
        'total_discount_value',
        'total_fee_charge_amount',
        'total_tax_amount',
        
        // IRBM Fields
        'status',
        'myinvois_uid',
        'qr_code_data',
        'irbm_unique_identifier',
        'validation_date_time',
        'digital_signature',
        'submitted_at',
    ];

    protected $casts = [
        'invoice_date_time' => 'datetime',
        'billing_period_start_date' => 'date',
        'billing_period_end_date' => 'date',
        'payment_date' => 'date',
        'validation_date_time' => 'datetime',
        'submitted_at' => 'datetime',
        'currency_exchange_rate' => 'decimal:6',
        'total_excluding_tax' => 'decimal:2',
        'total_including_tax' => 'decimal:2',
        'total_payable_amount' => 'decimal:2',
        'total_discount_value' => 'decimal:2',
        'total_fee_charge_amount' => 'decimal:2',
        'total_tax_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function taxSummaries()
    {
        return $this->hasMany(TaxSummary::class);
    }

    public function validationResults()
    {
        return $this->hasMany(ValidationResult::class);
    }

    public function anomalyDetections()
    {
        return $this->hasMany(AnomalyDetection::class);
    }

    public function myinvoisSubmission()
    {
        return $this->hasOne(MyinvoisSubmission::class);
    }

    // ==================== SCOPES ====================
    
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByInvoiceType($query, string $type)
    {
        return $query->where('invoice_type', $type);
    }

    public function scopeInvoices($query)
    {
        return $query->where('invoice_type', '01');
    }

    public function scopeCreditNotes($query)
    {
        return $query->where('invoice_type', '02');
    }

    public function scopeDebitNotes($query)
    {
        return $query->where('invoice_type', '03');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date_time', [$startDate, $endDate]);
    }

    // ==================== ACCESSORS ====================
    
    public function getInvoiceTypeNameAttribute(): string
    {
        return match($this->invoice_type) {
            '01' => 'Invoice',
            '02' => 'Credit Note',
            '03' => 'Debit Note',
            '04' => 'Refund Note',
            default => 'Unknown',
        };
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getIsValidatedAttribute(): bool
    {
        return $this->status === 'validated';
    }

    public function getIsSubmittedAttribute(): bool
    {
        return in_array($this->status, ['submitted', 'approved']);
    }

    public function getSupplierFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->supplier_address_line1,
            $this->supplier_address_line2,
            $this->supplier_address_line3,
            $this->supplier_postal_code . ' ' . $this->supplier_city,
            $this->supplier_state,
            $this->supplier_country,
        ]);
        
        return implode(', ', $parts);
    }

    public function getBuyerFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->buyer_address_line1,
            $this->buyer_address_line2,
            $this->buyer_address_line3,
            $this->buyer_postal_code . ' ' . $this->buyer_city,
            $this->buyer_state,
            $this->buyer_country,
        ]);
        
        return implode(', ', $parts);
    }

    public function getHasShippingInfoAttribute(): bool
    {
        return !empty($this->shipping_recipient_name);
    }

    public function getHasCustomsInfoAttribute(): bool
    {
        return !empty($this->customs_form_reference);
    }

    // ==================== MUTATORS ====================
    
    public function setInvoiceDateTimeAttribute($value)
    {
        // Ensure datetime format
        $this->attributes['invoice_date_time'] = date('Y-m-d H:i:s', strtotime($value));
    }

    // ==================== METHODS ====================
    
    public function calculateTotals(): void
    {
        $this->total_excluding_tax = $this->lineItems->sum('total_excluding_tax_per_line');
        $this->total_tax_amount = $this->lineItems->sum('tax_amount');
        $this->total_including_tax = $this->total_excluding_tax + $this->total_tax_amount;
        $this->total_payable_amount = $this->total_including_tax - $this->total_discount_value + $this->total_fee_charge_amount;
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = $this->invoice_type === '01' ? 'INV' : 
                  ($this->invoice_type === '02' ? 'CN' : 
                  ($this->invoice_type === '03' ? 'DN' : 'RN'));
        
        $date = now()->format('Ymd');
        $lastInvoice = static::whereDate('created_at', now()->toDateString())
            ->where('invoice_type', $this->invoice_type)
            ->latest()
            ->first();
        
        $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -6)) + 1) : 1;
        
        return sprintf('%s-%s-%06d', $prefix, $date, $sequence);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canSubmit(): bool
    {
        return $this->status === 'validated' && empty($this->myinvois_uid);
    }

    public function canCancel(): bool
    {
        if (!$this->validation_date_time) {
            return false;
        }
        
        $hoursSinceValidation = $this->validation_date_time->diffInHours(now());
        return $hoursSinceValidation <= 72 && $this->status !== 'cancelled';
    }
}
