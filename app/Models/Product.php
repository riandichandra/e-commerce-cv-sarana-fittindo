<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug', 
        'description', 
        'price', 
        'stock',
        'weight',
        'thickness',
        'dimensions',
        'specifications',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'specifications' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relasi ke Category
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    // Relasi ke Brand
    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    // Relasi ke Images
    public function images()
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

    // Generate slug otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = \Str::slug($product->name);
        });

        static::updating(function ($product) {
            $product->slug = \Str::slug($product->name);
        });
    }
}