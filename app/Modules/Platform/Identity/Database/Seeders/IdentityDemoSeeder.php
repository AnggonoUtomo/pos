<?php

namespace App\Modules\Platform\Identity\Database\Seeders;

use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Database\Seeder;

class IdentityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(IdentityAccessSeeder::class);
    }
}
