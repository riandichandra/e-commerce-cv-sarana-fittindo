<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'province_name',
        'city_name',
        'district_name',
        'village_name',
        'region_source',
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

    public function getProvinceDisplayNameAttribute(): ?string
    {
        return $this->province_name;
    }

    public function getCityDisplayNameAttribute(): ?string
    {
        return $this->city_name;
    }

    public function getDistrictDisplayNameAttribute(): ?string
    {
        return $this->district_name;
    }

    public function getVillageDisplayNameAttribute(): ?string
    {
        return $this->village_name;
    }

    public function getRegionSummaryAttribute(): string
    {
        return collect([
            $this->village_display_name,
            $this->district_display_name,
            $this->city_display_name,
            $this->province_display_name,
            $this->postal_code,
        ])->filter()->join(', ');
    }
}
