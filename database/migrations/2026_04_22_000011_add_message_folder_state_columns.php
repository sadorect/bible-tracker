<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'sender_archived_at')) {
                $table->timestamp('sender_archived_at')->nullable()->after('thread_root_id');
            }

            if (! Schema::hasColumn('messages', 'sender_deleted_at')) {
                $table->timestamp('sender_deleted_at')->nullable()->after('sender_archived_at');
            }
        });

        Schema::table('message_recipients', function (Blueprint $table) {
            if (! Schema::hasColumn('message_recipients', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('read_at');
            }

            if (! Schema::hasColumn('message_recipients', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('messages', 'sender_archived_at')) {
                $drops[] = 'sender_archived_at';
            }

            if (Schema::hasColumn('messages', 'sender_deleted_at')) {
                $drops[] = 'sender_deleted_at';
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });

        Schema::table('message_recipients', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('message_recipients', 'archived_at')) {
                $drops[] = 'archived_at';
            }

            if (Schema::hasColumn('message_recipients', 'deleted_at')) {
                $drops[] = 'deleted_at';
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
