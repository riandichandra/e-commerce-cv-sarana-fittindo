<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'address_id',
        'address',
        'courier',
        'tracking_number',
        'status',
        'estimated_arrival',
        'shipped_at',
        'delivered_at',
        'received_by',
        'shipping_notes',
    ];

    protected $dates = [
        'estimated_arrival',
        'shipped_at',
        'delivered_at',
    ];

    protected $enumStatuses = ['dikemas', 'dikirim', 'dalam_perjalanan', 'terkirim'];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'dikemas' => 'Dikemas',
            'dikirim' => 'Dikirim',
            'dalam_perjalanan' => 'Dalam Perjalanan',
            'terkirim' => 'Terkirim',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }
}
