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
            $table->uuid('snippet_id');
            $table->uuid('suggestion_id');
            $table->integer('display_order');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('snippet_id')->references('id')->on('snippets')->onDelete('cascade');
            $table->foreign('suggestion_id')->references('id')->on('suggestions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('snippet_suggestions');
    }
}
