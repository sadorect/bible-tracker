<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Identifies the seeded demo accounts created by UserSeeder / AdminUserSeeder.
 *
 * These all share the default password from the seeder, so they are useful for
 * testing but must be removable in one deliberate step before real members join.
 */
class DemoAccounts
{
    /**
     * Reserved example domains (RFC 2606). Addresses here can never belong to a
     * real person, which is what makes bulk deletion safe.
     *
     * @var list<string>
     */
    public const RESERVED_DOMAINS = [
        'example.com',
        'example.org',
        'example.net',
    ];

    /**
     * Query matching every seeded demo account.
     */
    public static function query(): Builder
    {
        return User::query()->where(function (Builder $query) {
            foreach (self::RESERVED_DOMAINS as $domain) {
                $query->orWhere('email', 'like', '%@'.$domain);
            }
        });
    }

    public static function isDemoAccount(User $user): bool
    {
        $domain = strtolower((string) substr((string) strrchr((string) $user->email, '@'), 1));

        return in_array($domain, self::RESERVED_DOMAINS, true);
    }
}
