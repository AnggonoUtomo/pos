<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemImageTest extends TestCase
{
    use DatabaseMigrations;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutVite();
        foreach (['inventory.view', 'inventory.manage', 'sales.pos.create'] as $key) {
            Permission::findOrCreate($key, 'web');
        }
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        $unit = Unit::create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit']);
        $level = CustomerLevel::create(['code' => 'ECERAN', 'name' => 'Eceran']);
        $this->payload = ['sku' => 'CAM-01', 'name' => 'Kamera latihan', 'base_unit_id' => $unit->id, 'is_active' => true,
            'units' => [['unit_id' => $unit->id, 'conversion_ratio' => '1', 'barcode' => '899000000001']],
            'prices' => [['unit_id' => $unit->id, 'customer_level_id' => $level->id, 'amount' => '450000']]];
    }

    private function createImage(): array
    {
        $id = $this->postJson('/inventory/items', [...$this->payload, 'image' => UploadedFile::fake()->image('camera.png', 100, 80)])
            ->assertCreated()->json('id');
        $url = $this->getJson('/inventory/items/data')->assertOk()->json('data.0.image_url');
        $this->assertIsString($url);

        return [$id, $url];
    }

    public function test_upload_is_private_ulid_single_image_and_published_by_catalog(): void
    {
        [$id, $url] = $this->createImage();
        $item = Item::findOrFail($id);
        $media = $item->media()->sole();
        $this->assertTrue(Str::isUlid($media->id));
        $this->assertSame($id, $media->model_id);
        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        $this->assertStringNotContainsString('camera.png', $url);
        $this->assertStringNotContainsString('storage/', $url);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $catalog = app(SaleItemCatalog::class)->search('899000000001', 1)['data'];
        $this->assertSame($url, $catalog[0]->imageUrl);
        $this->assertSame('899000000001', $catalog[0]->units[0]->barcode);
        $this->assertDatabaseHas('item_prices', ['item_id' => $id, 'amount' => '450000.00']);
        $this->assertTrue(Activity::where('event', 'image.updated')->where('subject_id', $id)->exists());
    }

    public function test_multipart_method_spoof_replace_then_remove_cleans_old_files(): void
    {
        [$id, $oldUrl] = $this->createImage();
        $old = Item::findOrFail($id)->media()->sole();
        $this->post("/inventory/items/{$id}", [...$this->payload, '_method' => 'put',
            'image' => UploadedFile::fake()->image('replacement.jpg', 80, 100)], ['Accept' => 'application/json'])->assertOk();
        $media = Item::findOrFail($id)->media()->sole();
        $this->assertNotSame($old->id, $media->id);
        Storage::disk('local')->assertMissing($old->getPathRelativeToRoot());
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        $this->get($oldUrl)->assertNotFound();
        $this->putJson("/inventory/items/{$id}", [...$this->payload, 'remove_image' => true])->assertOk();
        $this->assertSame(0, Item::findOrFail($id)->media()->count());
        Storage::disk('local')->assertMissing($media->getPathRelativeToRoot());
        $this->getJson('/inventory/items/data')->assertJsonPath('data.0.image_url', null);
        $this->assertTrue(Activity::where('event', 'image.removed')->where('subject_id', $id)->exists());
    }

    public function test_omitted_image_and_soft_delete_restore_preserve_existing_file(): void
    {
        [$id, $url] = $this->createImage();
        $before = DB::table('activity_log')->count();
        $this->putJson("/inventory/items/{$id}", $this->payload)->assertOk();
        $this->assertSame($before, DB::table('activity_log')->count());
        $this->deleteJson("/inventory/items/{$id}")->assertOk();
        $this->get($url)->assertNotFound();
        $this->postJson("/inventory/items/{$id}/restore")->assertOk();
        $this->get($url)->assertOk();
        $this->assertSame(1, Item::findOrFail($id)->media()->count());
    }

    public function test_invalid_and_conflicting_uploads_leave_no_master_or_files(): void
    {
        foreach ([UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->create('too-large.jpg', 2049, 'image/jpeg'),
            UploadedFile::fake()->image('wide.png', 4097, 1),
            UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "not image";')] as $file) {
            $this->postJson('/inventory/items', [...$this->payload, 'image' => $file])->assertUnprocessable()->assertJsonValidationErrors('image');
        }
        $this->postJson('/inventory/items', [...$this->payload, 'remove_image' => true, 'image' => UploadedFile::fake()->image('valid.png')])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertDatabaseCount('items', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('item-images'));
    }

    public function test_access_requires_permission_and_matching_live_item_and_media(): void
    {
        [$id, $url] = $this->createImage();
        $viewer = User::factory()->create()->givePermissionTo('inventory.view');
        $cashier = User::factory()->create()->givePermissionTo('sales.pos.create');
        $outsider = User::factory()->create();
        $this->actingAs($viewer)->get($url)->assertOk();
        $this->postJson('/inventory/items', [...$this->payload, 'image' => UploadedFile::fake()->image('x.png')])->assertForbidden();
        $this->actingAs($cashier)->get($url)->assertOk();
        $this->actingAs($outsider)->get($url)->assertForbidden();
        $this->actingAs($viewer)->get(str_replace($id, (string) Str::ulid(), $url))->assertNotFound();
        auth()->forgetGuards();
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_failed_price_validation_preserves_previous_image_and_catalog(): void
    {
        [$id, $url] = $this->createImage();
        $files = Storage::disk('local')->allFiles('item-images');
        $before = DB::table('activity_log')->count();
        $this->putJson("/inventory/items/{$id}", [...$this->payload, 'name' => 'Must rollback', 'prices' => [],
            'image' => UploadedFile::fake()->image('new.png')])->assertUnprocessable()->assertJsonValidationErrors('prices');
        $this->assertSame('Kamera latihan', Item::findOrFail($id)->name);
        $this->assertSame($files, Storage::disk('local')->allFiles('item-images'));
        $this->assertSame($before, DB::table('activity_log')->count());
        $this->get($url)->assertOk();
    }

    public function test_audit_failure_rolls_back_replacement_and_removal_without_losing_old_file(): void
    {
        [$id, $url] = $this->createImage();
        $files = Storage::disk('local')->allFiles('item-images');
        $before = DB::table('activity_log')->count();
        Activity::creating(function ($activity): void {
            if (in_array($activity->event, ['image.updated', 'image.removed'], true)) {
                throw new \RuntimeException('Simulated audit failure');
            }
        });
        $this->withoutExceptionHandling();
        foreach ([['image' => UploadedFile::fake()->image('new.png')], ['remove_image' => true]] as $change) {
            try {
                $this->putJson("/inventory/items/{$id}", [...$this->payload, ...$change]);
                $this->fail('Audit failure must roll back.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Simulated audit failure', $exception->getMessage());
            }
            $this->assertSame($files, Storage::disk('local')->allFiles('item-images'));
            $this->assertSame(1, Item::findOrFail($id)->media()->count());
            $this->assertSame($before, DB::table('activity_log')->count());
            $this->get($url)->assertOk();
        }
    }

    public function test_failed_storage_rolls_back_new_catalog_and_does_not_expose_paths(): void
    {
        $disk = Storage::disk('local');
        $mock = \Mockery::mock($disk)->makePartial();
        $mock->shouldReceive('put')->andReturn(false);
        Storage::set('local', $mock);
        $before = DB::table('activity_log')->count();
        $this->postJson('/inventory/items', [...$this->payload, 'image' => UploadedFile::fake()->image('x.png')])
            ->assertUnprocessable()->assertJsonValidationErrors('image')->assertDontSee(storage_path());
        $this->assertDatabaseCount('items', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseCount('item_prices', 0);
        $this->assertSame($before, DB::table('activity_log')->count());
    }

    public function test_media_mutation_requires_top_level_transaction_and_missing_files_are_not_found(): void
    {
        [$id, $url] = $this->createImage();
        $media = Item::findOrFail($id)->media()->sole();
        Storage::disk('local')->delete($media->getPathRelativeToRoot());
        $this->get($url)->assertNotFound();
        $this->withoutExceptionHandling();
        DB::beginTransaction();
        try {
            $this->putJson("/inventory/items/{$id}", [...$this->payload, 'remove_image' => true]);
            $this->fail('Nested media mutation must be refused.');
        } catch (\LogicException $exception) {
            $this->assertSame('Mutation gambar harus memiliki transaction terluar.', $exception->getMessage());
        } finally {
            DB::rollBack();
        }
    }
}
