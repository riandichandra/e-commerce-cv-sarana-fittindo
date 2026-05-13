<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'code', 
        'description',
        'type',
        'value',
        'min_purchase',
        'max_discount', 
        'start_date', 
        'end_date', 
        'is_active',
        'banner_image',
        'banner_url',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function createdBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products() : BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class);
    }
}