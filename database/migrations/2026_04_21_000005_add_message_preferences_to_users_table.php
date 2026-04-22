<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('message_delivery_preference', 20)->nullable()->after('hierarchy_id');
            $table->boolean('message_delivery_preference_locked')->default(false)->after('message_delivery_preference');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'message_delivery_preference',
                'message_delivery_preference_locked',
            ]);
        });
    }
};
