<?php

namespace App\Modules\Platform\CompanySettings\Database\Seeders;

use App\Modules\Platform\CompanySettings\Application\Actions\UpdateCompanySettings;
use App\Modules\Platform\CompanySettings\Application\Contracts\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanySettingsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('Demo hanya local/testing.');
        }
        if (app(CompanyProfile::class)->read()['version'] !== 0) {
            return;
        }
        app(UpdateCompanySettings::class)->handle([
            'name' => 'Toko CCTV & Listrik Demo', 'address' => 'Alamat fiktif untuk demonstrasi',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda.', 'version' => 0,
        ], null);
    }
}
