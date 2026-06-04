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
        'shipping_cost_status',
        'shipping_cost_confirmed_at',
        'shipping_cost_confirmed_by',
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
        'shipping_cost_confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $enumStatuses = [
        'menunggu_konfirmasi_ongkir',
        'belum_dibayar',
        'menunggu_verifikasi_pembayaran',
        'pembayaran_dikonfirmasi',
        'diproses',
        'dikirim',
        'selesai',
        'dibatalkan'
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

    public function shippingCostConfirmedBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'shipping_cost_confirmed_by');
    }

    public function statusHistory() : HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu_konfirmasi_ongkir' => 'Menunggu Konfirmasi Ongkir',
            'belum_dibayar' => 'Belum Dibayar',
            'menunggu_verifikasi_pembayaran' => 'Menunggu Verifikasi Admin',
            'pembayaran_dikonfirmasi' => 'Pembayaran Dikonfirmasi',
            'diproses' => 'Diproses',
            'dikirim' => 'Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'menunggu_konfirmasi_ongkir' => 'bg-orange-100 text-orange-800',
            'belum_dibayar' => $this->payment?->status === 'ditolak'
                ? 'bg-red-100 text-red-800'
                : 'bg-yellow-100 text-yellow-800',
            'menunggu_verifikasi_pembayaran' => 'bg-blue-100 text-blue-800',
            'pembayaran_dikonfirmasi' => 'bg-green-100 text-green-800',
            'diproses' => 'bg-indigo-100 text-indigo-800',
            'dikirim' => 'bg-sky-100 text-sky-800',
            'selesai' => 'bg-emerald-100 text-emerald-800',
            'dibatalkan' => 'bg-red-100 text-red-800',
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

    public function isWaitingForShippingCost(): bool
    {
        return $this->status === 'menunggu_konfirmasi_ongkir'
            || $this->shipping_cost_status === 'waiting_admin';
    }

    public function hasFinalShippingCost(): bool
    {
        return ! $this->isWaitingForShippingCost();
    }

    public static function isPalembangShippingCity(?string $city): bool
    {
        if (! $city) {
            return false;
        }

        $normalized = str($city)
            ->lower()
            ->replace(['kota ', 'kabupaten ', 'kab. '], '')
            ->squish()
            ->toString();

        return $normalized === 'palembang';
    }
}
