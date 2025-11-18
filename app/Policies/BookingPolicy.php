<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Booking Policy
 *
 * Defines authorization rules for Booking model:
 * - Admin: can manage all bookings
 * - Organizer: can view bookings for their own events
 * - Customer: can create bookings and view/cancel their own bookings
 */
class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view bookings (their own or for their events)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Admin can view all bookings
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can view bookings for their events
        if ($user->isOrganizer()) {
            return $booking->ticket->event->created_by === $user->id;
        }

        // Customer can only view their own bookings
        if ($user->isCustomer()) {
            return $booking->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only customers can create bookings (book tickets)
        return $user->isCustomer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Admin can update all bookings
        if ($user->isAdmin()) {
            return true;
        }

        // Customer can only update their own bookings (e.g., cancel)
        if ($user->isCustomer()) {
            return $booking->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Admin can cancel all bookings
        if ($user->isAdmin()) {
            return true;
        }

        // Customer can only cancel their own bookings
        if ($user->isCustomer()) {
            return $booking->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        // Only admin can delete bookings
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        // Only admin can restore bookings
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        // Only admin can permanently delete bookings
        return $user->isAdmin();
    }
}
