<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_user_id');
            $table->string('action');
            $table->jsonb('changes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
