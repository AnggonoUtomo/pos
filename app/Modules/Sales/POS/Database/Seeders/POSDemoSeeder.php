<?php

namespace App\Modules\Sales\POS\Database\Seeders;

use Illuminate\Database\Seeder;

class POSDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('Demo POS hanya local/testing.');
        }
        // Posted sales are created only in disposable QA fixtures, never by demo seeding.
    }
}
