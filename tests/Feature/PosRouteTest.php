<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_pos(): void
    {
        $this->get('/pos')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_pos_mock(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/pos')->assertOk();
    }
}
