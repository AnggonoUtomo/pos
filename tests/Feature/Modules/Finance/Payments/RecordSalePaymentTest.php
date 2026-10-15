<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Actions\RecordSalePayment;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecordSalePaymentTest extends TestCase
{
    use RefreshDatabase;

    private array $methods;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PaymentsAccessSeeder::class, PaymentsDemoSeeder::class]);
        $this->methods = DB::table('payment_methods')->pluck('id', 'code')->all();
    }

    private function input(): array
    {
        return ['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-QA-001', 'total' => '100000',
            'payments' => [['method_id' => $this->methods['TUNAI'], 'amount' => '50000'],
                ['method_id' => $this->methods['TRANSFER'], 'amount' => '60000']]];
    }

    public function test_net_cash_snapshots_and_explicit_actor_are_atomic_and_private(): void
    {
        $actor = User::factory()->create();
        $this->actingAs(User::factory()->create());
        DB::table('payment_accounts')->where('type', 'bank')->update(['account_number' => '0001234567', 'bank_name' => 'Bank Rahasia QA', 'account_holder' => 'Pemilik Rahasia QA']);
        $result = app(RecordSalePayment::class)->handle($this->input(), $actor->id);
        $this->assertTrue(Str::isUlid($result['id']));
        $this->assertSame('100000.00', $result['total']);
        $this->assertSame('110000.00', $result['tendered']);
        $this->assertSame('10000.00', $result['change']);
        $cash = collect($result['payments'])->firstWhere('method_type', 'cash');
        $this->assertSame(['50000.00', '40000.00', '10000.00'], array_values(array_intersect_key($cash, array_flip(['tendered_amount', 'applied_amount', 'change_amount']))));
        $this->assertSame('Kas Toko Demo', $cash['account_name']);
        $this->assertSame('Tunai', $cash['method_name']);
        $this->assertSame(2, count($result['payments']));
        $this->assertDatabaseHas('activity_log', ['subject_id' => $result['id'], 'causer_id' => $actor->id, 'event' => 'received']);
        $public = json_encode($result).DB::table('activity_log')->get()->toJson();
        foreach (['0001234567', 'Bank Rahasia QA', 'Pemilik Rahasia QA'] as $private) {
            $this->assertStringNotContainsString($private, $public);
        }
    }

    public function test_canonical_retry_preserves_snapshot_and_never_duplicates_audit(): void
    {
        $input = $this->input();
        $first = app(RecordSalePayment::class)->handle($input);
        app(ManagePaymentMethod::class)->save(new PaymentMethodData('TUNAI-NEW', 'Tunai Baru', 'cash', $first['payments'][0]['account_id']), $this->methods['TUNAI']);
        $input['payments'] = array_reverse($input['payments']);
        foreach ($input['payments'] as &$row) {
            $row['method_id'] = strtoupper($row['method_id']);
            $row['amount'] .= '.00';
        }
        unset($row);
        $input['source_id'] = strtoupper($input['source_id']);
        $input['source_number'] = ' POS-QA-001 ';
        $input['total'] = '0100000.00';
        $this->assertSame($first, app(RecordSalePayment::class)->handle($input));
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseCount('payment_records', 2);
        $this->assertSame(1, Activity::where('event', 'received')->count());
        $this->assertSame(0, Activity::where('event', 'received')->whereNotNull('causer_id')->count());
    }

    public function test_source_conflict_including_actor_is_rejected(): void
    {
        $input = $this->input();
        app(RecordSalePayment::class)->handle($input);
        foreach (['actor', 'total', 'number', 'amount'] as $change) {
            $next = $input;
            $actor = null;
            if ($change === 'actor') {
                $actor = User::factory()->create()->id;
            } elseif ($change === 'total') {
                $next['total'] = '90000';
            } elseif ($change === 'number') {
                $next['source_number'] = 'POS-DIFFERENT';
            } else {
                $next['payments'][0]['amount'] = '60000';
            }
            try {
                app(RecordSalePayment::class)->handle($next, $actor);
                $this->fail('Source conflict must fail.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('payment_receipts', 1);
                $this->assertDatabaseCount('payment_records', 2);
                $this->assertSame(1, Activity::where('event', 'received')->count());
            }
        }
    }

    public function test_inactive_deleted_or_incompatible_master_rejects_without_partial_data(): void
    {
        $method = $this->methods['TUNAI'];
        $account = DB::table('payment_methods')->where('id', $method)->value('payment_account_id');
        foreach (['inactive_method', 'deleted_method', 'inactive_account', 'deleted_account', 'incompatible'] as $case) {
            DB::beginTransaction();
            try {
                if ($case === 'incompatible') {
                    DB::table('payment_accounts')->where('id', $account)->update(['type' => 'bank']);
                } else {
                    DB::table(str_ends_with($case, 'method') ? 'payment_methods' : 'payment_accounts')->where('id', str_ends_with($case, 'method') ? $method : $account)
                        ->update([str_starts_with($case, 'inactive') ? 'is_active' : 'deleted_at' => str_starts_with($case, 'inactive') ? false : now()]);
                }
                try {
                    app(RecordSalePayment::class)->handle($this->input());
                    $this->fail('Invalid master must fail.');
                } catch (ValidationException|InvalidArgumentException $exception) {
                    $this->assertDatabaseCount('payment_receipts', 0);
                    $this->assertDatabaseCount('payment_records', 0);
                    $this->assertSame(0, Activity::where('event', 'received')->count());
                }
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_bad_input_and_untrusted_type_are_rejected(): void
    {
        foreach (['total', 'amount', 'duplicate', 'type', 'missing', 'two_cash', 'underpaid', 'overpaid_bank'] as $case) {
            $input = $this->input();
            match ($case) {
                'total' => $input['total'] = 100000,
                'amount' => $input['payments'][0]['amount'] = '50.000,00',
                'duplicate' => $input['payments'][] = $input['payments'][0],
                'type' => $input['payments'][0]['type'] = 'transfer',
                'missing' => $input['payments'][0]['method_id'] = strtolower((string) Str::ulid()),
                'underpaid' => $input['payments'][0]['amount'] = '1',
                'overpaid_bank' => $input['payments'][1]['amount'] = '110000',
                'two_cash' => $input['payments'][1]['method_id'] = app(ManagePaymentMethod::class)->save(new PaymentMethodData('CASH2', 'Kas Kedua', 'cash', DB::table('payment_accounts')->where('type', 'cash')->value('id'))),
            };
            try {
                app(RecordSalePayment::class)->handle($input);
                $this->fail('Invalid payment must fail.');
            } catch (ValidationException|InvalidArgumentException) {
                $this->assertDatabaseCount('payment_receipts', 0);
                $this->assertDatabaseCount('payment_records', 0);
            }
        }
    }

    public function test_persisted_money_is_normalized_and_never_uses_float_calculation(): void
    {
        $input = $this->input();
        $input['total'] = '100000.17';
        $input['payments'] = [['method_id' => $this->methods['TUNAI'], 'amount' => '100001.23']];
        $result = app(RecordSalePayment::class)->handle($input);
        $this->assertSame($input['total'], $result['total']);
        $this->assertSame('1.06', $result['change']);
        $this->assertSame($input['total'], $result['payments'][0]['applied_amount']);
    }

    public function test_posted_history_guards_method_and_account_even_for_super_system(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        $result = app(RecordSalePayment::class)->handle($this->input());
        $method = DB::table('payment_methods')->where('id', $this->methods['TRANSFER'])->first();
        $other = app(ManagePaymentAccount::class)->save(new PaymentAccountData('BANK2', 'Bank QA', 'bank'));
        $payload = ['code' => $method->code, 'name' => $method->name, 'type' => $method->type, 'payment_account_id' => $method->payment_account_id, 'is_active' => true];
        $this->deleteJson('/finance/payment-methods/'.$method->id)->assertUnprocessable()->assertJsonValidationErrors('payments');
        foreach ([['is_active' => false], ['type' => 'debit'], ['payment_account_id' => $other]] as $changes) {
            $this->putJson('/finance/payment-methods/'.$method->id, array_replace($payload, $changes))->assertUnprocessable()->assertJsonValidationErrors('payments');
        }
        $this->putJson('/finance/payment-methods/'.$method->id, array_replace($payload, ['name' => 'Nama Baru']))->assertOk();
        // Direct history protects an account even after an unsupported external reassignment.
        DB::table('payment_methods')->where('payment_account_id', $method->payment_account_id)->update(['payment_account_id' => $other]);
        $this->deleteJson('/finance/payment-accounts/'.$method->payment_account_id)->assertUnprocessable()->assertJsonValidationErrors('payments');
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseHas('payment_records', ['receipt_id' => $result['id'], 'method_name' => 'Transfer']);
    }

    public function test_audit_veto_rolls_back_and_retry_succeeds(): void
    {
        $input = $this->input();
        Activity::creating(static fn (Activity $activity) => $activity->event === 'received' ? false : null);
        try {
            app(RecordSalePayment::class)->handle($input);
            $this->fail('Audit veto must not be success.');
        } catch (\RuntimeException) {
            $this->assertDatabaseCount('payment_receipts', 0);
            $this->assertDatabaseCount('payment_records', 0);
        } finally {
            Activity::flushEventListeners();
        }
        $this->assertNotEmpty(app(RecordSalePayment::class)->handle($input)['id']);
    }

    public function test_failure_after_persistence_rolls_back_all_rows(): void
    {
        $repository = \Mockery::mock(app(PaymentsRepository::class))->makePartial();
        $repository->shouldReceive('auditReceipt')->once()->andThrow(new \RuntimeException('Simulated audit failure'));
        $this->app->instance(PaymentsRepository::class, $repository);
        try {
            app(RecordSalePayment::class)->handle($this->input());
            $this->fail('Must roll back.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit failure', $exception->getMessage());
            $this->assertDatabaseCount('payment_receipts', 0);
            $this->assertDatabaseCount('payment_records', 0);
        }
    }

    public function test_assignment_changed_after_discovery_is_not_silently_followed(): void
    {
        $id = $this->methods['TUNAI'];
        $old = DB::table('payment_methods')->where('id', $id)->value('payment_account_id');
        $new = app(ManagePaymentAccount::class)->save(new PaymentAccountData('OTHER', 'Kas Baru', 'cash'));
        $repository = \Mockery::mock(app(PaymentsRepository::class))->makePartial();
        $repository->shouldReceive('methodAccountId')->with($id)->once()->andReturnUsing(function () use ($id, $old, $new): string {
            DB::table('payment_methods')->where('id', $id)->update(['payment_account_id' => $new]);

            return $old;
        });
        $this->app->instance(PaymentsRepository::class, $repository);
        try {
            app(RecordSalePayment::class)->handle($this->input());
            $this->fail('Changed assignment must fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Relasi akun', $exception->getMessage());
            $this->assertDatabaseCount('payment_receipts', 0);
            $this->assertDatabaseCount('payment_records', 0);
        }
    }

    public function test_master_fk_restrict_and_populated_down_cannot_erase_payments(): void
    {
        $result = app(RecordSalePayment::class)->handle($this->input());
        foreach (['payment_methods' => $result['payments'][0]['method_id'], 'payment_accounts' => $result['payments'][0]['account_id'], 'payment_receipts' => $result['id']] as $table => $id) {
            try {
                DB::transaction(fn () => DB::table($table)->where('id', $id)->delete());
                $this->fail('FK must restrict deletion.');
            } catch (QueryException) {
                $this->assertDatabaseCount('payment_records', 2);
            }
        }
        $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_16_000002_create_payment_receipts.php');
        $this->expectException(\LogicException::class);
        $migration->down();
    }
}
