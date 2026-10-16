<?php

namespace App\Modules\Purchasing\Suppliers\Database\Seeders;

use App\Modules\Purchasing\Suppliers\Application\Actions\ManageSupplier;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SuppliersRepository;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class SuppliersDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo supplier hanya boleh dijalankan pada local/testing.');
        }
        DB::transaction(function (): void {
            $repository = app(SuppliersRepository::class);
            foreach ([
                ['DEMO-SUP-CCTV', 'Distribusi CCTV Demo'],
                ['DEMO-SUP-ELEC', 'Distributor Kelistrikan Demo'],
                ['DEMO-SUP-CABLE', 'Supplier Kabel Demo'],
            ] as [$code, $name]) {
                if (! $repository->codeExists($code)) {
                    app(ManageSupplier::class)->save(new SupplierData($code, $name));
                }
            }
        }, 3);
    }
}
