<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment Service
 * 
 * Service class for handling payment processing.
 * Simulates payment success/failure for bookings.
 */
class PaymentService
{
    /**
     * Process payment for a booking
     *
     * @param \App\Models\Booking $booking
     * @return \App\Models\Payment
     * @throws \Exception
     */
    public function processPayment(Booking $booking): Payment
    {
        // Calculate total amount
        $amount = $booking->total_amount;

        // Simulate payment processing (mock payment gateway)
        $paymentStatus = $this->simulatePayment($amount);

        // Create payment record
        $payment = DB::transaction(function () use ($booking, $amount, $paymentStatus) {
            // Check if payment already exists
            if ($booking->payment) {
                throw new \Exception('Payment already exists for this booking.');
            }

            // Create payment
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $amount,
                'status' => $paymentStatus ? 'success' : 'failed',
            ]);

            // Update booking status if payment successful
            if ($paymentStatus) {
                $booking->update(['status' => 'confirmed']);
            }

            return $payment;
        });

        // Log payment result
        Log::info('Payment processed', [
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => $payment->status,
        ]);

        return $payment;
    }

    /**
     * Create confirmed payment for a booking
     * 
     * Used when booking is manually confirmed (by admin/organizer).
     * Creates a payment with success status without simulation.
     *
     * @param \App\Models\Booking $booking
     * @return \App\Models\Payment
     * @throws \Exception
     */
    public function createConfirmedPayment(Booking $booking): Payment
    {
        // Check if payment already exists
        if ($booking->payment) {
            throw new \Exception('Payment already exists for this booking.');
        }

        $amount = $booking->total_amount;

        // Create confirmed payment directly (manual confirmation)
        $payment = DB::transaction(function () use ($booking, $amount) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $amount,
                'status' => 'success',
            ]);

            return $payment;
        });

        // Log payment creation
        Log::info('Confirmed payment created', [
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => 'success',
        ]);

        return $payment;
    }

    /**
     * Refund payment for a booking
     *
     * @param \App\Models\Booking $booking
     * @return \App\Models\Payment
     * @throws \Exception
     */
    public function refundPayment(Booking $booking): Payment
    {
        $payment = $booking->payment;

        if (!$payment) {
            throw new \Exception('No payment found for this booking.');
        }

        if ($payment->status === 'refunded') {
            throw new \Exception('Payment already refunded.');
        }

        if ($payment->status !== 'success') {
            throw new \Exception('Can only refund successful payments.');
        }

        // Process refund
        $payment = DB::transaction(function () use ($payment, $booking) {
            $payment->update(['status' => 'refunded']);
            $booking->update(['status' => 'cancelled']);

            return $payment->fresh();
        });

        // Log refund
        Log::info('Payment refunded', [
            'booking_id' => $booking->id,
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);

        return $payment;
    }

    /**
     * Simulate payment processing
     * 
     * In a real application, this would call a payment gateway API.
     * For this test, we simulate success/failure based on random chance
     * or business rules (e.g., always success for amounts > 0).
     *
     * @param float $amount
     * @return bool true if payment succeeds, false if it fails
     */
    protected function simulatePayment(float $amount): bool
    {
        // Simulate payment: 95% success rate for amounts > 0
        if ($amount <= 0) {
            return false;
        }

        // For testing: 95% success rate
        // In production, this would call actual payment gateway
        $successRate = 0.95;
        return rand(1, 100) / 100 <= $successRate;
    }
}

