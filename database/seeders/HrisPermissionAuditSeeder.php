<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ensures every HRIS role has exactly the right permissions for its purpose.
 *
 * Policy naming convention used throughout this app (and in the 3 new policies):
 *   underscore ( _ )  — biometric_enrollment, leave_card, leave_application
 *
 * Legacy Shield-generated permissions use double-colon ( :: ) for multi-word
 * models.  super_admin and HRIS Leave Admin only have :: versions, causing
 * policy checks to fail.  This seeder adds the underscore aliases they need.
 *
 * HRIS resource → model → policy gate used
 * ─────────────────────────────────────────────────────────────────────────
 * ProfileResource             Profile  → ProfilePolicy          → view_any_profile
 * AttendanceResource          Profile  → ProfilePolicy          → view_any_profile
 * ServiceRecordResource       Profile  → ProfilePolicy          → view_any_profile
 * TrainingResource            Profile  → ProfilePolicy          → view_any_profile
 * IndividualPerformanceRes.   Profile  → ProfilePolicy          → view_any_profile
 * LeaveCardResource           LeaveCard         → LeaveCardPolicy      → view_any_leave_card
 * LeaveApplicationResource    LeaveApplication  → LeaveApplicationPolicy → view_any_leave_application
 * BiometricEnrollmentResource BiometricEnrollment → BiometricEnrollmentPolicy → view_any_biometric_enrollment
 */
class HrisPermissionAuditSeeder extends Seeder
{
    private string $guard = 'web';

    /** All prefixes Shield generates per resource */
    private array $prefixes = [
        'view',
        'view_any',
        'create',
        'update',
        'restore',
        'restore_any',
        'replicate',
        'reorder',
        'delete',
        'delete_any',
        'force_delete',
        'force_delete_any',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ── 1. Guarantee all underscore permissions exist ─────────────────────
        $this->ensurePermissions('biometric_enrollment');
        $this->ensurePermissions('leave_card');
        $this->ensurePermissions('leave_application');

        // ── 2. super_admin — must have every underscore permission ────────────
        $this->syncRolePermissions('super_admin', $this->allUnderscore());

        // ── 3. HRIS Admin — full access to all HRIS resources ────────────────
        $this->syncRolePermissions('HRIS Admin', array_merge(
            $this->profilePermissions('full'),
            $this->namesFor('leave_card'),
            $this->namesFor('leave_application'),
            $this->namesFor('biometric_enrollment'),
        ));

        // ── 4. HRIS Leave Admin — leave resources only ────────────────────────
        $this->syncRolePermissions('HRIS Leave Admin', array_merge(
            $this->namesFor('leave_card'),
            $this->namesFor('leave_application'),
        ));

        // ── 5. HRIS Biometrics Admin — biometric enrollment only ──────────────
        $this->syncRolePermissions('HRIS Biometrics Admin', $this->namesFor('biometric_enrollment'));

        // ── 6. HRIS Training Encoder — training + profile read ────────────────
        //    Training uses Profile model → gated by view_any_profile.
        //    Also needs view_any_training / create_training / update_training
        //    for the Admin panel's TrainingResource (Training model).
        $this->syncRolePermissions('HRIS Training Encoder', array_merge(
            $this->profilePermissions('read'),
            ['view_any_training', 'view_training', 'create_training', 'update_training'],
        ));

        // ── 7. Hris GIP — profile read + write, training read + write ─────────
        $this->syncRolePermissions('Hris GIP', array_merge(
            $this->profilePermissions('write'),
            ['view_any_training', 'view_training', 'create_training', 'update_training'],
        ));

        // ── 8. HRIS User — profile read only ─────────────────────────────────
        $this->syncRolePermissions('HRIS User', $this->profilePermissions('read'));

        // ── Done ──────────────────────────────────────────────────────────────
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Permission audit complete. All HRIS role permissions synced.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create all 12 underscore permissions for the given resource if they
     * don't already exist.
     */
    private function ensurePermissions(string $resource): void
    {
        foreach ($this->prefixes as $prefix) {
            Permission::firstOrCreate([
                'name'       => "{$prefix}_{$resource}",
                'guard_name' => $this->guard,
            ]);
        }
        $this->command->info("  ✓ Permissions ensured for [{$resource}]");
    }

    /**
     * Return all 12 underscore permission names for the given resource.
     *
     * @return string[]
     */
    private function namesFor(string $resource): array
    {
        return array_map(fn ($p) => "{$p}_{$resource}", $this->prefixes);
    }

    /**
     * All underscore permissions that exist in DB for biometric + leave resources.
     *
     * @return string[]
     */
    private function allUnderscore(): array
    {
        return array_merge(
            $this->namesFor('biometric_enrollment'),
            $this->namesFor('leave_card'),
            $this->namesFor('leave_application'),
        );
    }

    /**
     * Profile permissions by level.
     *  'read'  — view_any + view
     *  'write' — read + create + update
     *  'full'  — write + delete variants
     *
     * @return string[]
     */
    private function profilePermissions(string $level): array
    {
        $read = ['view_any_profile', 'view_profile'];

        $write = array_merge($read, ['create_profile', 'update_profile']);

        $full = array_merge($write, [
            'delete_profile',
            'delete_any_profile',
            'force_delete_profile',
            'force_delete_any_profile',
            'restore_profile',
            'restore_any_profile',
            'replicate_profile',
            'reorder_profile',
        ]);

        return match ($level) {
            'read'  => $read,
            'write' => $write,
            'full'  => $full,
            default => $read,
        };
    }

    /**
     * Sync a role's underscore-style permissions WITHOUT touching its
     * legacy ( :: ) double-colon permissions.
     *
     * Strategy:
     *   1. Detach only the underscore permissions that belong to the managed
     *      resources (biometric_enrollment, leave_card, leave_application,
     *      profile — single-word so no :: conflict).
     *   2. Re-attach exactly the desired set.
     *
     * This preserves any :: permissions the role already holds (needed for
     * Admin panel resources like super_admin's vehicle/equipment access).
     *
     * @param string[] $permissionNames
     */
    private function syncRolePermissions(string $roleName, array $permissionNames): void
    {
        $role = Role::where('name', $roleName)
                    ->where('guard_name', $this->guard)
                    ->first();

        if (! $role) {
            $this->command->warn("  ⚠  Role [{$roleName}] not found — skipped.");
            return;
        }

        // Resolve permission IDs we want to manage (underscore only)
        $desired = Permission::whereIn('name', $permissionNames)
                              ->where('guard_name', $this->guard)
                              ->get();

        // The resources we manage permissions for (underscore pattern)
        $managedPatterns = [
            'biometric_enrollment',
            'leave_card',
            'leave_application',
            'profile',
            'training',
        ];

        // Current permissions split into managed vs unmanaged (:: versions etc.)
        $current = $role->permissions;

        $unmanaged = $current->filter(function ($perm) use ($managedPatterns) {
            foreach ($managedPatterns as $pattern) {
                if (str_ends_with($perm->name, $pattern) || str_contains($perm->name, $pattern . '_')) {
                    // This is an underscore-style permission for a managed resource
                    return false;
                }
            }
            return true; // keep legacy :: permissions untouched
        });

        // Rebuild: unmanaged kept + desired set
        $newSet = $unmanaged->pluck('id')
                            ->merge($desired->pluck('id'))
                            ->unique()
                            ->values();

        $role->permissions()->sync($newSet);

        $this->command->info("  ✓ [{$roleName}] synced — {$desired->count()} managed permission(s) set.");
    }
}
