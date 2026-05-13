<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Relasi ke Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($role)
    {
        return $this->role && $this->role->name === $role;
    }

    // Cek apakah user adalah admin
    public function isAdmin()
    {
        return $this->role_id === Role::where('name', 'admin')->first()->id;
    }

    // Redirect berdasarkan role setelah login
    public function redirectBasedOnRole()
    {
        return match ($this->role->name) {
            'admin' => '/admin/dashboard',
            'marketing' => '/marketing/dashboard',
            'gm' => '/gm/dashboard',
            'direktur' => '/direktur/dashboard',
            default => '/',
        };
    }
}