<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the Payment model
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'status' => fake()->randomElement(['success', 'failed', 'refunded']),
        ];
    }

    /**
     * Indicate that the payment was successful.
     *
     * @return static
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
        ]);
    }

    /**
     * Indicate that the payment failed.
     *
     * @return static
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the payment was refunded.
     *
     * @return static
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
        ]);
    }

    /**
     * Indicate that the payment is for a specific booking.
     *
     * @param Booking $booking
     * @return static
     */
    public function forBooking(Booking $booking): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
        ]);
    }
}
