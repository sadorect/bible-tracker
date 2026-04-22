<?php

namespace App\Support;

class SystemAccess
{
    public const PERMISSIONS = [
        'admin.access' => [
            'label' => 'Access admin console',
            'group' => 'Core',
            'description' => 'Open the admin console and shared administration shell.',
        ],
        'dashboard.view' => [
            'label' => 'View admin dashboard',
            'group' => 'Core',
            'description' => 'See system-wide dashboard summaries and operational snapshots.',
        ],
        'users.manage' => [
            'label' => 'Manage users',
            'group' => 'People',
            'description' => 'Create, edit, delete, and bulk-manage user accounts.',
        ],
        'hierarchies.manage' => [
            'label' => 'Manage hierarchies',
            'group' => 'People',
            'description' => 'Create and maintain clans, squads, platoons, batches, and teams.',
        ],
        'plans.manage' => [
            'label' => 'Manage reading plans',
            'group' => 'Plans',
            'description' => 'Create plans, adjust schedules, training resources, and enrollment links.',
        ],
        'progress.view' => [
            'label' => 'View reports',
            'group' => 'Reports',
            'description' => 'Open progress dashboards and detailed completion reports.',
        ],
        'progress.export' => [
            'label' => 'Export reports',
            'group' => 'Reports',
            'description' => 'Download progress data for offline reporting.',
        ],
        'audits.view' => [
            'label' => 'View audit trail',
            'group' => 'Core',
            'description' => 'Review high-value operational changes, exports, and administrative actions.',
        ],
        'automation.manage' => [
            'label' => 'Manage automation',
            'group' => 'Core',
            'description' => 'Configure reminder schedules, automation toggles, and lifecycle transitions.',
        ],
        'messages.manage_templates' => [
            'label' => 'Manage messaging settings',
            'group' => 'Messaging',
            'description' => 'Update message delivery defaults and shared message templates.',
        ],
        'system_roles.manage' => [
            'label' => 'Manage system access roles',
            'group' => 'Core',
            'description' => 'Create and maintain the platform access roles and permission bundles.',
        ],
    ];

    public const ROLE_DEFAULTS = [
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'Full platform access across every administration surface.',
            'permissions' => ['*'],
        ],
        'operations_admin' => [
            'name' => 'Operations Admin',
            'description' => 'Broad operational access for plans, users, hierarchy, and reporting.',
            'permissions' => [
                'admin.access',
                'dashboard.view',
                'users.manage',
                'hierarchies.manage',
                'plans.manage',
                'progress.view',
                'progress.export',
                'audits.view',
                'automation.manage',
            ],
        ],
        'plans_admin' => [
            'name' => 'Plans Admin',
            'description' => 'Manage reading plans, training resources, and enrollment links.',
            'permissions' => [
                'admin.access',
                'dashboard.view',
                'plans.manage',
            ],
        ],
        'reports_admin' => [
            'name' => 'Reports Admin',
            'description' => 'Review and export progress reporting across the platform.',
            'permissions' => [
                'admin.access',
                'dashboard.view',
                'progress.view',
                'progress.export',
            ],
        ],
        'messaging_admin' => [
            'name' => 'Messaging Admin',
            'description' => 'Maintain delivery defaults and shared message templates.',
            'permissions' => [
                'admin.access',
                'dashboard.view',
                'messages.manage_templates',
            ],
        ],
    ];

    public static function permissions(): array
    {
        return self::PERMISSIONS;
    }

    public static function permissionSlugs(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function groupedPermissions(): array
    {
        $grouped = [];

        foreach (self::PERMISSIONS as $slug => $permission) {
            $grouped[$permission['group']][$slug] = $permission;
        }

        return $grouped;
    }

    public static function defaultRoles(): array
    {
        return self::ROLE_DEFAULTS;
    }

    public static function expandedPermissionsForRole(string $slug): array
    {
        $permissions = self::ROLE_DEFAULTS[$slug]['permissions'] ?? [];

        if (in_array('*', $permissions, true)) {
            return self::permissionSlugs();
        }

        return array_values(array_intersect(self::permissionSlugs(), $permissions));
    }
}
