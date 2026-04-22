<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class SchemaCapabilities
{
    private static array $cache = [];

    public static function supportsMessageRecipientFolders(): bool
    {
        return self::allColumnsExist('message_recipients', [
            'archived_at',
            'deleted_at',
        ]);
    }

    public static function supportsMessageSenderFolders(): bool
    {
        return self::allColumnsExist('messages', [
            'sender_archived_at',
            'sender_deleted_at',
        ]);
    }

    public static function supportsNotificationPreferences(): bool
    {
        return self::columnExists('users', 'notification_preferences');
    }

    private static function columnExists(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (! array_key_exists($key, self::$cache)) {
            self::$cache[$key] = Schema::hasColumn($table, $column);
        }

        return self::$cache[$key];
    }

    private static function allColumnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! self::columnExists($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
