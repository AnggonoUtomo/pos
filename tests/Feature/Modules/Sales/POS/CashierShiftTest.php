<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManageCashbook;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private string $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([POSAccessSeeder::class, PaymentsDemoSeeder::class]);
        $this->cashier = User::factory()->create()->givePermissionTo('sales.pos.invoices.post');
        $this->account = DB::table('payment_accounts')->where('type', 'cash')->value('id');
        app(ManageCashbook::class)->open(['account_id' => $this->account, 'amount' => '100', 'starts_on' => now('Asia/Jakarta')->toDateString()], $this->cashier->id);
        $this->actingAs($this->cashier);
    }

    private function open(): string
    {
        $id = strtolower((string) Str::ulid());
        $this->postJson('/pos/shifts/'.$id.'/open', ['account_id' => $this->account])->assertOk()->assertJsonPath('shift.id', $id)->assertJsonPath('shift.opening_balance', '100.000000');

        return $id;
    }

    public function test_open_is_idempotent_exclusive_and_does_not_duplicate_money(): void
    {
        $id = $this->open();
        $this->postJson('/pos/shifts/'.$id.'/open', ['account_id' => $this->account])->assertOk();
        $this->postJson('/pos/shifts/'.strtolower((string) Str::ulid()).'/open', ['account_id' => $this->account])->assertUnprocessable();
        $other = User::factory()->create()->givePermissionTo('sales.pos.invoices.post');
        $this->actingAs($other)->postJson('/pos/shifts/'.strtolower((string) Str::ulid()).'/open', ['account_id' => $this->account])->assertUnprocessable();
        $this->getJson('/pos/shifts/'.$id)->assertForbidden();
        $other->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '100', 'reason' => ''])->assertForbidden();
        $this->assertDatabaseCount('cashier_shifts', 1);
        $this->assertDatabaseCount('cashbook_entries', 0);
        $this->assertDatabaseCount('payment_receipts', 0);
    }

    public function test_close_captures_all_cashbook_changes_and_snapshot_is_immutable(): void
    {
        $id = $this->open();
        app(ManageCashbook::class)->post(['kind' => 'incoming', 'account_id' => $this->account, 'amount' => '50.123456', 'description' => 'Tambahan uang operasional', 'occurred_on' => now('Asia/Jakarta')->toDateString(), 'idempotency_key' => (string) Str::uuid()], $this->cashier->id);
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '150.123456', 'reason' => ''])->assertOk()->assertJsonPath('shift.expected_cash', '150.123456')->assertJsonPath('shift.difference', '0.000000')->assertJsonPath('shift.review_status', 'not_required');
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '150.123456', 'reason' => ''])->assertOk();
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '155', 'reason' => ''])->assertStatus(409);
        $this->getJson('/pos/shifts/current')->assertOk()->assertJsonPath('active', null);
        $this->assertSame(2, DB::table('activity_log')->where('log_name', 'sales.pos.shifts')->count());
        $this->assertDatabaseCount('cashbook_entries', 1);
    }

    public function test_difference_requires_reason_releases_drawer_and_requires_explicit_review(): void
    {
        $id = $this->open();
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '90', 'reason' => ''])->assertUnprocessable();
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '90', 'reason' => 'Uang fisik kurang'])->assertOk()->assertJsonPath('shift.difference', '-10.000000')->assertJsonPath('shift.review_status', 'pending');
        $this->postJson('/pos/shifts/'.$id.'/review', ['note' => 'Sudah diperiksa'])->assertForbidden();
        $next = User::factory()->create()->givePermissionTo('sales.pos.invoices.post');
        $this->actingAs($next)->postJson('/pos/shifts/'.strtolower((string) Str::ulid()).'/open', ['account_id' => $this->account])->assertOk();
        $reviewer = User::factory()->create()->givePermissionTo(['sales.pos.shifts.view', 'sales.pos.shifts.review']);
        $this->actingAs($reviewer)->postJson('/pos/shifts/'.$id.'/review', ['note' => 'Sudah diperiksa'])->assertOk()->assertJsonPath('shift.review_status', 'reviewed');
        $this->postJson('/pos/shifts/'.$id.'/review', ['note' => 'Sudah diperiksa'])->assertOk();
        $this->getJson('/pos/shifts/'.$id)->assertOk()->assertJsonPath('shift.counted_cash', '90.000000');
        $this->assertDatabaseCount('cashbook_entries', 0);
    }

    public function test_bank_uninitialised_accounts_invalid_counts_and_foreign_payload_are_rejected(): void
    {
        $bank = DB::table('payment_accounts')->where('type', 'bank')->value('id');
        $this->postJson('/pos/shifts/'.strtolower((string) Str::ulid()).'/open', ['account_id' => $bank])->assertUnprocessable();
        $id = $this->open();
        foreach (['-1', '0.1234567', 'NaN', '1e6'] as $amount) {
            $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => $amount, 'reason' => ''])->assertUnprocessable();
        }
        $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '100', 'reason' => '', 'actor_id' => 999])->assertUnprocessable();
        $this->assertDatabaseHas('cashier_shifts', ['id' => $id, 'status' => 'open']);
    }

    public function test_audit_failure_rolls_back_closing_and_active_pointers(): void
    {
        $id = $this->open();
        Activity::creating(function ($audit): void {
            if ($audit->log_name === 'sales.pos.shifts') {
                throw new \RuntimeException('QA audit failure');
            }
        });
        try {
            $this->postJson('/pos/shifts/'.$id.'/close', ['counted' => '100', 'reason' => ''])->assertServerError();
            $this->assertDatabaseHas('cashier_shifts', ['id' => $id, 'status' => 'open', 'active_actor_id' => $this->cashier->id, 'active_account_id' => $this->account]);
        } finally {
            Activity::flushEventListeners();
        }
    }
}
