<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonasTable extends Migration
{
    public function up()
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->text('ai_expertise_description')->nullable();
            $table->json('expertise_embedding')->nullable(); // Vector embedding stored as JSON
            $table->uuid('associated_profile_snippet_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();

            // Foreign key constraint will be added after snippets table is created
        });
    }

    public function down()
    {
        Schema::dropIfExists('personas');
    }
}
