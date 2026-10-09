<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ItemsActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_and_unit_lifecycle_records_actor_subject_and_only_changes(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        $id = app(CreateItem::class)->handle(new CreateItemData('A', 'Barang A', $pcs));
        $item = Item::query()->findOrFail($id);
        $relation = $item->units()->sole();

        foreach ([$item, $relation] as $subject) {
            $created = Activity::query()->where('subject_id', $subject->id)->sole();
            $this->assertTrue($created->subject->is($subject));
            $this->assertTrue($created->causer->is($actor));
        }

        $item->update(['name' => 'Barang User', 'is_active' => false]);
        $relation->update(['barcode' => 'USER-BARCODE', 'is_sale_unit' => false]);
        $updated = Activity::query()->where('subject_id', $item->id)->where('event', 'updated')->sole();
        $this->assertSame('Barang A', $updated->attribute_changes['old']['name']);
        $this->assertSame('Barang User', $updated->attribute_changes['attributes']['name']);
        foreach ([$item, $relation] as $subject) {
            $subject->save();
            $this->assertSame(2, Activity::query()->where('subject_id', $subject->id)->count());
            $subject->delete();
            $subject->restore();
            $this->assertSame(['created', 'updated', 'deleted', 'restored'], Activity::query()->where('subject_id', $subject->id)->orderBy('id')->pluck('event')->all());
        }
    }
}
