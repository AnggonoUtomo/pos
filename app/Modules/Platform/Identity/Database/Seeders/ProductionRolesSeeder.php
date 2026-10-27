<?php

namespace App\Modules\Platform\Identity\Database\Seeders;

use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProductionRolesSeeder extends Seeder
{
    public const ROLE_PERMISSIONS = [
        // Super-system uses the existing Gate bypass; existing grants are never reset.
        'super-system' => [],
        'owner-supervisor' => [
            'inventory.view', 'inventory.stock.view', 'inventory.stock.manage',
            'inventory.stock.post', 'inventory.stock.adjust',
            'inventory.transfers.view', 'inventory.transfers.post',
            'sales.parties.view', 'sales.pos.invoices.view',
            'sales.returns.view', 'sales.returns.manage', 'sales.returns.post',
            'purchasing.suppliers.view', 'purchasing.purchase-orders.view',
            'purchasing.purchase-orders.post', 'purchasing.purchase-orders.pay',
            'purchasing.returns.view', 'purchasing.returns.post',
            'finance.payments.view', 'finance.payments.history.view',
            'reporting.operational.view',
        ],
        'master-admin' => [
            'inventory.view', 'inventory.manage', 'sales.parties.view',
            'sales.parties.manage', 'purchasing.suppliers.view', 'purchasing.suppliers.manage',
        ],
        'cashier-operational' => ['sales.pos.invoices.create', 'sales.pos.invoices.post'],
        'warehouse-staff' => [
            'inventory.view', 'inventory.stock.view', 'inventory.transfers.view',
            'inventory.transfers.manage', 'inventory.transfers.post',
        ],
        'purchasing-staff' => [
            'inventory.view', 'purchasing.suppliers.view', 'purchasing.suppliers.manage',
            'purchasing.purchase-orders.view', 'purchasing.purchase-orders.manage',
            'purchasing.returns.view', 'purchasing.returns.manage',
        ],
        'finance-staff' => [
            'finance.payments.view', 'finance.payments.manage', 'finance.payments.history.view',
            'purchasing.purchase-orders.view', 'purchasing.purchase-orders.pay',
            'sales.returns.view', 'sales.returns.refund',
            'purchasing.returns.view', 'purchasing.returns.refund',
        ],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        try {
            DB::transaction(function (): void {
                // Match the lock order used by administrative role mutations.
                $roles = Role::query()->where('guard_name', 'web')->orderBy('id')
                    ->lockForUpdate()->get()->keyBy('name');
                $required = array_values(array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))));
                $available = Permission::query()->where('guard_name', 'web')
                    ->whereIn('name', $required)->pluck('name')->all();
                $missing = array_diff($required, $available);
                if ($missing !== []) {
                    throw new DomainException('Katalog permission module belum lengkap: '.implode(', ', $missing));
                }

                // Preflight every role before creating anything. Never overwrite manual grants.
                foreach (self::ROLE_PERMISSIONS as $name => $expected) {
                    if ($name === 'super-system' || ! $roles->has($name)) {
                        continue;
                    }
                    sort($expected);
                    $actual = $roles[$name]->permissions()->orderBy('name')->pluck('name')->all();
                    if ($actual !== $expected) {
                        throw new DomainException('Matriks role '.$name.' berbeda. Tidak ada perubahan; tinjau melalui Identitas & Akses.');
                    }
                }

                foreach (self::ROLE_PERMISSIONS as $name => $permissions) {
                    if ($roles->has($name)) {
                        continue;
                    }
                    $role = Role::create(['name' => $name, 'guard_name' => 'web']);
                    $role->syncPermissions($permissions);
                    sort($permissions);
                    activity('identity')->performedOn($role)
                        ->event('identity.production_role_created')
                        ->withProperties([
                            'before' => null,
                            'after' => ['name' => $name, 'guard_name' => 'web', 'permissions' => $permissions],
                            'source' => self::class,
                        ])->log('Production role created');
                }
            }, 3);
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }
}
