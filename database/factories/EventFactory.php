<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the Event model
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = [
            'Paris, France',
            'Lyon, France',
            'Marseille, France',
            'Toulouse, France',
            'Nice, France',
            'Nantes, France',
            'Strasbourg, France',
            'Montpellier, France',
            'Bordeaux, France',
            'Lille, France',
        ];

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'location' => fake()->randomElement($locations),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the event is created by an organizer.
     */
    public function forOrganizer(?User $organizer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $organizer?->id ?? User::factory()->organizer(),
        ]);
    }

    /**
     * Indicate that the event is in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    /**
     * Indicate that the event is in the future.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('+1 day', '+6 months'),
        ]);
    }
}
