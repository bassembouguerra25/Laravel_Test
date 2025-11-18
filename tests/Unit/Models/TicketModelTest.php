<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use Tests\TestCase;

/**
 * Ticket Model Test
 *
 * Tests Ticket model relationships and accessors
 */
class TicketModelTest extends TestCase
{
    /**
     * Test ticket belongs to event
     */
    public function test_ticket_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->forEvent($event)->create();

        $this->assertInstanceOf(Event::class, $ticket->event);
        $this->assertEquals($event->id, $ticket->event->id);
    }

    /**
     * Test ticket has bookings relationship
     */
    public function test_ticket_has_bookings_relationship(): void
    {
        $ticket = Ticket::factory()->create();
        $booking = Booking::factory()->forTicket($ticket)->create();

        $this->assertInstanceOf(Booking::class, $ticket->bookings->first());
        $this->assertEquals($booking->id, $ticket->bookings->first()->id);
    }

    /**
     * Test available quantity accessor
     */
    public function test_available_quantity_accessor(): void
    {
        $ticket = Ticket::factory()->create(['quantity' => 100]);

        // No bookings yet
        $this->assertEquals(100, $ticket->available_quantity);

        // Book 30 tickets (pending)
        Booking::factory()
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 30]);

        $this->assertEquals(70, $ticket->available_quantity);

        // Book 20 more tickets (confirmed)
        Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 20]);

        $this->assertEquals(50, $ticket->available_quantity);

        // Cancelled bookings should not affect availability
        Booking::factory()
            ->forTicket($ticket)
            ->cancelled()
            ->create(['quantity' => 10]);

        $this->assertEquals(50, $ticket->available_quantity);
    }

    /**
     * Test available quantity cannot be negative
     */
    public function test_available_quantity_cannot_be_negative(): void
    {
        $ticket = Ticket::factory()->create(['quantity' => 10]);

        // Book more than available
        Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 15]);

        // Should return 0, not negative
        $this->assertEquals(0, $ticket->available_quantity);
    }
}
