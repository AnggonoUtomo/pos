<?php

namespace App\Modules\Sales\Parties\Database\Seeders;

use App\Modules\Sales\Parties\Application\Actions\ManageCustomer;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomerLevel;
use App\Modules\Sales\Parties\Application\Contracts\PartiesRepository;
use App\Modules\Sales\Parties\Application\DTOs\CustomerData;
use App\Modules\Sales\Parties\Application\DTOs\CustomerLevelData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PartiesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo pelanggan hanya boleh dijalankan pada local/testing.');
        }
        DB::transaction(function (): void {
            $repository = app(PartiesRepository::class);
            foreach (['ECERAN' => 'Eceran', 'GROSIR' => 'Grosir', 'INSTALLER' => 'Installer'] as $code => $name) {
                if ($repository->findLevelByCode($code) === null) {
                    app(ManageCustomerLevel::class)->save(new CustomerLevelData($code, $name));
                }
            }
            foreach ([
                ['DEMO-UMUM', 'Pelanggan Umum', 'ECERAN'],
                ['DEMO-GROSIR', 'Toko Listrik Demo', 'GROSIR'],
                ['DEMO-INSTALLER', 'Installer CCTV Demo', 'INSTALLER'],
            ] as [$code, $name, $levelCode]) {
                if ($repository->customerCodeExists($code)) {
                    continue;
                }
                $level = $repository->findLevelByCode($levelCode);
                if ($level === null || ! $level['is_active'] || $level['deleted_at'] !== null) {
                    throw new LogicException("Level {$levelCode} harus aktif sebelum demo pelanggan baru dibuat.");
                }
                app(ManageCustomer::class)->save(new CustomerData($code, $name, $level['id']));
            }
        });
    }
}
