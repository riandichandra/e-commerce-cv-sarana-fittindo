<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'payment_method_id',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_province',
        'shipping_city',
        'shipping_district',
        'shipping_village',
        'shipping_postal_code',
        'notes',
        'cancelled_by',
        'cancellation_reason',
        'cancelled_at',
        'received_image',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    protected $enumStatuses = [
        'pending_payment',
        'waiting_payment_confirmation',
        'payment_confirmed',
        'processing',
        'shipped',
        'completed',
        'cancelled'
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items() : HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethod() : BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payment() : HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery() : HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function statusHistory() : HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => 'Belum Dibayar',
            'waiting_payment_confirmation' => 'Menunggu Verifikasi Admin',
            'payment_confirmed' => 'Pembayaran Dikonfirmasi',
            'processing' => 'Diproses',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => $this->payment?->status === 'rejected'
                ? 'bg-red-100 text-red-800'
                : 'bg-yellow-100 text-yellow-800',
            'waiting_payment_confirmation' => 'bg-blue-100 text-blue-800',
            'payment_confirmed' => 'bg-green-100 text-green-800',
            'processing' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-sky-100 text-sky-800',
            'completed' => 'bg-emerald-100 text-emerald-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', today())
        ->orderBy('id', 'desc')
        ->first();

        $sequence = $lastOrder ? (intval(substr($lastOrder->order_number, -4)) + 1) : 1;
        
        return 'SF' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
