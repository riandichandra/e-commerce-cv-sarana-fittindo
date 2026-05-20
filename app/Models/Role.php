<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
    ];

    // Relasi ke User
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
