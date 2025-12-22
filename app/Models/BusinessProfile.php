<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_registration_number',
        'tax_identification_number',
        'business_type',
        'business_address',
        'city',
        'state',
        'postal_code',
        'country',
        'contact_email',
        'contact_phone',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================
    
    public function getFormattedAddressAttribute(): string
    {
        return sprintf(
            "%s, %s %s, %s",
            $this->business_address,
            $this->postal_code,
            $this->city,
            $this->state
        );
    }
}
