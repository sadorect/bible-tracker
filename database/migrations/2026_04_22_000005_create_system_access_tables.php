<?php

use App\Models\SystemPermission;
use App\Models\SystemRole;
use App\Models\User;
use App\Support\SystemAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('group', 100);
            $table->text('description')->nullable();
        });

        Schema::create('system_roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('system_permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('system_permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['system_role_id', 'system_permission_id'], 'spr_role_perm_unq');
        });

        Schema::create('system_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['system_role_id', 'user_id'], 'sru_role_user_unq');
        });

        DB::transaction(function () {
            foreach (SystemAccess::permissions() as $slug => $permission) {
                SystemPermission::query()->create([
                    'name' => $slug,
                    'label' => $permission['label'],
                    'group' => $permission['group'],
                    'description' => $permission['description'] ?? null,
                ]);
            }

            $permissionsByName = SystemPermission::query()
                ->pluck('id', 'name');

            foreach (SystemAccess::defaultRoles() as $slug => $role) {
                $systemRole = SystemRole::query()->create([
                    'slug' => $slug,
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'is_system' => true,
                ]);

                $systemRole->permissions()->sync(
                    collect(SystemAccess::expandedPermissionsForRole($slug))
                        ->map(fn (string $permission) => $permissionsByName[$permission] ?? null)
                        ->filter()
                        ->values()
                        ->all()
                );
            }

            $superAdminRole = SystemRole::query()
                ->where('slug', SystemRole::SUPER_ADMIN)
                ->first();

            if ($superAdminRole) {
                $adminIds = User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->pluck('id')
                    ->all();

                $superAdminRole->users()->syncWithoutDetaching($adminIds);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_role_user');
        Schema::dropIfExists('system_permission_role');
        Schema::dropIfExists('system_roles');
        Schema::dropIfExists('system_permissions');
    }
};
