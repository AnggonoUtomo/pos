<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Models\User;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class StockDraftTest extends StockTestCase
{
    public function test_draft_lifecycle_is_audited_and_does_not_mutate_stock(): void
    {
        $id = $this->draft();
        $this->assertDatabaseHas('stock_openings', ['id' => $id, 'status' => 'draft']);
        $this->assertDatabaseHas('stock_opening_lines', ['opening_id' => $id, 'base_quantity' => '24.000000', 'total_value' => '240000.000000']);
        $payload = $this->payload();
        $payload['lines'][0]['quantity'] = '3';
        $payload['status'] = 'posted';
        $this->putJson("/inventory/stock/openings/{$id}", $payload)->assertOk();
        $this->assertDatabaseHas('stock_openings', ['id' => $id, 'status' => 'draft']);
        $this->deleteJson("/inventory/stock/openings/{$id}")->assertOk();
        $this->assertSoftDeleted('stock_openings', ['id' => $id]);
        $this->putJson("/inventory/stock/openings/{$id}", $payload)->assertNotFound();
        $this->postJson("/inventory/stock/openings/{$id}/restore")->assertOk();
        foreach (['stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], Activity::where('log_name', 'inventory.stock')->orderBy('id')->pluck('event')->all());
    }

    public function test_invalid_duplicate_lines_cost_and_master_are_rejected(): void
    {
        $payload = $this->payload();
        $payload['lines'][] = $payload['lines'][0];
        $this->postJson('/inventory/stock/openings', $payload)->assertUnprocessable();
        $payload = $this->payload();
        $payload['lines'][0]['unit_cost'] = '0';
        $this->postJson('/inventory/stock/openings', $payload)->assertUnprocessable();
        $payload['lines'][0]['unit_cost'] = 120000;
        $this->postJson('/inventory/stock/openings', $payload)->assertUnprocessable();
        DB::table('warehouses')->where('id', $this->warehouse)->update(['is_active' => false]);
        $this->postJson('/inventory/stock/openings', $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('stock_openings', 0);
    }

    public function test_permission_is_checked_on_backend_and_guest_is_rejected(): void
    {
        $id = $this->draft();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.stock.view');
        $this->actingAs($viewer);
        $this->postJson('/inventory/stock/openings', $this->payload())->assertForbidden();
        $this->putJson("/inventory/stock/openings/{$id}", $this->payload())->assertForbidden();
        $this->deleteJson("/inventory/stock/openings/{$id}")->assertForbidden();
        $this->postJson("/inventory/stock/openings/{$id}/restore")->assertForbidden();
        auth()->logout();
        $this->postJson('/inventory/stock/openings', [])->assertUnauthorized();
    }

    public function test_audit_failure_rolls_back_draft_and_lines(): void
    {
        Activity::creating(static function (Activity $activity): void {
            if ($activity->log_name === 'inventory.stock') {
                throw new \RuntimeException('Audit QA gagal.');
            }
        });
        try {
            $this->postJson('/inventory/stock/openings', $this->payload())->assertStatus(500);
            $this->assertDatabaseCount('stock_openings', 0);
            $this->assertDatabaseCount('stock_opening_lines', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_persisted_decimal_keeps_all_digits_without_float_conversion(): void
    {
        $payload = $this->payload();
        $payload['lines'][0] = ['item_id' => $this->item, 'unit_id' => $this->base,
            'quantity' => '99999999999999.999999', 'unit_cost' => '1'];
        $id = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        $record = DB::transaction(fn () => app(StockRepository::class)->lockDraft($id));
        $this->assertSame('99999999999999.999999', $record['lines'][0]['base_quantity']);
        $this->assertSame('99999999999999.999999', $record['lines'][0]['total_value']);
    }

    public function test_master_ulids_are_case_insensitive_and_duplicate_variants_are_rejected(): void
    {
        $payload = $this->payload();
        $payload['warehouse_id'] = strtoupper($this->warehouse);
        $payload['lines'][0]['item_id'] = strtoupper($this->item);
        $payload['lines'][0]['unit_id'] = strtoupper($this->box);
        $id = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        $this->assertDatabaseHas('stock_opening_lines', ['opening_id' => $id, 'item_id' => $this->item, 'unit_id' => $this->box]);
        $payload['lines'][] = $this->payload()['lines'][0];
        $this->postJson('/inventory/stock/openings', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('stock_openings', 1);
    }
}
