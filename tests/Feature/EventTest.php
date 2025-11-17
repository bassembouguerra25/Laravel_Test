<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Event Test
 * 
 * Tests Events CRUD operations and authorization
 */
class EventTest extends TestCase
{
    /**
     * Test admin can create event
     */
    public function test_admin_can_create_event(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/events', [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'date' => now()->addDays(30)->toISOString(),
            'location' => 'Test Location',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'date',
                    'location',
                    'created_by',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Event created successfully',
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Test Event',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * Test organizer can create event
     */
    public function test_organizer_can_create_event(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $response = $this->postJson('/api/events', [
            'title' => 'Organizer Event',
            'description' => 'Organizer Description',
            'date' => now()->addDays(30)->toISOString(),
            'location' => 'Organizer Location',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('events', [
            'title' => 'Organizer Event',
            'created_by' => $organizer->id,
        ]);
    }

    /**
     * Test customer cannot create event
     */
    public function test_customer_cannot_create_event(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/events', [
            'title' => 'Customer Event',
            'description' => 'Customer Description',
            'date' => now()->addDays(30)->toISOString(),
            'location' => 'Customer Location',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can view all events
     */
    public function test_authenticated_user_can_view_all_events(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        Event::factory()->count(3)->create();

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'events',
                    'pagination',
                ],
            ]);

        $this->assertCount(3, $response->json('data.events'));
    }

    /**
     * Test organizer only sees their own events in index
     */
    public function test_organizer_only_sees_their_own_events(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();

        Event::factory()->count(2)->forOrganizer($organizer1)->create();
        Event::factory()->count(3)->forOrganizer($organizer2)->create();

        Sanctum::actingAs($organizer1);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.events'));
    }

    /**
     * Test admin sees all events in index
     */
    public function test_admin_sees_all_events(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();

        Event::factory()->count(2)->forOrganizer($organizer)->create();
        Event::factory()->count(3)->forOrganizer($organizer)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data.events'));
    }

    /**
     * Test can view single event
     */
    public function test_can_view_single_event(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $event = Event::factory()->create();

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'date',
                    'location',
                ],
            ])
            ->assertJsonPath('data.id', $event->id);
    }

    /**
     * Test organizer can update their own event
     */
    public function test_organizer_can_update_their_own_event(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create([
            'title' => 'Original Title',
        ]);

        $response = $this->putJson("/api/events/{$event->id}", [
            'title' => 'Updated Title',
            'date' => now()->addDays(30)->toISOString(),
            'location' => $event->location,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * Test organizer cannot update other organizer's event
     */
    public function test_organizer_cannot_update_other_organizer_event(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer1)->create();

        Sanctum::actingAs($organizer2);

        $response = $this->putJson("/api/events/{$event->id}", [
            'title' => 'Unauthorized Update',
            'date' => now()->addDays(30)->toISOString(),
            'location' => $event->location,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test admin can update any event
     */
    public function test_admin_can_update_any_event(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/events/{$event->id}", [
            'title' => 'Admin Updated Title',
            'date' => now()->addDays(30)->toISOString(),
            'location' => $event->location,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Admin Updated Title');
    }

    /**
     * Test organizer can delete their own event
     */
    public function test_organizer_can_delete_their_own_event(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();

        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event deleted successfully',
            ]);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * Test organizer cannot delete other organizer's event
     */
    public function test_organizer_cannot_delete_other_organizer_event(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer1)->create();

        Sanctum::actingAs($organizer2);

        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403);
    }

    /**
     * Test admin can delete any event
     */
    public function test_admin_can_delete_any_event(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer)->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * Test event creation requires future date
     */
    public function test_event_creation_requires_future_date(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/events', [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'date' => now()->subDays(1)->toISOString(),
            'location' => 'Test Location',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * Test event search functionality
     */
    public function test_event_search_functionality(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        Event::factory()->create(['title' => 'Concert Event', 'location' => 'Paris']);
        Event::factory()->create(['title' => 'Conference Event', 'location' => 'London']);
        Event::factory()->create(['title' => 'Workshop', 'location' => 'Berlin']);

        $response = $this->getJson('/api/events?search=Concert');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.events'));
        $this->assertEquals('Concert Event', $response->json('data.events.0.title'));
    }

    /**
     * Test events list is cached
     */
    public function test_events_list_is_cached(): void
    {
        Cache::flush();

        $user = $this->createUser();
        Sanctum::actingAs($user);

        Event::factory()->count(3)->create();

        // First request - should query database and cache
        $response1 = $this->getJson('/api/events');
        $response1->assertStatus(200);
        $this->assertCount(3, $response1->json('data.events'));

        // Second request - should use cache (no new events created)
        $response2 = $this->getJson('/api/events');
        $response2->assertStatus(200);
        $this->assertCount(3, $response2->json('data.events'));

        // Verify cache was used (events count should be same even if we add new events)
        Event::factory()->create(); // This won't appear until cache expires

        $response3 = $this->getJson('/api/events');
        $response3->assertStatus(200);
        $this->assertCount(3, $response3->json('data.events')); // Still 3 from cache
    }

    /**
     * Test cache is cleared when event is created
     */
    public function test_cache_cleared_when_event_created(): void
    {
        Cache::flush();

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        Event::factory()->count(2)->create();

        // Request events to populate cache
        $this->getJson('/api/events');

        // Create new event - should clear cache
        $this->postJson('/api/events', [
            'title' => 'New Event',
            'description' => 'New Description',
            'date' => now()->addDays(30)->toISOString(),
            'location' => 'New Location',
        ]);

        // Verify cache was cleared by checking if new event appears
        $response = $this->getJson('/api/events');
        $this->assertGreaterThanOrEqual(3, $response->json('data.events')); // At least 3 events (2 + 1 new)
    }

    /**
     * Test cache is cleared when event is updated
     */
    public function test_cache_cleared_when_event_updated(): void
    {
        Cache::flush();

        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create([
            'title' => 'Original Title',
        ]);

        // Request events to populate cache
        $this->getJson('/api/events');

        // Update event - should clear cache
        $this->putJson("/api/events/{$event->id}", [
            'title' => 'Updated Title',
            'date' => now()->addDays(30)->toISOString(),
            'location' => $event->location,
        ]);

        // Verify cache was cleared by checking if updated event appears
        $response = $this->getJson('/api/events');
        $this->assertStringContainsString('Updated Title', json_encode($response->json('data.events')));
    }

    /**
     * Test cache is cleared when event is deleted
     */
    public function test_cache_cleared_when_event_deleted(): void
    {
        Cache::flush();

        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();

        // Request events to populate cache
        $response1 = $this->getJson('/api/events');
        $initialCount = count($response1->json('data.events'));

        // Delete event - should clear cache
        $this->deleteJson("/api/events/{$event->id}");

        // Verify cache was cleared by checking if event count decreased
        $response2 = $this->getJson('/api/events');
        $this->assertLessThan($initialCount, count($response2->json('data.events')));
    }
}
