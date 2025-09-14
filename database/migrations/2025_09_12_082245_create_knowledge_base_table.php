<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKnowledgeBaseTable extends Migration
{
    public function up()
    {
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('content'); // Main knowledge base text content
            $table->json('embedding')->nullable(); // For semantic search vector embedding (stored as JSON array)
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();
            $table->timestampTz('last_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('knowledge_base');
    }
}
