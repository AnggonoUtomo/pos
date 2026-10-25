<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_redirects_directly_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->component('auth/login'));
    }

    public function test_authenticated_home_redirects_to_existing_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
    }
}
