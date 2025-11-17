<?php

namespace Tests\Feature\Auth;

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Logout Test
 * 
 * Tests user logout endpoint
 */
class LogoutTest extends TestCase
{
    /**
     * Test authenticated user can logout
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test-token')->plainTextToken;

        // Use token for authentication
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Verify token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token',
        ]);
    }

    /**
     * Test logout requires authentication
     */
    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * Test logout revokes current token only
     */
    public function test_logout_revokes_only_current_token(): void
    {
        $user = $this->createUser();
        $token1 = $user->createToken('token-1')->plainTextToken;
        $token2 = $user->createToken('token-2')->plainTextToken;

        // Logout with token1
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token1,
        ]);

        $response->assertStatus(200);

        // token1 should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'token-1',
        ]);

        // token2 should still exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'token-2',
        ]);
    }
}
