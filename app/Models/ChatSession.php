<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatSession extends Model
{
    use HasFactory;

    protected $table = 'chat_sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

        /**
         * Get the tenant that owns the chat session.
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
