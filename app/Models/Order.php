<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const DUMMY_ORDER_PREFIX = 'SFDM';

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'discount_amount',
        'promotion_id',
        'promotion_name',
        'promotion_type',
        'promotion_value',
        'shipping_cost',
        'shipping_cost_status',
        'shipping_cost_source',
        'shipping_origin_district_id',
        'shipping_destination_district_id',
        'shipping_weight_gram',
        'shipping_courier_code',
        'shipping_courier_name',
        'shipping_service',
        'shipping_service_description',
        'shipping_etd',
        'shipping_rate_snapshot',
        'shipping_cost_confirmed_at',
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
        'shipped_at',
        'completed_at',
        'auto_completed_at',
        'completion_source',
        'completion_notes',
        'stock_restored_at',
        'stock_restored_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'promotion_value' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_rate_snapshot' => 'array',
        'shipping_cost_confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'auto_completed_at' => 'datetime',
        'stock_restored_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNonDummy($query)
    {
        return $query->where('order_number', 'not like', self::DUMMY_ORDER_PREFIX.'%');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function stockRestoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stock_restored_by');
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

        return 'SF'.$date.str_pad($sequence, 4, '0', STR_PAD_LEFT);
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

    public function isEligibleForAutoCompletion(): bool
    {
        return $this->status === 'dikirim'
            && $this->shipped_at
            && $this->shipped_at->lte(now()->subDays(3));
    }

    public function getShippingServiceLabelAttribute(): ?string
    {
        if (! $this->shipping_courier_name && ! $this->shipping_service) {
            return null;
        }

        return collect([
            $this->shipping_courier_name,
            $this->shipping_service,
        ])->filter()->join(' - ');
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
