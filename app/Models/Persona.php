<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

        /**
         * Get the tenant that owns the persona.
         */
        public function tenant()
        {
            return $this->belongsTo(Tenant::class);
        }

        /**
         * The "booted" method of the model.
         * Applies global scope for tenant_id.
         */
        protected static function booted()
        {
            static::addGlobalScope('tenant', function ($query) {
                if (app()->has('currentTenant')) {
                    $tenantId = app('currentTenant')->id;
                    $query->where('tenant_id', $tenantId);
                }
            });
        }

    /**
     * Get the profile snippet associated with this persona.
     */
    public function profileSnippet(): BelongsTo
    {
        return $this->belongsTo(Snippet::class, 'associated_profile_snippet_id');
    }

    /**
     * Get the snippets assigned to this persona.
     */
    public function assignedSnippets(): HasMany
    {
        return $this->hasMany(Snippet::class, 'assigned_persona_id');
    }

    /**
     * Scope a query to only include active personas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive personas.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Check if the persona is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the persona is inactive.
     */
    public function isInactive(): bool
    {
        return !$this->is_active;
    }
}
