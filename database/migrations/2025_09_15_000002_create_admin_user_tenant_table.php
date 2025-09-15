<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_user_tenant', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id');
            $table->string('admin_user_id');
            $table->primary(['tenant_id', 'admin_user_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
        });
        // Remove tenant_id from admin_users if exists
        if (Schema::hasColumn('admin_users', 'tenant_id')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_tenant');
        // Optionally, add tenant_id back to admin_users
        // Schema::table('admin_users', function (Blueprint $table) {
        //     $table->unsignedBigInteger('tenant_id')->nullable();
        // });
    }
};
