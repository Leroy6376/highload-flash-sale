<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    private const string GuardName = 'web';

    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'catalog.events.view',
            'catalog.events.create',
            'catalog.events.update',
            'catalog.events.delete',
            'catalog.ticket-types.view',
            'catalog.ticket-types.create',
            'catalog.ticket-types.update',
            'catalog.ticket-types.delete',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, self::GuardName);
        }

        $permissionRegistrar->forgetCachedPermissions();

        Role::findOrCreate(UserRole::SuperAdmin->value, self::GuardName)->syncPermissions($permissions);
        Role::findOrCreate(UserRole::CatalogManager->value, self::GuardName)->syncPermissions([
            'admin.access',
            'catalog.events.view',
            'catalog.events.create',
            'catalog.events.update',
            'catalog.events.delete',
            'catalog.ticket-types.view',
            'catalog.ticket-types.create',
            'catalog.ticket-types.update',
            'catalog.ticket-types.delete',
        ]);
        Role::findOrCreate(UserRole::Customer->value, self::GuardName);
    }
}
