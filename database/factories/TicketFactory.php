<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the Ticket model
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['VIP', 'Standard', 'Premium', 'Early Bird', 'Student', 'Senior'];

        return [
            'type' => fake()->randomElement($types),
            'price' => fake()->randomFloat(2, 10, 500),
            'quantity' => fake()->numberBetween(10, 500),
            'event_id' => Event::factory(),
        ];
    }

    /**
     * Indicate that the ticket is of type VIP.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'VIP',
            'price' => fake()->randomFloat(2, 100, 500),
        ]);
    }

    /**
     * Indicate that the ticket is of type Standard.
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Standard',
            'price' => fake()->randomFloat(2, 20, 150),
        ]);
    }

    /**
     * Indicate that the ticket is of type Premium.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Premium',
            'price' => fake()->randomFloat(2, 50, 300),
        ]);
    }

    /**
     * Indicate that the ticket has a limited quantity.
     */
    public function limitedQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Indicate that the ticket is for a specific event.
     */
    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event->id,
        ]);
    }
}
