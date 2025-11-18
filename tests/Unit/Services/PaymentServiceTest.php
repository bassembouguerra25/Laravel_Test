<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Service Test
 *
 * Unit tests for PaymentService class
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService;
    }

    /**
     * Test successful payment processing
     */
    public function test_process_payment_creates_successful_payment(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        // Mock simulatePayment to always return true for success
        $paymentService = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['simulatePayment'])
            ->getMock();

        $paymentService->expects($this->once())
            ->method('simulatePayment')
            ->with(100.00) // 50.00 * 2
            ->willReturn(true);

        $payment = $paymentService->processPayment($booking);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('success', $payment->status);
        $this->assertEquals(100.00, $payment->amount);
        $this->assertEquals($booking->id, $payment->booking_id);

        // Booking should be confirmed
        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);

        // Verify payment was saved in database
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'status' => 'success',
        ]);
    }

    /**
     * Test failed payment processing
     */
    public function test_process_payment_creates_failed_payment(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        // Mock simulatePayment to return false for failure
        $paymentService = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['simulatePayment'])
            ->getMock();

        $paymentService->expects($this->once())
            ->method('simulatePayment')
            ->with(100.00)
            ->willReturn(false);

        $payment = $paymentService->processPayment($booking);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('failed', $payment->status);
        $this->assertEquals(100.00, $payment->amount);

        // Booking should remain pending on failed payment
        $booking->refresh();
        $this->assertEquals('pending', $booking->status);

        // Verify failed payment was saved
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'failed',
        ]);
    }

    /**
     * Test payment processing throws exception when payment already exists
     */
    public function test_process_payment_throws_exception_when_payment_exists(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        // Create existing payment
        Payment::factory()->forBooking($booking)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment already exists for this booking.');

        $this->paymentService->processPayment($booking);
    }

    /**
     * Test successful refund processing
     */
    public function test_refund_payment_processes_refund(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 2]);

        // Create successful payment
        $payment = Payment::factory()
            ->forBooking($booking)
            ->success()
            ->create(['amount' => 100.00]);

        $refundedPayment = $this->paymentService->refundPayment($booking);

        $this->assertInstanceOf(Payment::class, $refundedPayment);
        $this->assertEquals('refunded', $refundedPayment->status);
        $this->assertEquals(100.00, $refundedPayment->amount);

        // Booking should be cancelled
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);

        // Verify refund was saved
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
    }

    /**
     * Test refund throws exception when no payment exists
     */
    public function test_refund_payment_throws_exception_when_no_payment(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No payment found for this booking.');

        $this->paymentService->refundPayment($booking);
    }

    /**
     * Test refund throws exception when payment already refunded
     */
    public function test_refund_payment_throws_exception_when_already_refunded(): void
    {
        $booking = Booking::factory()->cancelled()->create();
        Payment::factory()->forBooking($booking)->refunded()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment already refunded.');

        $this->paymentService->refundPayment($booking);
    }

    /**
     * Test refund throws exception when payment failed
     */
    public function test_refund_payment_throws_exception_when_payment_failed(): void
    {
        $booking = Booking::factory()->pending()->create();
        Payment::factory()->forBooking($booking)->failed()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can only refund successful payments.');

        $this->paymentService->refundPayment($booking);
    }

    /**
     * Test payment processing maintains data integrity
     */
    public function test_process_payment_maintains_data_integrity(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->pending()
            ->create(['quantity' => 2]);

        $paymentService = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['simulatePayment'])
            ->getMock();

        $paymentService->method('simulatePayment')->willReturn(true);

        $payment = $paymentService->processPayment($booking);

        // Verify both payment and booking are updated atomically
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'success',
        ]);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    /**
     * Test refund maintains data integrity
     */
    public function test_refund_payment_maintains_data_integrity(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        $payment = Payment::factory()->forBooking($booking)->success()->create();

        $this->paymentService->refundPayment($booking);

        // Verify both payment and booking are updated atomically
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    /**
     * Test create confirmed payment for manually confirmed booking
     */
    public function test_create_confirmed_payment(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 2]);

        $payment = $this->paymentService->createConfirmedPayment($booking);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('success', $payment->status);
        $this->assertEquals(100.00, $payment->amount);
        $this->assertEquals($booking->id, $payment->booking_id);

        // Verify payment was saved in database
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'status' => 'success',
        ]);
    }

    /**
     * Test create confirmed payment throws exception when payment exists
     */
    public function test_create_confirmed_payment_throws_exception_when_payment_exists(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 2]);

        // Create existing payment
        Payment::factory()->forBooking($booking)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment already exists for this booking.');

        $this->paymentService->createConfirmedPayment($booking);
    }

    /**
     * Test create confirmed payment maintains data integrity
     */
    public function test_create_confirmed_payment_maintains_data_integrity(): void
    {
        $ticket = \App\Models\Ticket::factory()->create(['price' => 50.00]);
        $booking = Booking::factory()
            ->forTicket($ticket)
            ->confirmed()
            ->create(['quantity' => 2]);

        $payment = $this->paymentService->createConfirmedPayment($booking);

        // Verify payment was created successfully
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'success',
        ]);

        // Verify booking status remains confirmed (not changed by this method)
        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }
}
