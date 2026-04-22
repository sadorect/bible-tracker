<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->foreignId('parent_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->foreignId('thread_root_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('direction', 20);
            $table->string('subject');
            $table->text('body');
            $table->json('targeting_filters')->nullable();
            $table->json('targeting_snapshot')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
            $table->index(['thread_root_id', 'created_at']);
            $table->index(['direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
