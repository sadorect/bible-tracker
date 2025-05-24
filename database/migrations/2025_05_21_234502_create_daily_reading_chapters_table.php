<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_reading_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_reading_id')->constrained()->onDelete('cascade');
            $table->foreignId('bible_chapter_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate entries
            $table->unique(['daily_reading_id', 'bible_chapter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reading_chapters');
    }
};
