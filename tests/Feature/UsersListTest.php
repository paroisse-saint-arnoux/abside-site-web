<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_visits_record_the_last_connection_at_most_every_five_minutes(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $first = $admin->refresh()->last_seen_at;
        $this->assertNotNull($first);

        $this->travel(2)->minutes();
        $this->get('/admin');
        $this->assertTrue($first->equalTo($admin->refresh()->last_seen_at));

        $this->travel(4)->minutes();
        $this->get('/admin');
        $this->assertTrue($admin->refresh()->last_seen_at->gt($first));
    }
}
