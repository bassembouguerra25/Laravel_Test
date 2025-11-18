<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Main database seeder
 *
 * Creates initial data according to specifications:
 * - 2 administrators
 * - 3 organizers
 * - 10 customers
 * - 5 events (created by organizers)
 * - 15 tickets (distributed across the 5 events)
 * - 20 bookings (created by customers)
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 2 administrators
        $admins = User::factory()
            ->count(2)
            ->admin()
            ->create();

        $this->command->info('✓ 2 administrators created');

        // Create 3 organizers
        $organizers = User::factory()
            ->count(3)
            ->organizer()
            ->create();

        $this->command->info('✓ 3 organizers created');

        // Create 10 customers
        $customers = User::factory()
            ->count(10)
            ->customer()
            ->create();

        $this->command->info('✓ 10 customers created');

        // Create 5 events (created by organizers)
        $events = collect();
        foreach ($organizers as $index => $organizer) {
            // Each organizer creates at least one event
            $eventCount = $index < 2 ? 2 : 1; // First 2 create 2 events each, last one creates 1

            $organizerEvents = Event::factory()
                ->count($eventCount)
                ->forOrganizer($organizer)
                ->upcoming()
                ->create();

            $events = $events->merge($organizerEvents);
        }

        // Ensure we have exactly 5 events
        if ($events->count() < 5) {
            $remainingEvents = Event::factory()
                ->count(5 - $events->count())
                ->forOrganizer($organizers->random())
                ->upcoming()
                ->create();

            $events = $events->merge($remainingEvents);
        }

        // Limit to 5 events if necessary
        $events = $events->take(5);

        $this->command->info('✓ 5 events created');

        // Create 15 tickets (distributed across the 5 events)
        $tickets = collect();
        foreach ($events as $event) {
            $ticketCount = 3; // 3 tickets per event = 15 tickets total

            $eventTickets = Ticket::factory()
                ->count($ticketCount)
                ->state(['event_id' => $event->id])
                ->create();

            $tickets = $tickets->merge($eventTickets);
        }

        $this->command->info('✓ 15 tickets created');

        // Create 20 bookings (created by customers)
        $bookings = collect();
        $bookingStatuses = ['pending', 'confirmed', 'cancelled'];
        $statusWeights = [30, 60, 10]; // 30% pending, 60% confirmed, 10% cancelled

        for ($i = 0; $i < 20; $i++) {
            $randomStatus = $this->weightedRandom($bookingStatuses, $statusWeights);
            $customer = $customers->random();
            $ticket = $tickets->random();

            $booking = Booking::factory()
                ->forUser($customer)
                ->forTicket($ticket)
                ->state(['status' => $randomStatus])
                ->create();

            $bookings->push($booking);

            // Create a payment for confirmed bookings
            if ($randomStatus === 'confirmed') {
                Payment::factory()
                    ->forBooking($booking)
                    ->success()
                    ->create();
            } elseif ($randomStatus === 'pending') {
                // Some pending bookings may have a failed payment
                if (fake()->boolean(30)) {
                    Payment::factory()
                        ->forBooking($booking)
                        ->failed()
                        ->create();
                }
            } elseif ($randomStatus === 'cancelled') {
                // Cancelled bookings may have a refund
                if (fake()->boolean(50)) {
                    Payment::factory()
                        ->forBooking($booking)
                        ->refunded()
                        ->create();
                }
            }
        }

        $this->command->info('✓ 20 bookings created');
        $this->command->info('✓ Associated payments created');

        $this->command->newLine();
        $this->command->info('🎉 Database seeded successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Type', 'Quantity'],
            [
                ['Administrators', '2'],
                ['Organizers', '3'],
                ['Customers', '10'],
                ['Events', '5'],
                ['Tickets', '15'],
                ['Bookings', '20'],
            ]
        );
    }

    /**
     * Selects an element randomly based on weights
     */
    private function weightedRandom(array $items, array $weights): mixed
    {
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($items as $index => $item) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $item;
            }
        }

        return $items[0];
    }
}
