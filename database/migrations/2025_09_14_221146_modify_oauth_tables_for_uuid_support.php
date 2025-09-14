<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify oauth_access_tokens table to support UUID user_ids
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('id');
        });

        // Modify oauth_auth_codes table to support UUID user_ids
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('id');
        });

        // Modify oauth_refresh_tokens table to support UUID user_ids (if needed)
        if (Schema::hasColumn('oauth_refresh_tokens', 'user_id')) {
            Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            
            Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert oauth_access_tokens table
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->after('id');
        });

        // Revert oauth_auth_codes table
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->after('id');
        });

        // Revert oauth_refresh_tokens table (if needed)
        if (Schema::hasColumn('oauth_refresh_tokens', 'user_id')) {
            Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            
            Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
                $table->bigInteger('user_id')->nullable()->after('id');
            });
        }
    }
};
