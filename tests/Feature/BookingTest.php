<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Booking Test
 *
 * Tests Bookings CRUD operations, business logic, and authorization
 */
class BookingTest extends TestCase
{
    /**
     * Test customer can create booking
     */
    public function test_customer_can_create_booking(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $ticket = Ticket::factory()->create(['quantity' => 100]);

        $response = $this->postJson('/api/bookings', [
            'ticket_id' => $ticket->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'ticket_id',
                    'quantity',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'status' => 'pending',
                    'user_id' => $customer->id,
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    /**
     * Test booking creation fails when exceeding available quantity
     */
    public function test_booking_creation_fails_when_exceeding_available_quantity(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $ticket = Ticket::factory()->create(['quantity' => 5]);

        // Book all available tickets
        Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 5]);

        $response = $this->postJson('/api/bookings', [
            'ticket_id' => $ticket->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test booking creation fails for past events
     */
    public function test_booking_creation_fails_for_past_events(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        // Create event with explicit past date
        $pastDate = now()->subDays(2);
        $event = Event::factory()->create([
            'date' => $pastDate,
        ]);
        $ticket = Ticket::factory()->forEvent($event)->create();

        $response = $this->postJson('/api/bookings', [
            'ticket_id' => $ticket->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_id']);
    }

    /**
     * Test customer cannot create double booking for same ticket
     */
    public function test_customer_cannot_create_double_booking(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $ticket = Ticket::factory()->create(['quantity' => 100]);

        // Create first booking
        Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->pending()
            ->create();

        // Try to create another booking for same ticket
        $response = $this->postJson('/api/bookings', [
            'ticket_id' => $ticket->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_id']);
    }

    /**
     * Test customer can view their own bookings
     */
    public function test_customer_can_view_their_own_bookings(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        Booking::factory()
            ->forUser($customer)
            ->count(3)
            ->create();

        Booking::factory()
            ->count(2)
            ->create(); // Other customers' bookings

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'bookings',
                    'pagination',
                ],
            ]);

        // Should only see customer's own bookings
        $this->assertCount(3, $response->json('data.bookings'));
    }

    /**
     * Test organizer can view bookings for their events
     */
    public function test_organizer_can_view_bookings_for_their_events(): void
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);

        $event = Event::factory()->forOrganizer($organizer)->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        Booking::factory()
            ->forTicket($ticket)
            ->count(2)
            ->create();

        $otherEvent = Event::factory()->create();
        $otherTicket = Ticket::factory()->forEvent($otherEvent)->create();

        Booking::factory()
            ->forTicket($otherTicket)
            ->count(3)
            ->create();

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200);
        // Should only see bookings for organizer's events
        $this->assertCount(2, $response->json('data.bookings'));
    }

    /**
     * Test admin can view all bookings
     */
    public function test_admin_can_view_all_bookings(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        Booking::factory()->count(5)->create();

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data.bookings'));
    }

    /**
     * Test customer can view their own booking
     */
    public function test_customer_can_view_their_own_booking(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $booking = Booking::factory()->forUser($customer)->create();

        $response = $this->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'user_id',
                    'ticket_id',
                    'quantity',
                    'status',
                ],
            ])
            ->assertJsonPath('data.id', $booking->id);
    }

    /**
     * Test customer cannot view other customers' bookings
     */
    public function test_customer_cannot_view_other_customers_bookings(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();

        $booking = Booking::factory()->forUser($customer2)->create();

        Sanctum::actingAs($customer1);

        $response = $this->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403);
    }

    /**
     * Test confirming booking creates payment
     */
    public function test_confirming_booking_creates_payment(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $ticket = Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        // Payment should be created
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 100.00, // 50.00 * 2
            'status' => 'success',
        ]);
    }

    /**
     * Test confirming booking sends notification to customer
     */
    public function test_confirming_booking_sends_notification(): void
    {
        Notification::fake();

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $customer = $this->createCustomer();
        $ticket = Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);

        // Verify notification was sent to customer
        Notification::assertSentTo(
            $customer,
            BookingConfirmedNotification::class,
            function ($notification, $channels) use ($booking) {
                return $notification->booking->id === $booking->id;
            }
        );
    }

    /**
     * Test notification implements ShouldQueue (is queued)
     */
    public function test_notification_implements_should_queue(): void
    {
        $booking = Booking::factory()->create();
        $notification = new BookingConfirmedNotification($booking);

        // Verify notification implements ShouldQueue interface
        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }

    /**
     * Test notification is queued (not sent immediately)
     */
    public function test_notification_is_queued(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $customer = $this->createCustomer();
        $ticket = Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);

        // Verify notification job was pushed to queue (asynchronous)
        Queue::assertPushed(\Illuminate\Notifications\SendQueuedNotifications::class, function ($job) use ($booking) {
            $notification = $job->notification;

            return $notification instanceof BookingConfirmedNotification
                && $notification->booking->id === $booking->id;
        });
    }

    /**
     * Test customer can cancel their own booking
     */
    public function test_customer_can_cancel_their_own_booking(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $booking = Booking::factory()
            ->forUser($customer)
            ->confirmed()
            ->create();

        // Create payment for confirmed booking
        Payment::factory()->forBooking($booking)->success()->create();

        $response = $this->postJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);

        // Payment should be refunded
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'refunded',
        ]);
    }

    /**
     * Test customer cannot cancel other customers' bookings
     */
    public function test_customer_cannot_cancel_other_customers_bookings(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();

        $booking = Booking::factory()->forUser($customer2)->create();

        Sanctum::actingAs($customer1);

        $response = $this->postJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    /**
     * Test cannot cancel already cancelled booking
     */
    public function test_cannot_cancel_already_cancelled_booking(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $booking = Booking::factory()
            ->forUser($customer)
            ->cancelled()
            ->create();

        $response = $this->postJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Booking is already cancelled.',
            ]);
    }

    /**
     * Test admin can update any booking
     */
    public function test_admin_can_update_any_booking(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $booking = Booking::factory()->pending()->create();

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }

    /**
     * Test customer can update their own booking quantity
     */
    public function test_customer_can_update_their_own_booking_quantity(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $ticket = Ticket::factory()->create(['quantity' => 100]);
        $booking = Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'quantity' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 3);
    }

    /**
     * Test quantity update fails when exceeding availability
     */
    public function test_quantity_update_fails_when_exceeding_availability(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $ticket = Ticket::factory()->create(['quantity' => 10]);

        $booking = Booking::factory()
            ->forUser($customer)
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 5]);

        // Book remaining tickets
        Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 5]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'quantity' => 6, // Would exceed availability
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test admin can delete booking
     */
    public function test_admin_can_delete_booking(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $booking = Booking::factory()->create();

        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /**
     * Test customer cannot delete booking
     */
    public function test_customer_cannot_delete_booking(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $booking = Booking::factory()->forUser($customer)->create();

        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403);
    }

    /**
     * Test booking quantity cannot exceed maximum
     */
    public function test_booking_quantity_cannot_exceed_maximum(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer);

        $ticket = Ticket::factory()->create(['quantity' => 1000]);

        $response = $this->postJson('/api/bookings', [
            'ticket_id' => $ticket->id,
            'quantity' => 11, // Max is 10
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
}
