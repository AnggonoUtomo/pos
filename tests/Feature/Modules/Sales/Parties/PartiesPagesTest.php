<?php

namespace Tests\Feature\Modules\Sales\Parties;

use App\Models\User;
use App\Modules\Sales\Parties\Database\Seeders\PartiesAccessSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PartiesPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_inertia_pages_filters_pagination_and_active_level_choices(): void
    {
        $this->seed([PartiesAccessSeeder::class, PartiesDemoSeeder::class]);
        $user = User::factory()->create();
        $user->givePermissionTo('sales.parties.view');
        $this->actingAs($user);
        CustomerLevel::create(['code' => 'INACTIVE', 'name' => 'Inactive', 'is_active' => false]);
        CustomerLevel::create(['code' => 'DELETED', 'name' => 'Deleted'])->delete();
        $this->get('/sales/customers?search=Installer')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('sales/parties/index')->where('records.meta.total', 1)->where('records.data.0.code', 'DEMO-INSTALLER')
            ->has('levelOptions', 3)->where('filters.search', 'Installer'));
        $this->get('/sales/customer-levels?trashed=1')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('sales/parties/customer-levels')->where('records.meta.total', 1)->where('records.data.0.code', 'DELETED'));
        $this->post('/sales/customers', [])->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/sales/customers')->assertForbidden();
        $this->get('/sales/customer-levels')->assertForbidden();
    }

    public function test_web_mutation_redirects_success_and_duplicate_validation(): void
    {
        $this->seed(PartiesAccessSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['sales.parties.view', 'sales.parties.manage']);
        $this->actingAs($user)->from('/sales/customer-levels')->post('/sales/customer-levels', ['code' => 'ECERAN', 'name' => 'Eceran'])
            ->assertRedirect('/sales/customer-levels')->assertSessionHas('success');
        $this->from('/sales/customer-levels')->post('/sales/customer-levels', ['code' => 'ECERAN', 'name' => 'Duplicate'])
            ->assertRedirect('/sales/customer-levels')->assertSessionHasErrors('code');
    }
}
