<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add tenant_id columns as nullable first to avoid not-null violations
        Schema::table('admin_users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('snippets', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('suggestions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        // Get default tenant (will be created by previous migration)
        $defaultTenant = DB::table('tenants')->first();
        if ($defaultTenant) {
            $defaultTenantId = $defaultTenant->id;
            
            // Update existing records with default tenant
            DB::table('admin_users')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('personas')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('knowledge_base')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('chat_sessions')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('chat_messages')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('snippets')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('suggestions')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        // Now make columns not nullable and add foreign key constraints
        Schema::table('admin_users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('snippets', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('suggestions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop foreign keys and columns
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('snippets', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
