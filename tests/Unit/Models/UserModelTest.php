<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;

/**
 * User Model Test
 * 
 * Tests User model relationships and helper methods
 */
class UserModelTest extends TestCase
{
    /**
     * Test user has events relationship
     */
    public function test_user_has_events_relationship(): void
    {
        $user = $this->createUser();
        $event = Event::factory()->forOrganizer($user)->create();

        $this->assertInstanceOf(Event::class, $user->events->first());
        $this->assertEquals($event->id, $user->events->first()->id);
    }

    /**
     * Test user has bookings relationship
     */
    public function test_user_has_bookings_relationship(): void
    {
        $user = $this->createUser();
        $booking = Booking::factory()->forUser($user)->create();

        $this->assertInstanceOf(Booking::class, $user->bookings->first());
        $this->assertEquals($booking->id, $user->bookings->first()->id);
    }

    /**
     * Test user has payments relationship through bookings
     */
    public function test_user_has_payments_relationship(): void
    {
        $user = $this->createUser();
        $booking = Booking::factory()->forUser($user)->create();
        $payment = Payment::factory()->forBooking($booking)->create();

        $this->assertInstanceOf(Payment::class, $user->payments->first());
        $this->assertEquals($payment->id, $user->payments->first()->id);
    }

    /**
     * Test isAdmin helper method
     */
    public function test_is_admin_helper_method(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($customer->isAdmin());
    }

    /**
     * Test isOrganizer helper method
     */
    public function test_is_organizer_helper_method(): void
    {
        $organizer = $this->createOrganizer();
        $customer = $this->createCustomer();

        $this->assertTrue($organizer->isOrganizer());
        $this->assertFalse($customer->isOrganizer());
    }

    /**
     * Test isCustomer helper method
     */
    public function test_is_customer_helper_method(): void
    {
        $customer = $this->createCustomer();
        $admin = $this->createAdmin();

        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($admin->isCustomer());
    }
}

