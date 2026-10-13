<?php

namespace Tests\Feature\Modules\Sales\Parties;

use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomerLevel;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ItemPricesTest extends TestCase
{
    use RefreshDatabase;

    private string $item;

    private array $units;

    private array $levels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnitsDemoSeeder::class);
        $this->units = Unit::whereIn('code', ['PCS', 'BOX'])->orderBy('code')->pluck('id')->all();
        $this->item = app(CreateItem::class)->handle(new CreateItemData('PRICE-TEST', 'Barang harga', $this->units[1], [
            new ItemUnitData($this->units[1], '1'), new ItemUnitData($this->units[0], '12'),
        ]));
        $this->levels = [];
        foreach (['ECERAN', 'GROSIR', 'INSTALLER'] as $code) {
            $this->levels[] = CustomerLevel::create(['code' => $code, 'name' => $code])->id;
        }
    }

    private function rows(): array
    {
        $rows = [];
        foreach ($this->units as $unit) {
            foreach ($this->levels as $level) {
                $rows[] = new ItemPriceData($unit, $level, '100000.25');
            }
        }

        return $rows;
    }

    public function test_complete_matrix_save_read_noop_and_edit_are_exact_audited(): void
    {
        $service = app(ItemPriceCatalog::class);
        $service->saveForItem($this->item, $this->rows());
        $this->assertDatabaseCount('item_prices', 6);
        $logs = DB::table('activity_log')->where('log_name', 'sales.item_prices')->count();
        $this->assertSame(6, $logs);
        $service->saveForItem($this->item, $this->rows());
        $this->assertSame($logs, DB::table('activity_log')->where('log_name', 'sales.item_prices')->count());
        $rows = $this->rows();
        $rows[0] = new ItemPriceData($rows[0]->unitId, $rows[0]->customerLevelId, '999999999999999999.99');
        $service->saveForItem($this->item, $rows);
        $this->assertDatabaseHas('item_prices', ['item_id' => $this->item, 'unit_id' => $rows[0]->unitId,
            'customer_level_id' => $rows[0]->customerLevelId, 'amount' => '999999999999999999.99']);
        $this->assertCount(6, $service->forItems([$this->item])[$this->item]);
        $saved = collect($service->forItems([$this->item])[$this->item])->first(fn ($price) => $price->unitId === $rows[0]->unitId && $price->customerLevelId === $rows[0]->customerLevelId);
        $this->assertSame('999999999999999999.99', $saved->amount);
    }

    public function test_missing_pair_rolls_back_all_prices_and_audit(): void
    {
        $rows = $this->rows();
        array_pop($rows);
        try {
            app(ItemPriceCatalog::class)->saveForItem($this->item, $rows);
            $this->fail('Incomplete matrix must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('prices', $exception->errors());
        }
        $this->assertDatabaseCount('item_prices', 0);
        $this->assertSame(0, DB::table('activity_log')->where('log_name', 'sales.item_prices')->count());
    }

    public function test_duplicate_and_invalid_amount_do_not_change_existing_matrix(): void
    {
        $service = app(ItemPriceCatalog::class);
        $service->saveForItem($this->item, $this->rows());
        $before = DB::table('item_prices')->orderBy('id')->get()->toJson();
        foreach (['0', '-1', '1.001', '1e6', '1,5'] as $amount) {
            $rows = $this->rows();
            $rows[0] = new ItemPriceData($rows[0]->unitId, $rows[0]->customerLevelId, $amount);
            try {
                $service->saveForItem($this->item, $rows);
                $this->fail('Invalid amount must fail.');
            } catch (ValidationException) {
                $this->assertSame($before, DB::table('item_prices')->orderBy('id')->get()->toJson());
            }
        }
        $rows = $this->rows();
        $rows[1] = $rows[0];
        $this->expectException(ValidationException::class);
        $service->saveForItem($this->item, $rows);
    }

    public function test_unused_sale_unit_prices_are_soft_deleted_and_still_protect_level(): void
    {
        $service = app(ItemPriceCatalog::class);
        $service->saveForItem($this->item, $this->rows());
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->units[0])->update(['is_sale_unit' => false]);
        $rows = array_values(array_filter($this->rows(), fn ($row) => $row->unitId === $this->units[1]));
        $service->saveForItem($this->item, $rows);
        $this->assertSame(3, DB::table('item_prices')->whereNotNull('deleted_at')->count());
        $this->assertCount(3, $service->forItems([$this->item])[$this->item]);
        $this->expectException(\InvalidArgumentException::class);
        app(ManageCustomerLevel::class)->delete($this->levels[0]);
    }

    public function test_prices_migration_can_roll_back_and_reapply(): void
    {
        $migration = require app_path('Modules/Sales/Parties/Database/Migrations/2026_10_13_000001_create_item_prices_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('item_prices'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('item_prices'));
    }

    public function test_readding_sale_unit_restores_same_price_identifiers(): void
    {
        $service = app(ItemPriceCatalog::class);
        $service->saveForItem($this->item, $this->rows());
        $ids = DB::table('item_prices')->orderBy('id')->pluck('id')->all();
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->units[0])->update(['is_sale_unit' => false]);
        $service->saveForItem($this->item, array_values(array_filter($this->rows(), fn ($row) => $row->unitId === $this->units[1])));
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->units[0])->update(['is_sale_unit' => true]);
        $service->saveForItem($this->item, $this->rows());
        $this->assertSame($ids, DB::table('item_prices')->orderBy('id')->pluck('id')->all());
        $this->assertSame(0, DB::table('item_prices')->whereNotNull('deleted_at')->count());
    }

    public function test_direct_call_rejects_oversized_matrix_and_no_active_levels(): void
    {
        $service = app(ItemPriceCatalog::class);
        try {
            $service->saveForItem($this->item, array_fill(0, 5001, $this->rows()[0]));
            $this->fail('Direct callers must respect matrix bound.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('5.000', $exception->errors()['prices'][0]);
        }
        DB::table('customer_levels')->update(['is_active' => false]);
        try {
            $service->saveForItem($this->item, []);
            $this->fail('Sellable item requires active levels.');
        } catch (ValidationException $exception) {
            $this->assertSame('Belum ada level pelanggan aktif.', $exception->errors()['prices'][0]);
        }
        $this->assertDatabaseCount('item_prices', 0);
    }
}
