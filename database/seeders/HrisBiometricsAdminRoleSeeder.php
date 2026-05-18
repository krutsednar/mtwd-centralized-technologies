<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HrisBiometricsAdminRoleSeeder extends Seeder
{
    /**
     * Permission prefixes that FilamentShield generates for every resource.
     */
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
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // ── 1. Create permissions for every new HRIS resource ─────────────────
        $biometricPerms      = $this->createPermissions('biometric_enrollment', $guard);
        $leaveCardPerms      = $this->createPermissions('leave_card', $guard);
        $leaveApplicationPerms = $this->createPermissions('leave_application', $guard);

        // ── 2. Create (or fetch) the "HRIS Biometrics Admin" role ─────────────
        $biometricsAdminRole = Role::firstOrCreate([
            'name'       => 'HRIS Biometrics Admin',
            'guard_name' => $guard,
        ]);

        // Biometrics Admin gets ONLY biometric_enrollment permissions
        $biometricsAdminRole->syncPermissions($biometricPerms);

        $this->command->info('✓  Role "HRIS Biometrics Admin" synced with '.count($biometricPerms).' biometric_enrollment permissions.');

        // ── 3. Ensure "HRIS Admin" has ALL HRIS resource permissions ──────────
        //    Uses givePermissionTo() so existing permissions are preserved.
        $hrisAdminRole = Role::where('name', 'HRIS Admin')
                             ->where('guard_name', $guard)
                             ->first();

        if ($hrisAdminRole) {
            $hrisAdminRole->givePermissionTo(
                collect($biometricPerms)
                    ->merge($leaveCardPerms)
                    ->merge($leaveApplicationPerms)
                    ->all()
            );
            $this->command->info('✓  Role "HRIS Admin" granted all new HRIS permissions.');
        } else {
            $this->command->warn('  Role "HRIS Admin" not found — skipped. Create it and assign permissions manually.');
        }

        // ── 4. Ensure "super_admin" has ALL new permissions ───────────────────
        //    Required because define_via_gate is false in filament-shield config.
        $superAdminRole = Role::where('name', 'super_admin')
                              ->where('guard_name', $guard)
                              ->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(
                collect($biometricPerms)
                    ->merge($leaveCardPerms)
                    ->merge($leaveApplicationPerms)
                    ->all()
            );
            $this->command->info('✓  Role "super_admin" granted all new HRIS permissions.');
        } else {
            $this->command->warn('  Role "super_admin" not found — skipped.');
        }

        // Reset cache again after all changes
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Done. Run [php artisan optimize] to clear application cache.');
    }

    /**
     * Create all 12 Shield-style permissions for the given resource slug.
     *
     * @return \Spatie\Permission\Models\Permission[]
     */
    private function createPermissions(string $resource, string $guard): array
    {
        $perms = [];

        foreach ($this->prefixes as $prefix) {
            $perms[] = Permission::firstOrCreate([
                'name'       => "{$prefix}_{$resource}",
                'guard_name' => $guard,
            ]);
        }

        $this->command->info("  Created/verified ".count($perms)." permissions for [{$resource}].");

        return $perms;
    }
}
