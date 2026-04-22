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
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event');
                $table->nullableMorphs('subject');
                $table->string('subject_label')->nullable();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('route_name')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event', 'created_at'], 'audit_logs_event_created_idx');
                $table->index(['actor_id', 'created_at'], 'audit_logs_actor_created_idx');
            });
        }

        if (! Schema::hasTable('system_permissions') || ! Schema::hasTable('system_roles')) {
            return;
        }

        DB::transaction(function () {
            $permissionDefinition = SystemAccess::permissions()['audits.view'];

            $permission = SystemPermission::query()->updateOrCreate(
                ['name' => 'audits.view'],
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
                ->where('name', 'audits.view')
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

        Schema::dropIfExists('audit_logs');
    }
};
