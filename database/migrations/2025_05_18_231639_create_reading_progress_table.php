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
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reading_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('daily_reading_id')->constrained()->onDelete('cascade');
            $table->date('completed_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint to ensure a user can't submit multiple reports for the same daily reading
            $table->unique(['user_id', 'daily_reading_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
