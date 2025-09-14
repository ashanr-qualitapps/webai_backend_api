<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnippetsTable extends Migration
{
    public function up()
    {
        Schema::create('snippets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier')->unique();
            $table->text('collapsed_html')->nullable();
            $table->text('expanded_html')->nullable();
            $table->text('ai_explanation')->nullable();
            $table->text('hyperlink_keywords')->nullable();
            $table->uuid('assigned_persona_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();

            $table->foreign('assigned_persona_id')->references('id')->on('personas')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('snippets');
    }
}
