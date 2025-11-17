<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Tests\TestCase;

/**
 * Booking Model Test
 * 
 * Tests Booking model relationships, accessors, and helper methods
 */
class BookingModelTest extends TestCase
{
    /**
     * Test booking belongs to user
     */
    public function test_booking_belongs_to_user(): void
    {
        $user = $this->createUser();
        $booking = Booking::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertEquals($user->id, $booking->user->id);
    }

    /**
     * Test booking belongs to ticket
     */
    public function test_booking_belongs_to_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $booking = Booking::factory()->forTicket($ticket)->create();

        $this->assertInstanceOf(Ticket::class, $booking->ticket);
        $this->assertEquals($ticket->id, $booking->ticket->id);
    }

    /**
     * Test booking has payment relationship
     */
    public function test_booking_has_payment_relationship(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->forBooking($booking)->create();

        $this->assertInstanceOf(Payment::class, $booking->payment);
        $this->assertEquals($payment->id, $booking->payment->id);
    }

    /**
     * Test total amount accessor
     */
    public function test_total_amount_accessor(): void
    {
        $ticket = Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->create(['quantity' => 3]);

        $this->assertEquals(150.00, $booking->total_amount);
    }

    /**
     * Test isPending helper method
     */
    public function test_is_pending_helper_method(): void
    {
        $booking = Booking::factory()->pending()->create();
        $confirmed = Booking::factory()->confirmed()->create();

        $this->assertTrue($booking->isPending());
        $this->assertFalse($confirmed->isPending());
    }

    /**
     * Test isConfirmed helper method
     */
    public function test_is_confirmed_helper_method(): void
    {
        $booking = Booking::factory()->pending()->create();
        $confirmed = Booking::factory()->confirmed()->create();

        $this->assertFalse($booking->isConfirmed());
        $this->assertTrue($confirmed->isConfirmed());
    }

    /**
     * Test isCancelled helper method
     */
    public function test_is_cancelled_helper_method(): void
    {
        $booking = Booking::factory()->pending()->create();
        $cancelled = Booking::factory()->cancelled()->create();

        $this->assertFalse($booking->isCancelled());
        $this->assertTrue($cancelled->isCancelled());
    }
}

