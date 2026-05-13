<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Get or create cart for user
    public static function getForUser(User $user)
    {
        return self::firstOrCreate(
            ['user_id' => $user->id]
        );
    }

    // Calculate subtotal
    public function getSubtotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });
    }

    // Get total weight
    public function getTotalWeightAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->product->weight * $item->quantity;
        });
    }

    // Get total items count
    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }
}