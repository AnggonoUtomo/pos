<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Database\Seeders\TransfersDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class TransferDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_is_relational_idempotent_and_never_moves_stock_or_overwrites_trashed_draft(): void
    {
        foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class] as $seeder) {
            $this->seed($seeder);
        }
        $before = [DB::table('items')->get()->toJson(), DB::table('warehouses')->get()->toJson(), DB::table('units')->get()->toJson()];
        $this->seed(TransfersDemoSeeder::class);
        $this->seed(TransfersDemoSeeder::class);
        $this->assertDatabaseCount('stock_transfers', 1);
        $this->assertDatabaseCount('stock_transfer_lines', 2);
        $this->assertDatabaseHas('stock_transfer_lines', ['sku' => 'DEMO-ELEC-NYM-3X15', 'quantity' => '0.125000', 'base_quantity' => '12.500000']);
        $id = DB::table('stock_transfers')->value('id');
        DB::table('stock_transfers')->where('id', $id)->update(['notes' => 'Catatan user']);
        app(ManageTransferDraft::class)->delete($id, ['version' => 1]);
        $snapshot = DB::table('stock_transfers')->get()->toJson();
        $this->seed(TransfersDemoSeeder::class);
        $this->assertSame($snapshot, DB::table('stock_transfers')->get()->toJson());
        $this->assertSame($before, [DB::table('items')->get()->toJson(), DB::table('warehouses')->get()->toJson(), DB::table('units')->get()->toJson()]);
        foreach (['stock_movements', 'fifo_layers', 'stock_balances', 'payment_receipts', 'payment_disbursements', 'users', 'roles', 'permissions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_demo_skips_missing_prerequisites_without_creating_master(): void
    {
        $this->seed(TransfersDemoSeeder::class);
        $this->assertDatabaseCount('stock_transfers', 0);
        $this->assertDatabaseCount('warehouses', 0);
    }

    public function test_demo_refuses_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);
        app(TransfersDemoSeeder::class)->run();
    }
}
