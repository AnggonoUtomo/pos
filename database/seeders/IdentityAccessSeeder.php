<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class IdentityAccessSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * @var list<string>
     */
    private array $permissions = [
        'platform.identity.users.view',
        'platform.identity.users.create',
        'sales.pos.invoices.create',
        'sales.pos.invoices.post',
        'sales.returns.create',
        'purchasing.purchase_orders.invoices.post',
        'inventory.warehouse_operations.transfers.create',
        'reporting.operational_reports.sales.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        $superAdmin = Role::findOrCreate('super-admin', self::GUARD);
        $superAdmin->syncPermissions($this->permissions);

        $user = User::query()->updateOrCreate(
            ['email' => env('SEED_SUPER_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('SEED_SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SEED_SUPER_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole($superAdmin);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
