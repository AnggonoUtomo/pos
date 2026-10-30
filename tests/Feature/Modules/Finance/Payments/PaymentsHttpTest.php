<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PaymentsAccessSeeder::class, PaymentsDemoSeeder::class]);
        $this->actingAs(User::factory()->create()->givePermissionTo(['finance.payments.view', 'finance.payments.manage']));
    }

    public function test_guest_viewer_manager_and_super_system_permission_matrix(): void
    {
        $viewer = User::factory()->create()->givePermissionTo('finance.payments.view');
        $this->actingAs($viewer)->getJson('/finance/payment-accounts/data')->assertOk();
        $this->getJson('/finance/payment-methods/data')->assertOk();
        foreach (['payment-accounts', 'payment-methods'] as $path) {
            $this->postJson('/finance/'.$path, [])->assertForbidden();
        }
        $this->actingAs(User::factory()->create())->getJson('/finance/payment-accounts/data')->assertForbidden();
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')))
            ->postJson('/finance/payment-accounts', ['code' => 'SYS', 'name' => 'Kas', 'type' => 'cash'])->assertCreated();
        auth()->logout();
        $this->getJson('/finance/payment-accounts/data')->assertUnauthorized();
        $this->postJson('/finance/payment-methods', [])->assertUnauthorized();
    }

    public function test_account_crud_normalizes_and_reserves_deleted_code(): void
    {
        $payload = ['code' => ' QA ', 'name' => ' Kas QA ', 'type' => 'cash', 'is_active' => true, 'balance' => '999999'];
        $id = $this->postJson('/finance/payment-accounts', $payload)->assertCreated()->json('id');
        $this->assertDatabaseHas('payment_accounts', ['id' => $id, 'code' => 'QA', 'name' => 'Kas QA']);
        $this->putJson('/finance/payment-accounts/'.$id, ['code' => 'QA', 'name' => 'Kas Ubah', 'type' => 'cash', 'is_active' => false])->assertOk();
        $this->deleteJson('/finance/payment-accounts/'.$id)->assertOk();
        $this->postJson('/finance/payment-accounts', $payload)->assertUnprocessable()->assertJsonValidationErrors('code')
            ->assertJsonPath('errors.code.0', 'Kode sudah digunakan, termasuk pada data terhapus.');
        $this->putJson('/finance/payment-accounts/'.$id, $payload)->assertNotFound();
        $this->postJson('/finance/payment-accounts/'.$id.'/restore')->assertOk();
        $this->assertDatabaseHas('payment_accounts', ['id' => $id, 'is_active' => false, 'deleted_at' => null]);
        $this->postJson('/finance/payment-accounts/'.$id.'/restore')->assertNotFound();
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], DB::table('activity_log')->where('subject_id', $id)->orderBy('id')->pluck('event')->all());
    }

    public function test_method_crud_syncs_real_account_relation_and_restore(): void
    {
        $bank = DB::table('payment_accounts')->where('type', 'bank')->value('id');
        $payload = ['code' => ' QA ', 'name' => ' Debit QA ', 'type' => 'debit', 'payment_account_id' => strtoupper($bank), 'is_active' => true];
        $id = $this->postJson('/finance/payment-methods', $payload)->assertCreated()->json('id');
        $this->getJson('/finance/payment-methods/data')->assertOk()->assertJsonFragment(['id' => $id, 'payment_account_id' => $bank, 'account_name' => 'Bank Operasional Demo']);
        $payload['name'] = 'Transfer QA';
        $payload['type'] = 'transfer';
        $this->putJson('/finance/payment-methods/'.$id, $payload)->assertOk();
        $this->deleteJson('/finance/payment-methods/'.$id)->assertOk();
        $this->assertSoftDeleted('payment_methods', ['id' => $id]);
        $this->postJson('/finance/payment-methods/'.$id.'/restore')->assertOk();
        $this->assertDatabaseHas('payment_methods', ['id' => $id, 'deleted_at' => null, 'type' => 'transfer']);
    }

    public function test_invariants_cannot_be_bypassed_by_super_system_and_fail_without_audit(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        $cash = DB::table('payment_accounts')->where('type', 'cash')->value('id');
        $before = DB::table('activity_log')->count();
        $this->deleteJson('/finance/payment-accounts/'.$cash)->assertUnprocessable()->assertJsonValidationErrors('payments');
        $this->putJson('/finance/payment-accounts/'.$cash, ['code' => 'CASH-DEMO', 'name' => 'Kas Toko Demo', 'type' => 'cash', 'is_active' => false])->assertUnprocessable();
        $this->postJson('/finance/payment-methods', ['code' => 'BAD', 'name' => 'Bad', 'type' => 'transfer', 'payment_account_id' => $cash])->assertUnprocessable()->assertJsonValidationErrors('payment_account_id');
        $this->assertSame($before, DB::table('activity_log')->count());
    }

    public function test_account_bank_fields_are_conditional_and_leading_zeros_are_preserved(): void
    {
        $payload = ['code' => 'BANK-QA', 'name' => 'Bank QA', 'type' => 'bank', 'bank_name' => 'Bank Contoh', 'account_number' => '00000042', 'account_holder' => 'Pemilik Contoh'];
        $id = $this->postJson('/finance/payment-accounts', $payload)->assertCreated()->json('id');
        $this->assertDatabaseHas('payment_accounts', ['id' => $id, 'account_number' => '00000042']);
        $payload['code'] = 'CASH-QA';
        $payload['type'] = 'cash';
        $this->postJson('/finance/payment-accounts', $payload)->assertUnprocessable()->assertJsonValidationErrors(['bank_name', 'account_number', 'account_holder']);
        $this->assertStringNotContainsString('00000042', DB::table('activity_log')->get()->toJson());
    }

    public function test_filters_pagination_and_invalid_query_validation(): void
    {
        $this->getJson('/finance/payment-accounts/data?type=bank&search=BANK')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 10);
        $cash = DB::table('payment_accounts')->where('type', 'cash')->value('id');
        $this->getJson('/finance/payment-methods/data?payment_account_id='.$cash)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/finance/payment-methods/data?type=debit')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/finance/payment-methods/data?page=0&status=bad&type=other')->assertUnprocessable()->assertJsonValidationErrors(['page', 'status', 'type']);
        $this->getJson('/finance/payment-accounts/data?type=debit')->assertUnprocessable();
        $this->deleteJson('/finance/payment-accounts/not-ulid')->assertNotFound();
        $this->deleteJson('/finance/payment-methods/01arz3ndektsv4rrffq69g5fav')->assertNotFound();
    }

    public function test_all_mutations_reject_viewer_without_changing_data_or_audit(): void
    {
        $this->actingAs(User::factory()->create()->givePermissionTo('finance.payments.view'));
        $before = DB::table('activity_log')->count();
        foreach (['payment-accounts' => 'payment_accounts', 'payment-methods' => 'payment_methods'] as $path => $table) {
            $id = DB::table($table)->value('id');
            $this->postJson('/finance/'.$path, [])->assertForbidden();
            $this->putJson('/finance/'.$path.'/'.$id, [])->assertForbidden();
            $this->deleteJson('/finance/'.$path.'/'.$id)->assertForbidden();
            $this->postJson('/finance/'.$path.'/'.$id.'/restore')->assertForbidden();
            $this->assertDatabaseHas($table, ['id' => $id, 'deleted_at' => null]);
        }
        $this->assertSame($before, DB::table('activity_log')->count());
    }

    public function test_inertia_pages_publish_scoped_filters_and_only_active_account_choices(): void
    {
        $this->postJson('/finance/payment-accounts', ['code' => 'OFF', 'name' => 'Kas nonaktif', 'type' => 'cash', 'is_active' => false])->assertCreated();
        $deleted = $this->postJson('/finance/payment-accounts', ['code' => 'DEL', 'name' => 'Kas terhapus', 'type' => 'cash'])->assertCreated()->json('id');
        $this->deleteJson('/finance/payment-accounts/'.$deleted)->assertOk();
        $this->get('/finance/payment-accounts?type=bank')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('finance/payments/accounts')->where('filters.type', 'bank')->has('records.data', 1));
        $this->get('/finance/payment-methods')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('finance/payments/methods')->has('records.data', 3)->has('accountOptions', 2)
            ->where('accountOptions.0.code', 'BANK-DEMO')->missing('accountOptions.0.account_number'));
        $this->putJson('/finance/payment-accounts/'.$deleted, ['code' => 'DEL', 'name' => 'Nama', 'type' => 'cash'])
            ->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_method_deleted_code_is_reserved_and_unknown_accounts_are_validation_errors(): void
    {
        $cash = DB::table('payment_accounts')->where('type', 'cash')->value('id');
        $payload = ['code' => 'RESERVED', 'name' => 'Tunai QA', 'type' => 'cash', 'payment_account_id' => $cash];
        $id = $this->postJson('/finance/payment-methods', $payload)->assertCreated()->json('id');
        $this->deleteJson('/finance/payment-methods/'.$id)->assertOk();
        $before = DB::table('activity_log')->count();
        $this->postJson('/finance/payment-methods', $payload)->assertUnprocessable()->assertJsonValidationErrors('code')
            ->assertJsonPath('errors.code.0', 'Kode sudah digunakan, termasuk pada data terhapus.');
        $payload['code'] = 'UNKNOWN';
        $payload['payment_account_id'] = '01arz3ndektsv4rrffq69g5fav';
        $this->postJson('/finance/payment-methods', $payload)->assertUnprocessable()->assertJsonValidationErrors('payment_account_id');
        $this->assertSame($before, DB::table('activity_log')->count());
    }
}
