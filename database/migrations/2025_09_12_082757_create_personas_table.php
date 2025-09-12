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
            $table->uuid('associated_profile_snippet_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();

            $table->foreign('associated_profile_snippet_id')->references('id')->on('snippets')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('personas');
    }
}
