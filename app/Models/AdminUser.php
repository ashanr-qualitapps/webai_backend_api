<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passport\HasApiTokens;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids;

    protected $table = 'admin_users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'password_hash',
        'full_name',
        'permissions',
        'metadata',
        'updated_by',
        'last_login',
        'is_active',
        'last_updated',
    ];

    protected $casts = [
        'permissions' => 'array',
        'metadata' => 'array',
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_updated' => 'datetime',
        'is_active' => 'boolean',
    ];
}
