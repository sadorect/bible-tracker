<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('delivery_preference_snapshot', 20);
            $table->string('rendered_subject');
            $table->text('rendered_body');
            $table->timestamp('inbox_delivered_at')->nullable();
            $table->string('email_status', 20)->default('pending');
            $table->timestamp('email_attempted_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->text('email_failure')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'recipient_id']);
            $table->index(['recipient_id', 'read_at']);
            $table->index(['recipient_id', 'created_at']);
            $table->index(['email_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
    }
};
