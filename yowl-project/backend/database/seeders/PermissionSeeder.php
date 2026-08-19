<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * The permissions the administration console can attach to a role.
     *
     * They are named after what they allow, not after a screen, so a role
     * keeps its meaning when the interface changes.
     */
    private const PERMISSIONS = [
        'moderate.reports',
        'moderate.content',
        'manage.users',
        'manage.roles',
        'manage.settings',
        'read.audit_log',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // L'administrateur les porte toutes par defaut.
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(self::PERMISSIONS);
    }
}
