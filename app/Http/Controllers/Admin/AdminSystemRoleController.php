<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemPermission;
use App\Models\SystemRole;
use App\Support\SystemAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSystemRoleController extends Controller
{
    public function index(): View
    {
        $roles = SystemRole::query()
            ->with(['permissions', 'users'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('admin.system-roles.index', [
            'roles' => $roles,
            'permissionGroups' => SystemAccess::groupedPermissions(),
            'permissionSlugs' => SystemAccess::permissionSlugs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'alpha_dash', 'max:100', 'unique:system_roles,slug'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(SystemAccess::permissionSlugs())],
        ]);

        $role = SystemRole::query()->create([
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($this->resolvePermissionIds($validated['permissions'] ?? []));

        return back()->with('success', 'System access role created successfully.');
    }

    public function update(Request $request, SystemRole $systemRole): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(SystemAccess::permissionSlugs())],
        ]);

        $systemRole->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $systemRole->permissions()->sync($this->resolvePermissionIds($validated['permissions'] ?? []));

        return back()->with('success', "{$systemRole->name} was updated successfully.");
    }

    public function destroy(SystemRole $systemRole): RedirectResponse
    {
        if ($systemRole->is_system) {
            return back()->with('error', 'Built-in access roles cannot be deleted.');
        }

        if ($systemRole->users()->exists()) {
            return back()->with('error', 'Remove this role from assigned users before deleting it.');
        }

        $systemRole->delete();

        return back()->with('success', 'System access role deleted successfully.');
    }

    private function resolvePermissionIds(array $permissionSlugs): array
    {
        if ($permissionSlugs === []) {
            return [];
        }

        return SystemPermission::query()
            ->whereIn('name', $permissionSlugs)
            ->pluck('id')
            ->all();
    }
}
