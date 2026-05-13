<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_registration_number',
        'tax_identification_number',
        'sst_registration_number',
        'tourism_tax_registration_number',
        'msic_code',
        'business_activity_description',
        'address_line_0',
        'address_line_1',
        'address_line_2',
        'postal_zone',
        'city',
        'state',
        'country',
        'contact_email',
        'contact_phone',
        'myinvois_client_id',
        'myinvois_client_secret',
    ];

    protected $casts = [
        'myinvois_client_secret' => 'encrypted',
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
            "%s, %s, %s, %s %s, %s",
            $this->address_line_0,
            $this->address_line_1,
            $this->address_line_2,
            $this->postal_zone,
            $this->city,
            $this->state
        );
    }
}
