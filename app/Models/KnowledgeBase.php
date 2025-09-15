<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $table = 'knowledge_base';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'content',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

        /**
         * Get the tenant that owns the knowledge base entry.
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
}
