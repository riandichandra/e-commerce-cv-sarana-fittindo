<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'tersedia';
    public const STATUS_UNAVAILABLE = 'tidak tersedia';

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'status',
        'weight',
        'thickness',
        'dimensions',
        'specifications',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'weight' => 'decimal:2',
        'specifications' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relasi ke Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    // Relasi ke Brand
    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    // Relasi ke Images
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    // Primary image
    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();
    }

    // Scope: Active products
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: Featured products
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Scope: Available products
    public function scopeAvailable($query)
    {
        return $query
            ->where('is_active', true)
            ->where('status', self::STATUS_AVAILABLE)
            ->where('stock', '>', 0);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->stock > 0 && $this->status === self::STATUS_AVAILABLE;
    }

    public function syncStatusFromStock(): void
    {
        $this->stock = max(0, (int) $this->stock);
        $this->status = $this->stock > 0 ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;
    }

    public function reduceStock(int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $this->stock = max(0, $this->stock - $quantity);
        $this->syncStatusFromStock();
    }

    public function restoreStock(int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $this->stock = (int) $this->stock + $quantity;
        $this->syncStatusFromStock();
    }

    // Generate slug and keep status in sync
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Product $product) {
            $product->stock = max(0, (int) $product->stock);
            $product->slug = Str::slug($product->name);
            $product->syncStatusFromStock();
        });
    }
}
