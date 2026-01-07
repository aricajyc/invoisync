<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_number'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==================== RELATIONSHIPS ====================
    
    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bulkUploadBatches()
    {
        return $this->hasMany(BulkUploadBatch::class);
    }

    public function submissionAnalytics()
    {
        return $this->hasMany(SubmissionAnalytic::class);
    }

    public function notifications()
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function mobileDevices()
    {
        return $this->hasMany(MobileDevice::class);
    }

    public function invoiceTemplates()
    {
        return $this->hasMany(InvoiceTemplate::class);
    }

    // ==================== SCOPES ====================
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeB2B($query)
    {
        return $query->where('user_type', 'B2B');
    }

    public function scopeB2C($query)
    {
        return $query->where('user_type', 'B2C');
    }

    // ==================== ACCESSORS ====================
    
    public function getIsB2BAttribute(): bool
    {
        return $this->user_type === 'B2B';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
