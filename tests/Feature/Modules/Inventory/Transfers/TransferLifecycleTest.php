<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Infrastructure\Persistence\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class TransferLifecycleTest extends TransferTestCase
{
    public function test_soft_delete_restore_keeps_snapshot_even_when_master_unavailable(): void
    {
        $id = $this->postJson('/inventory/transfers', $this->transfer())->assertCreated()->json('id');
        $before = DB::table('stock_transfer_lines')->get()->toJson();
        $this->deleteJson('/inventory/transfers/'.$id, ['version' => 1, 'status' => 'posted'])->assertUnprocessable();
        $this->deleteJson('/inventory/transfers/'.$id, ['version' => 1])->assertOk();
        $this->deleteJson('/inventory/transfers/'.$id, ['version' => 2])->assertNotFound();
        $this->getJson('/inventory/transfers/data')->assertJsonPath('meta.total', 0);
        $this->getJson('/inventory/transfers/data?trashed=1')->assertJsonPath('meta.total', 1)->assertJsonMissingPath('data.0.demo_key');
        DB::table('warehouses')->update(['is_active' => false]);
        DB::table('units')->update(['is_active' => false]);
        $this->postJson('/inventory/transfers/'.$id.'/restore', ['version' => 1])->assertUnprocessable();
        $this->postJson('/inventory/transfers/'.$id.'/restore', ['version' => 2])->assertOk();
        $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'version' => 3, 'deleted_at' => null]);
        $this->assertSame($before, DB::table('stock_transfer_lines')->get()->toJson());
        $this->assertSame(['created', 'deleted', 'restored'], Activity::where('log_name', 'inventory.transfers')->orderBy('id')->pluck('event')->all());
        $this->assertStringNotContainsString('Catatan internal', Activity::where('log_name', 'inventory.transfers')->get()->toJson());
        $migration = require app_path('Modules/Inventory/Transfers/Database/Migrations/2026_10_23_000003_create_stock_transfer_drafts.php');
        $this->expectException(LogicException::class);
        $migration->down();
    }

    public function test_audit_veto_rolls_back_every_mutation(): void
    {
        $action = app(ManageTransferDraft::class);
        $id = $action->save($this->transfer());
        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            if ($event === 'restored') {
                $action->delete($id, ['version' => 1]);
            }
            $before = [DB::table('stock_transfers')->get()->toJson(), DB::table('stock_transfer_lines')->get()->toJson(), Activity::count()];
            Activity::creating(fn ($log) => $log->log_name === 'inventory.transfers' && $log->event === $event ? false : null);
            try {
                match ($event) {
                    'created' => $action->save($this->transfer()),
                    'updated' => $action->save(array_replace($this->transfer(), ['version' => 1, 'notes' => 'Baru']), $id),
                    'deleted' => $action->delete($id, ['version' => 1]),
                    'restored' => $action->restore($id, ['version' => 2]),
                };
                $this->fail('Audit wajib atomic.');
            } catch (RuntimeException) {
                $this->assertSame($before, [DB::table('stock_transfers')->get()->toJson(), DB::table('stock_transfer_lines')->get()->toJson(), Activity::count()]);
            } finally {
                Activity::flushEventListeners();
            }
        }
    }

    public function test_non_draft_and_exhausted_version_remain_immutable_for_super_system(): void
    {
        $id = $this->postJson('/inventory/transfers', $this->transfer())->assertCreated()->json('id');
        $this->manager->assignRole(Role::findOrCreate('super-system', 'web'));
        DB::table('stock_transfers')->where('id', $id)->update(['status' => 'posted']);
        $this->deleteJson('/inventory/transfers/'.$id, ['version' => 1])->assertUnprocessable();
        $this->putJson('/inventory/transfers/'.$id, $this->transfer() + ['version' => 1])->assertUnprocessable();
        DB::table('stock_transfers')->where('id', $id)->update(['status' => 'draft', 'version' => 2147483647]);
        $this->deleteJson('/inventory/transfers/'.$id, ['version' => 2147483647])->assertUnprocessable();
    }

    public function test_model_veto_does_not_replace_lines_or_advance_version(): void
    {
        $id = $this->postJson('/inventory/transfers', $this->transfer())->assertCreated()->json('id');
        $before = DB::table('stock_transfer_lines')->get()->toJson();
        StockTransfer::saving(fn () => false);
        try {
            app(ManageTransferDraft::class)->save(array_replace($this->transfer(), ['version' => 1, 'notes' => 'Ditolak']), $id);
            $this->fail('Veto harus membatalkan save.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'version' => 1]);
            $this->assertSame($before, DB::table('stock_transfer_lines')->get()->toJson());
        } finally {
            StockTransfer::flushEventListeners();
        }
    }
}
