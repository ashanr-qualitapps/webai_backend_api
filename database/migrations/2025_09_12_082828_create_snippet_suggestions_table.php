<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnippetSuggestionsTable extends Migration
{
    public function up()
    {
        Schema::create('snippet_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('suggestion_name');
            $table->string('button_text');
            $table->text('ai_explanation')->nullable();
            $table->integer('priority_score')->default(0);
            $table->float('confidence_threshold')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('snippet_suggestions');
    }
}
