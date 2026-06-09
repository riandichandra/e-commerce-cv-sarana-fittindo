<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'receiver_name',
        'receiver_phone',
        'full_address',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'postal_code',
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'address_id');
    }
}
