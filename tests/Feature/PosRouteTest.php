<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_pos(): void
    {
        $this->get('/pos')->assertRedirect('/login');
    }

    public function test_authorized_users_can_visit_pos_workspace(): void
    {
        $this->seed(POSAccessSeeder::class);
        $this->actingAs(User::factory()->create()->givePermissionTo('sales.pos.invoices.create'));

        $this->get('/pos')->assertOk();
    }
}
