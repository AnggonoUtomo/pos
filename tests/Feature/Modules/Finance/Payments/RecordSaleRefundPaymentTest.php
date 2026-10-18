<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RecordSaleRefundPaymentTest extends TestCase
{
    use RefreshDatabase;

    private array $methods;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentsDemoSeeder::class);
        $this->methods = DB::table('payment_methods')->pluck('id', 'code')->all();
    }

    private function request(?array $payments = null, ?string $source = null, string $total = '100.01', ?int $actor = null): SaleRefundPaymentRequest
    {
        return new SaleRefundPaymentRequest($source ?? strtolower((string) Str::ulid()), 'SR-QA', $total, $payments ?? [
            ['method_id' => $this->methods['TUNAI'], 'amount' => '40'],
            ['method_id' => $this->methods['TRANSFER'], 'amount' => '60.01'],
        ], $actor);
    }

    private function pay(SaleRefundPaymentRequest $request): mixed
    {
        return DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay($request));
    }

    public function test_exact_multi_payment_snapshot_replay_and_actor_are_stable(): void
    {
        $actor = User::factory()->create();
        $request = $this->request(actor: $actor->id);
        $result = $this->pay($request);
        $this->assertSame('100.01', $result->snapshot['total']);
        $this->assertCount(2, $result->snapshot['payments']);
        $this->assertDatabaseCount('payment_refunds', 1);
        $this->assertDatabaseCount('payment_refund_lines', 2);
        $this->assertDatabaseCount('payment_disbursements', 0);
        $this->assertDatabaseCount('payment_receipts', 0);
        $this->assertDatabaseHas('payment_refunds', ['id' => $result->id, 'source_type' => 'sale_return', 'paid_by' => $actor->id]);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $result->id, 'event' => 'refunded', 'causer_id' => $actor->id]);
        foreach (['account_id', 'account_number', 'account_holder', 'bank_name', 'request_hash', 'total_cost'] as $field) {
            $this->assertStringNotContainsString($field, json_encode($result));
            $this->assertStringNotContainsString($field, Activity::where('event', 'refunded')->sole()->properties->toJson());
        }
        DB::table('payment_methods')->update(['is_active' => false, 'deleted_at' => now(), 'name' => 'Nama berubah']);
        DB::table('payment_accounts')->update(['is_active' => false, 'deleted_at' => now()]);
        $retry = $this->request(array_reverse($request->payments), strtoupper($request->sourceId), actor: null);
        $replay = $this->pay($retry);
        $this->assertSame($result->id, $replay->id);
        $this->assertSame($result->paidAt, $replay->paidAt);
        $this->assertSame($result->snapshot, $replay->snapshot);
        $this->assertDatabaseHas('payment_refunds', ['id' => $result->id, 'paid_by' => $actor->id]);
        $this->assertSame(1, Activity::where('event', 'refunded')->count());
    }

    public function test_invalid_splits_totals_and_fields_do_not_create_any_refund(): void
    {
        $invalid = [[], [['method_id' => $this->methods['TUNAI'], 'amount' => '0']],
            [['method_id' => $this->methods['TUNAI'], 'amount' => '100']],
            [['method_id' => $this->methods['TUNAI'], 'amount' => '101']],
            [['method_id' => $this->methods['TUNAI'], 'amount' => '-1']],
            [['method_id' => $this->methods['TUNAI'], 'amount' => '100.001']],
            [['method_id' => $this->methods['TUNAI'], 'amount' => 100.01]],
            [['method_id' => $this->methods['TUNAI'], 'amount' => '100.01', 'account_id' => strtolower((string) Str::ulid())]],
            [['method_id' => $this->methods['TUNAI'], 'amount' => '50'], ['method_id' => strtoupper($this->methods['TUNAI']), 'amount' => '50.01']],
            array_fill(0, 11, ['method_id' => $this->methods['TUNAI'], 'amount' => '100.01']),
        ];
        foreach ($invalid as $payments) {
            try {
                $this->pay($this->request($payments));
                $this->fail('Invalid split accepted.');
            } catch (InvalidArgumentException|ValidationException) {
                $this->assertDatabaseCount('payment_refunds', 0);
                $this->assertDatabaseCount('payment_refund_lines', 0);
                $this->assertSame(0, Activity::where('event', 'refunded')->count());
            }
        }
        foreach (['0', '100.001', '10000000000000000000000000000'] as $total) {
            try {
                $this->pay($this->request(total: $total));
                $this->fail('Invalid total accepted.');
            } catch (InvalidArgumentException|ValidationException) {
                $this->assertDatabaseCount('payment_refunds', 0);
            }
        }
    }

    public function test_changed_source_body_is_not_a_replay(): void
    {
        $request = $this->request();
        $this->pay($request);
        $this->expectException(InvalidArgumentException::class);
        $this->pay($this->request([['method_id' => $this->methods['TUNAI'], 'amount' => '100.01']], $request->sourceId));
    }

    public function test_invalid_masters_and_disabled_or_vetoed_audit_roll_back(): void
    {
        foreach (['inactive_method', 'inactive_account', 'deleted_method', 'deleted_account', 'incompatible', 'missing', 'audit', 'disabled_audit'] as $case) {
            DB::beginTransaction();
            try {
                match ($case) {
                    'inactive_method' => DB::table('payment_methods')->update(['is_active' => false]),
                    'inactive_account' => DB::table('payment_accounts')->update(['is_active' => false]),
                    'deleted_method' => DB::table('payment_methods')->update(['deleted_at' => now()]),
                    'deleted_account' => DB::table('payment_accounts')->update(['deleted_at' => now()]),
                    'incompatible' => DB::table('payment_accounts')->update(['type' => 'bank']),
                    'missing' => DB::table('payment_methods')->where('id', $this->methods['TRANSFER'])->delete(),
                    'audit' => Activity::creating(fn ($log) => $log->event === 'refunded' ? false : null),
                    'disabled_audit' => activity()->disableLogging(),
                };
                try {
                    $this->pay($this->request());
                    $this->fail('Invalid master/audit accepted.');
                } catch (InvalidArgumentException|RuntimeException) {
                    $this->assertDatabaseCount('payment_refunds', 0);
                    $this->assertDatabaseCount('payment_refund_lines', 0);
                }
            } finally {
                activity()->enableLogging();
                Activity::flushEventListeners();
                DB::rollBack();
            }
        }
    }

    public function test_outer_failure_after_writer_rolls_back_refund_and_audit(): void
    {
        try {
            DB::transaction(function (): void {
                app(SaleRefundPaymentWriter::class)->pay($this->request());
                throw new RuntimeException('Caller veto');
            });
        } catch (RuntimeException) {
            $this->assertDatabaseCount('payment_refunds', 0);
            $this->assertDatabaseCount('payment_refund_lines', 0);
            $this->assertSame(0, Activity::where('event', 'refunded')->count());
        }
    }

    public function test_maximum_scale_two_amount_preserves_last_cent(): void
    {
        $amount = '9999999999999999999999999999.99';
        $result = $this->pay($this->request([['method_id' => $this->methods['TUNAI'], 'amount' => $amount]], total: $amount));
        $this->assertSame($amount, $result->snapshot['total']);
        $this->assertSame($amount, $result->snapshot['payments'][0]['amount']);
    }

    public function test_refund_history_protects_method_and_direct_account_references(): void
    {
        $request = $this->request();
        $original = $this->pay($request)->snapshot;
        $method = DB::table('payment_methods')->where('id', $this->methods['TUNAI'])->sole();
        $otherAccount = DB::table('payment_methods')->where('id', $this->methods['TRANSFER'])->value('payment_account_id');
        $changes = [fn () => app(ManagePaymentMethod::class)->delete($method->id),
            fn () => app(ManagePaymentMethod::class)->save(new PaymentMethodData($method->code, $method->name, 'cash', $method->payment_account_id, false), $method->id),
            fn () => app(ManagePaymentMethod::class)->save(new PaymentMethodData($method->code, $method->name, 'transfer', $otherAccount, true), $method->id)];
        foreach ($changes as $change) {
            try {
                $change();
                $this->fail('Refund history guard failed.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('payment_refunds', 1);
            }
        }
        app(ManagePaymentMethod::class)->save(new PaymentMethodData($method->code, 'Nama baru', 'cash', $method->payment_account_id, true), $method->id);
        $this->assertSame($original, $this->pay($request)->snapshot);
        $this->assertDatabaseHas('payment_methods', ['id' => $method->id, 'name' => 'Nama baru']);
        DB::table('payment_methods')->where('id', $method->id)->update(['payment_account_id' => $otherAccount]);
        $this->expectException(ValidationException::class);
        app(ManagePaymentAccount::class)->delete($method->payment_account_id);
    }

    public function test_choices_are_safe_and_only_return_compatible_active_masters(): void
    {
        $reader = app(SaleRefundPaymentMethods::class);
        $this->assertCount(3, $reader->choices());
        $this->assertSame(['id', 'code', 'name', 'type'], array_keys($reader->choices()[0]));
        DB::table('payment_accounts')->where('type', 'bank')->update(['is_active' => false]);
        $this->assertSame([$this->methods['TUNAI']], array_column($reader->choices(), 'id'));
    }

    public function test_schema_is_additive_and_guard_works_before_refund_migration(): void
    {
        $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_19_000001_create_payment_refunds.php');
        $this->assertTrue(Schema::hasColumns('payment_refunds', ['source_id', 'request_hash', 'total', 'paid_at']));
        $this->assertFalse(Schema::hasColumn('payment_refunds', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('payment_refund_lines', 'deleted_at'));
        $migration->down();
        app(ManagePaymentMethod::class)->delete($this->methods['DEBIT']);
        $migration->up();
        $this->assertTrue(Schema::hasTable('payment_refund_lines'));
    }

    public function test_posted_refund_prevents_down_migration(): void
    {
        $this->pay($this->request());
        $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_19_000001_create_payment_refunds.php');
        $this->expectException(LogicException::class);
        $migration->down();
    }

    public function test_writer_requires_caller_transaction(): void
    {
        $request = $this->request();
        $writer = app(SaleRefundPaymentWriter::class);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->expectException(LogicException::class);
        $writer->pay($request);
    }

    public function test_source_and_actor_validation_does_not_persist_a_partial_refund(): void
    {
        foreach ([['bad-id', 'SR-QA', null], [strtolower((string) Str::ulid()), '', null],
            [strtolower((string) Str::ulid()), str_repeat('X', 33), null],
            [strtolower((string) Str::ulid()), 'SR-QA', 0]] as [$source, $number, $actor]) {
            try {
                $this->pay(new SaleRefundPaymentRequest($source, $number, '1', [['method_id' => $this->methods['TUNAI'], 'amount' => '1']], $actor));
                $this->fail('Invalid source accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('payment_refunds', 0);
            }
        }
    }
}
