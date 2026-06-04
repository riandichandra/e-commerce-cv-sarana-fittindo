<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 
        'payment_method_id', 
        'amount', 
        'proof_image',
        'transfer_date',
        'sender_name',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date',
        'verified_at' => 'datetime',
    ];

    protected $enumStatuses = ['menunggu', 'terverifikasi', 'ditolak'];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu',
            'terverifikasi' => 'Terverifikasi',
            'ditolak' => 'Ditolak',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod() : BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifiedBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
