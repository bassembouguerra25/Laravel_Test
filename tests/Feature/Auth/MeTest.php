<?php

namespace Tests\Feature\Auth;

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Me Test
 * 
 * Tests authenticated user information endpoint
 */
class MeTest extends TestCase
{
    /**
     * Test authenticated user can get their information
     */
    public function test_authenticated_user_can_get_their_information(): void
    {
        $user = $this->createUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'customer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'role' => 'customer',
                    ],
                ],
            ]);
    }

    /**
     * Test me endpoint requires authentication
     */
    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    /**
     * Test me returns correct user role
     */
    public function test_me_returns_correct_user_role(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'admin');

        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'organizer');
    }
}
