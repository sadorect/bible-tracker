<?php

use App\Models\SystemPermission;
use App\Models\SystemRole;
use App\Support\SystemAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('system_permissions') || ! Schema::hasTable('system_roles')) {
            return;
        }

        DB::transaction(function () {
            $permissionDefinition = SystemAccess::permissions()['automation.manage'];

            $permission = SystemPermission::query()->updateOrCreate(
                ['name' => 'automation.manage'],
                [
                    'label' => $permissionDefinition['label'],
                    'group' => $permissionDefinition['group'],
                    'description' => $permissionDefinition['description'] ?? null,
                ],
            );

            SystemRole::query()
                ->whereIn('slug', [
                    SystemRole::SUPER_ADMIN,
                    'operations_admin',
                ])
                ->get()
                ->each(fn (SystemRole $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('system_permission_role') && Schema::hasTable('system_permissions')) {
            $permissionId = SystemPermission::query()
                ->where('name', 'automation.manage')
                ->value('id');

            if ($permissionId) {
                DB::table('system_permission_role')
                    ->where('system_permission_id', $permissionId)
                    ->delete();

                SystemPermission::query()
                    ->whereKey($permissionId)
                    ->delete();
            }
        }

        Schema::dropIfExists('notifications');
    }
};
