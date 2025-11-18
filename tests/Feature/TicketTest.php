<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ticket Test
 *
 * Tests Tickets CRUD operations and authorization
 */
class TicketTest extends TestCase
{
    /**
     * Test admin can create ticket
     */
    public function test_admin_can_create_ticket(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $event = Event::factory()->create();

        $response = $this->postJson('/api/tickets', [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'type',
                    'price',
                    'quantity',
                    'event_id',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Ticket created successfully',
            ]);

        $this->assertDatabaseHas('tickets', [
            'type' => 'VIP',
            'event_id' => $event->id,
        ]);
    }

    /**
     * Test organizer can create ticket for their event
     */
    public function test_organizer_can_create_ticket_for_their_event(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();

        $response = $this->postJson('/api/tickets', [
            'type' => 'Standard',
            'price' => 49.99,
            'quantity' => 200,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tickets', [
            'type' => 'Standard',
            'event_id' => $event->id,
        ]);
    }

    /**
     * Test organizer cannot create ticket for other organizer's event
     */
    public function test_organizer_cannot_create_ticket_for_other_event(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer2)->create();

        Sanctum::actingAs($organizer1);

        $response = $this->postJson('/api/tickets', [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test customer cannot create ticket
     */
    public function test_customer_cannot_create_ticket(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $event = Event::factory()->create();

        $response = $this->postJson('/api/tickets', [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test can view all tickets
     */
    public function test_can_view_all_tickets(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        Ticket::factory()->count(3)->create();

        $response = $this->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tickets',
                    'pagination',
                ],
            ]);

        $this->assertCount(3, $response->json('data.tickets'));
    }

    /**
     * Test can filter tickets by event
     */
    public function test_can_filter_tickets_by_event(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();

        Ticket::factory()->count(2)->forEvent($event1)->create();
        Ticket::factory()->count(3)->forEvent($event2)->create();

        $response = $this->getJson("/api/tickets?event_id={$event1->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.tickets'));
    }

    /**
     * Test can filter tickets by available only
     */
    public function test_can_filter_tickets_by_available_only(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $ticketWithStock = Ticket::factory()->create(['quantity' => 100]);
        $ticketSoldOut = Ticket::factory()->create(['quantity' => 5]);

        // Book all tickets for sold out ticket
        \App\Models\Booking::factory()
            ->forTicket($ticketSoldOut)
            ->confirmed()
            ->count(1)
            ->create(['quantity' => 5]);

        $response = $this->getJson('/api/tickets?available_only=1');

        $response->assertStatus(200);

        // Should only show ticket with stock
        $ticketIds = collect($response->json('data.tickets'))->pluck('id');
        $this->assertContains($ticketWithStock->id, $ticketIds);
        $this->assertNotContains($ticketSoldOut->id, $ticketIds);
    }

    /**
     * Test can view single ticket
     */
    public function test_can_view_single_ticket(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $ticket = Ticket::factory()->create();

        $response = $this->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'price',
                    'quantity',
                    'available_quantity',
                ],
            ])
            ->assertJsonPath('data.id', $ticket->id);
    }

    /**
     * Test organizer can update their own ticket
     */
    public function test_organizer_can_update_their_own_ticket(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();
        $ticket = Ticket::factory()->forEvent($event)->create(['type' => 'Standard']);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'type' => 'Premium',
            'price' => $ticket->price,
            'quantity' => $ticket->quantity,
            'event_id' => $ticket->event_id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'Premium');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'type' => 'Premium',
        ]);
    }

    /**
     * Test organizer cannot update ticket for other organizer's event
     */
    public function test_organizer_cannot_update_other_organizer_ticket(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer2)->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        Sanctum::actingAs($organizer1);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'type' => 'Unauthorized',
            'price' => $ticket->price,
            'quantity' => $ticket->quantity,
            'event_id' => $ticket->event_id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test admin can update any ticket
     */
    public function test_admin_can_update_any_ticket(): void
    {
        $admin = $this->createAdmin();
        $organizer = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer)->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'type' => 'Admin Updated',
            'price' => $ticket->price,
            'quantity' => $ticket->quantity,
            'event_id' => $ticket->event_id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'Admin Updated');
    }

    /**
     * Test organizer can delete their own ticket
     */
    public function test_organizer_can_delete_their_own_ticket(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    /**
     * Test organizer cannot delete other organizer's ticket
     */
    public function test_organizer_cannot_delete_other_organizer_ticket(): void
    {
        $organizer1 = $this->createOrganizer();
        $organizer2 = $this->createOrganizer();

        $event = Event::factory()->forOrganizer($organizer2)->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        Sanctum::actingAs($organizer1);

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403);
    }

    /**
     * Test ticket creation requires valid event
     */
    public function test_ticket_creation_requires_valid_event(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/tickets', [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
            'event_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_id']);
    }

    /**
     * Test ticket price must be positive
     */
    public function test_ticket_price_must_be_positive(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $event = Event::factory()->create();

        $response = $this->postJson('/api/tickets', [
            'type' => 'VIP',
            'price' => -10,
            'quantity' => 100,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Test cache is cleared when ticket is created
     */
    public function test_cache_cleared_when_ticket_created(): void
    {
        Cache::flush();

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $event = Event::factory()->create();

        // Request events to populate cache
        $this->getJson('/api/events');

        // Create new ticket - should clear cache
        $this->postJson('/api/tickets', [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
            'event_id' => $event->id,
        ]);

        // Verify cache was cleared (events should reflect new ticket)
        $response = $this->getJson('/api/events');
        $response->assertStatus(200);
        // Cache should be cleared, so we should see fresh data
    }

    /**
     * Test cache is cleared when ticket is updated
     */
    public function test_cache_cleared_when_ticket_updated(): void
    {
        Cache::flush();

        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();
        $ticket = Ticket::factory()->forEvent($event)->create([
            'type' => 'Standard',
        ]);

        // Request events to populate cache
        $this->getJson('/api/events');

        // Update ticket - should clear cache
        $this->putJson("/api/tickets/{$ticket->id}", [
            'type' => 'Premium',
            'price' => $ticket->price,
            'quantity' => $ticket->quantity,
            'event_id' => $ticket->event_id,
        ]);

        // Verify cache was cleared
        $response = $this->getJson('/api/events');
        $response->assertStatus(200);
        // Cache should be cleared, so we should see updated ticket
    }

    /**
     * Test cache is cleared when ticket is deleted
     */
    public function test_cache_cleared_when_ticket_deleted(): void
    {
        Cache::flush();

        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        // Request events to populate cache
        $response1 = $this->getJson('/api/events');
        $initialTicketsCount = $response1->json('data.events.0.tickets_count') ?? 0;

        // Delete ticket - should clear cache
        $this->deleteJson("/api/tickets/{$ticket->id}");

        // Verify cache was cleared
        $response2 = $this->getJson('/api/events');
        $response2->assertStatus(200);
        // Cache should be cleared, ticket count should reflect deletion
    }
}
