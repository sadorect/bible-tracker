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
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'old_testament', 'new_testament', etc.
            $table->integer('chapters_per_day')->default(8);
            $table->integer('streak_days')->default(10); // Days before a break
            $table->integer('break_days')->default(1); // Number of break days
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_reading_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reading_plan_id')->constrained()->onDelete('cascade');
            $table->date('joined_date');
            $table->integer('current_day')->default(1);
            $table->integer('current_streak')->default(0);
            $table->float('completion_rate')->default(0);
            $table->timestamps();
            
            // Unique constraint to ensure a user can join a plan only once
            $table->unique(['user_id', 'reading_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_plans');
        Schema::dropIfExists('user_reading_plans');
    }
};
