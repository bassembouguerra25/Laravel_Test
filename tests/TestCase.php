<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates an authenticated user for testing
     */
    protected function createUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Creates an authenticated admin for testing
     */
    protected function createAdmin(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->admin()->create($attributes);
    }

    /**
     * Creates an authenticated organizer for testing
     */
    protected function createOrganizer(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->organizer()->create($attributes);
    }

    /**
     * Creates an authenticated customer for testing
     */
    protected function createCustomer(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->customer()->create($attributes);
    }
}
