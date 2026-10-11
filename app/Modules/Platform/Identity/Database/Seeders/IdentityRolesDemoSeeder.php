<?php

namespace App\Modules\Platform\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IdentityRolesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo role hanya untuk local/testing.');
        }
        DB::transaction(function (): void {
            foreach (['cashier' => ['sales.pos.invoices.create'], 'inventory-manager' => ['inventory.view', 'inventory.manage'],
                'inventory-viewer' => ['inventory.view']] as $name => $permissions) {
                if (Role::where('guard_name', 'web')->where('name', $name)->exists()) {
                    continue;
                }
                foreach ($permissions as $permission) {
                    Permission::findByName($permission, 'web');
                }
                $role = Role::create(['name' => $name, 'guard_name' => 'web']);
                $role->syncPermissions($permissions);
            }
        });
    }
}
