<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'domain',
        'app_key',
        'is_active',
        'settings',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
    
    /**
     * Boot the model and automatically generate app_key.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tenant) {
            if (empty($tenant->app_key)) {
                $tenant->app_key = (string) Str::uuid();
            }
        });
    }
    
    /**
     * Get users that belong to this tenant.
     * Note: Commented out until User model is created
     */
    // public function users(): HasMany
    // {
    //     return $this->hasMany(User::class);
    // }
    
    /**
     * Get admin users that can access this tenant.
     */
    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_tenant');
    }
    
    /**
     * Get chat sessions for this tenant.
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }
    
    /**
     * Get personas for this tenant.
     */
    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }
    
    /**
     * Get knowledge base entries for this tenant.
     */
    public function knowledgeBase(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class);
    }
    
    /**
     * Find tenant by app_key (primary method for tenant resolution).
     */
    public static function findByAppKey(string $appKey): ?self
    {
        return static::where('app_key', $appKey)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Find tenant by domain (legacy method for backward compatibility).
     */
    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)
            ->orWhere('domain', '*.' . implode('.', array_slice(explode('.', $domain), 1)))
            ->where('is_active', true)
            ->first();
    }
}
