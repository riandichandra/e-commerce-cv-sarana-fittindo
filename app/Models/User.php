<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    protected $guard_name = 'web';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    // Cek apakah user adalah admin
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    // Redirect berdasarkan role setelah login
    public function redirectBasedOnRole(): string
    {
        if ($this->hasRole('admin')) {
            return '/admin/dashboard';
        }

        if ($this->hasRole('marketing')) {
            return '/marketing/dashboard';
        }

        if ($this->hasRole('gm')) {
            return '/gm/dashboard';
        }

        if ($this->hasRole('direktur')) {
            return '/direktur/dashboard';
        }

        if ($this->hasRole('pelanggan')) {
            return '/pelanggan/dashboard';
        }

        return '/';
    }
}
