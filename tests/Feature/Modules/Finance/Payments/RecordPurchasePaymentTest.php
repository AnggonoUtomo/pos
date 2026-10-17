<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RecordPurchasePaymentTest extends TestCase
{
    use RefreshDatabase;

    private array $methods;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentsDemoSeeder::class);
        $this->methods = DB::table('payment_methods')->pluck('id', 'code')->all();
    }

    private function request(?array $payments = null, ?string $source = null): PurchasePaymentRequest
    {
        return new PurchasePaymentRequest($source ?? strtolower((string) Str::ulid()), 'PO-QA', '100.123456', $payments ?? [
            ['method_id' => $this->methods['TUNAI'], 'amount' => '40'],
            ['method_id' => $this->methods['TRANSFER'], 'amount' => '60.123456'],
        ]);
    }

    private function pay(PurchasePaymentRequest $request): mixed
    {
        return DB::transaction(fn () => app(PurchasePaymentWriter::class)->pay($request));
    }

    public function test_exact_multi_payment_snapshot_replay_and_history_guard(): void
    {
        $request = $this->request();
        $result = $this->pay($request);
        $this->assertSame('100.123456', $result->snapshot['total']);
        $this->assertCount(2, $result->snapshot['payments']);
        $this->assertDatabaseCount('payment_disbursements', 1);
        $this->assertDatabaseCount('payment_disbursement_lines', 2);
        $this->assertDatabaseCount('payment_receipts', 0);
        $public = json_encode($result->snapshot);
        foreach (['account_id', 'account_number', 'account_holder', 'bank_name'] as $key) {
            $this->assertStringNotContainsString($key, $public);
        }
        $this->assertDatabaseHas('activity_log', ['subject_id' => $result->id, 'event' => 'disbursed', 'causer_id' => null]);
        DB::table('payment_methods')->update(['is_active' => false, 'name' => 'Renamed']);
        $replay = $this->pay($this->request(array_reverse($request->payments), strtoupper($request->sourceId)));
        $this->assertSame($result->snapshot, $replay->snapshot);
        $this->assertSame($result->id, $replay->id);
        $this->assertSame(1, Activity::where('event', 'disbursed')->count());
        DB::table('payment_methods')->update(['is_active' => true]);
        $this->expectException(ValidationException::class);
        app(ManagePaymentMethod::class)->delete($this->methods['TUNAI']);
    }

    public function test_under_over_zero_duplicate_and_changed_replay_rejected(): void
    {
        $source = strtolower((string) Str::ulid());
        foreach (['0', '100', '101', '-1', '100.1234567'] as $amount) {
            try {
                $this->pay($this->request([['method_id' => $this->methods['TUNAI'], 'amount' => $amount]], $source));
                $this->fail('Jumlah harus exact dan lunas.');
            } catch (InvalidArgumentException|ValidationException) {
                $this->assertDatabaseCount('payment_disbursements', 0);
            }
        }
        try {
            $this->pay($this->request([['method_id' => $this->methods['TUNAI'], 'amount' => '50'], ['method_id' => strtoupper($this->methods['TUNAI']), 'amount' => '50.123456']], $source));
            $this->fail('Metode duplikat ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('payment_disbursements', 0);
        }
        $this->pay($this->request(source: $source));
        $this->expectException(InvalidArgumentException::class);
        $this->pay($this->request([['method_id' => $this->methods['TUNAI'], 'amount' => '100.123456']], $source));
    }

    public function test_inactive_deleted_incompatible_masters_and_audit_veto_rollback(): void
    {
        foreach (['method', 'account', 'deleted', 'type', 'audit'] as $case) {
            DB::beginTransaction();
            try {
                if ($case === 'method') {
                    DB::table('payment_methods')->update(['is_active' => false]);
                }
                if ($case === 'account') {
                    DB::table('payment_accounts')->update(['is_active' => false]);
                }
                if ($case === 'deleted') {
                    DB::table('payment_methods')->update(['deleted_at' => now()]);
                }
                if ($case === 'type') {
                    DB::table('payment_accounts')->update(['type' => 'bank']);
                }
                if ($case === 'audit') {
                    Activity::creating(fn ($log) => $log->event === 'disbursed' ? false : null);
                }
                try {
                    $this->pay($this->request());
                    $this->fail('Master invalid/audit veto harus gagal.');
                } catch (InvalidArgumentException|RuntimeException) {
                    $this->assertDatabaseCount('payment_disbursements', 0);
                    $this->assertDatabaseCount('payment_disbursement_lines', 0);
                }
            } finally {
                Activity::flushEventListeners();
                DB::rollBack();
            }
        }
    }

    public function test_writer_requires_transaction(): void
    {
        $request = $this->request();
        $writer = app(PurchasePaymentWriter::class);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->expectException(LogicException::class);
        $writer->pay($request);
    }

    public function test_posted_schema_cannot_be_dropped(): void
    {
        $this->pay($this->request());
        $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_17_000005_create_payment_disbursements.php');
        $this->expectException(LogicException::class);
        $migration->down();
    }
}
