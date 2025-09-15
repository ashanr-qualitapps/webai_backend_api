<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Snippet extends Model
{
    use HasFactory;

    protected $table = 'snippets';
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
         * Get the tenant that owns the snippet.
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
     * Get the persona that this snippet is assigned to.
     */
    public function assignedPersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'assigned_persona_id');
    }
}
