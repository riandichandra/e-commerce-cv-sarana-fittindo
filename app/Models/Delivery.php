<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
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

    protected $enumStatuses = ['packed', 'shipped', 'in_transit', 'delivered'];

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address', 'id');
    }
}