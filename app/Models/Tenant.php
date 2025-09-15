<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'is_active',
        'settings',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
    
    /**
     * Get users that belong to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    
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
     * Find tenant by domain (exact match or wildcard).
     */
    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)
            ->orWhere('domain', '*.' . implode('.', array_slice(explode('.', $domain), 1)))
            ->where('is_active', true)
            ->first();
    }
}
