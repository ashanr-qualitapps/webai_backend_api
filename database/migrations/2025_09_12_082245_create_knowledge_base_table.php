<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateKnowledgeBaseTable extends Migration
{
    public function up()
    {
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('content'); // Main knowledge base text content
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();
            $table->timestampTz('last_updated')->nullable();
        });

        // Add vector column using raw SQL
        DB::statement('ALTER TABLE knowledge_base ADD COLUMN embedding vector(1536)');
    }

    public function down()
    {
        Schema::dropIfExists('knowledge_base');
    }
}
