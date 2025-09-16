<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Add app_key as UUID field
            $table->uuid('app_key')->nullable()->after('id');
            
            // Add unique index on app_key
            $table->unique('app_key');
        });
        
        // Generate app_keys for existing tenants
        $tenants = DB::table('tenants')->whereNull('app_key')->get();
        foreach ($tenants as $tenant) {
            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['app_key' => (string) Str::uuid()]);
        }
        
        // Make app_key not nullable after generating for existing records
        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('app_key')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['app_key']);
            $table->dropColumn('app_key');
        });
    }
};