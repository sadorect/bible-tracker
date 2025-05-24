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
        Schema::table('bible_chapters', function (Blueprint $table) {
            $table->text('content')->nullable()->after('testament');
            $table->string('version')->default('KJV')->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_chapters', function (Blueprint $table) {
            $table->dropColumn(['content', 'version']);
        });
    }
};
