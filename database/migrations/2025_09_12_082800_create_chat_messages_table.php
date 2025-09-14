<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->uuid('snippet_id')->nullable();
            $table->uuid('persona_id')->nullable();
            $table->string('message_type')->nullable();
            $table->text('message_text');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            $table->foreign('snippet_id')->references('id')->on('snippets')->onDelete('set null');
            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
