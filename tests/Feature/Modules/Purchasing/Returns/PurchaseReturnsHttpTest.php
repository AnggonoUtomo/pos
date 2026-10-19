<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Models\User;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsAccessSeeder;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseReturnsHttpTest extends StockTestCase
{
    private const URL = '/purchasing/returns';

    private string $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app(ReturnsAccessSeeder::class)->run();
        $this->manager->givePermissionTo(['purchasing.returns.view', 'purchasing.returns.manage']);
        $this->supplier = Supplier::create(['code' => 'HTTP-RET', 'name' => 'Supplier CCTV 100%_!', 'phone' => 'private-contact', 'is_active' => true])->id;
    }

    private function received(): array
    {
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
        app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);

        return [$id, DB::table('purchase_order_lines')->where('purchase_order_id', $id)->value('id')];
    }

    private function input(string $purchaseId, string $lineId): array
    {
        return ['purchase_order_id' => $purchaseId, 'notes' => 'Catatan retur', 'lines' => [
            ['purchase_order_line_id' => $lineId, 'quantity' => '0.5'],
        ]];
    }

    public function test_manage_can_lookup_create_and_update_using_server_snapshot(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $this->getJson(self::URL.'/purchases?search='.urlencode('100%_!'))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.payment_snapshot')->assertJsonMissingPath('data.0.lines.0.total_cost');
        $this->getJson(self::URL.'/purchases/'.$purchaseId)->assertOk()->assertJsonPath('lines.0.unit_cost', '120000.000000');
        $payload = $this->input($purchaseId, $lineId) + ['id' => 'FORGED', 'number' => 'FORGED', 'status' => 'posted', 'snapshot' => ['forged' => true]];
        $id = $this->postJson(self::URL, $payload)->assertCreated()->json('id');
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'number' => 'PR-'.strtoupper($id), 'status' => 'draft', 'version' => 1]);
        $this->getJson(self::URL.'/data')->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.lines.0.base_quantity', '6.000000');

        $updated = $this->input($purchaseId, $lineId) + ['version' => 1];
        $updated['lines'][0]['quantity'] = '0.25';
        $this->putJson(self::URL.'/'.$id, $updated)->assertOk();
        $this->getJson(self::URL.'/data')->assertJsonPath('data.0.version', 2)->assertJsonPath('data.0.lines.0.base_quantity', '3.000000');
        $this->putJson(self::URL.'/'.$id, $updated)->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->getJson(self::URL)->assertMethodNotAllowed();
    }

    public function test_manage_can_delete_and_restore_only_through_current_draft_version(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $id = $this->postJson(self::URL, $this->input($purchaseId, $lineId))->assertCreated()->json('id');
        $lines = DB::table('purchase_return_lines')->where('purchase_return_id', $id)->get()->toJson();

        $this->deleteJson(self::URL.'/'.$id, ['version' => 2])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->deleteJson(self::URL.'/'.$id, ['version' => 1])->assertOk();
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 2]);
        $this->getJson(self::URL.'/data')->assertJsonPath('meta.total', 0);
        $this->getJson(self::URL.'/data?trashed=1')->assertJsonPath('data.0.version', 2);
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 2])->assertOk();
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 3, 'deleted_at' => null]);
        $this->assertSame($lines, DB::table('purchase_return_lines')->where('purchase_return_id', $id)->get()->toJson());
        $this->assertSame(['created', 'deleted', 'restored'], DB::table('activity_log')->where('log_name', 'purchasing.returns')->orderBy('id')->pluck('event')->all());
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 3])->assertNotFound();
        $this->deleteJson(self::URL.'/'.$id, ['version' => 3])->assertOk();
        $this->deleteJson(self::URL.'/'.$id, ['version' => 4])->assertNotFound();
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 4])->assertOk();
    }

    public function test_lifecycle_permissions_super_system_and_posted_invariant_are_enforced(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $id = $this->postJson(self::URL, $this->input($purchaseId, $lineId))->assertCreated()->json('id');

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('purchasing.returns.view');
        $this->actingAs($viewer);
        $this->deleteJson(self::URL.'/'.$id, ['version' => 1])->assertForbidden();
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 1])->assertForbidden();

        $this->actingAs($this->manager);
        DB::table('purchase_returns')->where('id', $id)->update(['status' => 'posted']);
        $this->deleteJson(self::URL.'/'.$id, ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('purchase_return');
        DB::table('purchase_returns')->where('id', $id)->update(['status' => 'draft', 'deleted_at' => now()]);
        $system = User::factory()->create();
        $system->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($system);
        DB::table('purchase_returns')->where('id', $id)->update(['status' => 'posted']);
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('purchase_return');
    }

    public function test_permissions_are_separate_and_source_not_found_is_safe(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('purchasing.returns.view');
        $this->actingAs($viewer);
        $this->getJson(self::URL.'/data')->assertOk();
        $this->getJson(self::URL.'/purchases')->assertForbidden();
        $this->postJson(self::URL, $this->input($purchaseId, $lineId))->assertForbidden();

        $manager = User::factory()->create();
        $manager->givePermissionTo('purchasing.returns.manage');
        $this->actingAs($manager);
        $this->getJson(self::URL.'/data')->assertForbidden();
        $this->getJson(self::URL.'/purchases/'.strtolower((string) Str::ulid()))->assertNotFound();
        $this->postJson(self::URL, $this->input($purchaseId, $lineId))->assertCreated();

        $superSystem = User::factory()->create();
        $superSystem->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($superSystem);
        $invalid = $this->input($purchaseId, $lineId);
        $invalid['lines'][0]['quantity'] = '3';
        $this->postJson(self::URL, $invalid)->assertUnprocessable()->assertJsonValidationErrors('lines.0.quantity');
    }

    public function test_guest_and_lookup_validation_are_enforced(): void
    {
        [$purchaseId] = $this->received();
        auth()->logout();
        $this->getJson(self::URL.'/data')->assertUnauthorized();
        $this->postJson(self::URL, [])->assertUnauthorized();
        $this->actingAs($this->manager);
        $this->getJson(self::URL.'/purchases?search='.str_repeat('x', 151))->assertUnprocessable();
        $this->getJson(self::URL.'/purchases/not-a-ulid')->assertNotFound();
        $this->getJson(self::URL.'/purchases/'.strtolower((string) Str::ulid()))->assertNotFound();

        $lineId = DB::table('purchase_order_lines')->where('purchase_order_id', $purchaseId)->value('id');
        $payload = $this->input($purchaseId, $lineId);
        $payload['lines'][0]['unit_cost'] = '1';
        $this->postJson(self::URL, $payload)->assertUnprocessable()->assertJsonValidationErrors('lines.0');
    }

    public function test_draft_data_search_is_literal_and_pagination_is_bounded(): void
    {
        [$purchaseId, $lineId] = $this->received();
        for ($index = 0; $index < 26; $index++) {
            $input = $this->input($purchaseId, $lineId);
            $input['notes'] = 'QA-'.$index;
            app(ManagePurchaseReturnDraft::class)->save($input);
        }

        $this->getJson(self::URL.'/data')->assertOk()->assertJsonCount(25, 'data')->assertJsonPath('meta.total', 26);
        $this->getJson(self::URL.'/data?page=2')->assertJsonCount(1, 'data');
        $this->getJson(self::URL.'/data?search='.urlencode('%_!'))->assertJsonPath('meta.total', 26);
        $this->getJson(self::URL.'/data?page=0')->assertUnprocessable();
        $this->getJson(self::URL.'/purchases?search='.str_repeat('x', 151))->assertUnprocessable();
    }

    public function test_access_catalog_seeder_does_not_grant_permissions(): void
    {
        $user = User::factory()->create();
        $before = DB::table('model_has_permissions')->where('model_id', $user->id)->count();
        app(ReturnsAccessSeeder::class)->run();

        $this->assertSame($before, DB::table('model_has_permissions')->where('model_id', $user->id)->count());
        $this->assertSame(2, DB::table('permissions')->whereIn('name', ['purchasing.returns.view', 'purchasing.returns.manage'])->count());
        $this->assertFalse($user->can('purchasing.returns.view'));
        $this->assertFalse($user->can('purchasing.returns.manage'));
    }
}
