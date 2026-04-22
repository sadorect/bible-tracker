<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hierarchies') || ! Schema::hasColumn('hierarchies', 'leader_id')) {
            return;
        }

        Schema::table('hierarchies', function (Blueprint $table) {
            $table->foreignId('leader_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hierarchies') || ! Schema::hasColumn('hierarchies', 'leader_id')) {
            return;
        }

        Schema::table('hierarchies', function (Blueprint $table) {
            $table->foreignId('leader_id')->nullable(false)->change();
        });
    }
};
