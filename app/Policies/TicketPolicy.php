<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Ticket Policy
 * 
 * Defines authorization rules for Ticket model:
 * - Admin: can manage all tickets
 * - Organizer: can create and manage tickets for their own events
 * - Customer: can only view tickets
 */
class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view tickets list
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Ticket $ticket
     * @return bool
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // All authenticated users can view tickets
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Only admin and organizer can create tickets
        return $user->isAdmin() || $user->isOrganizer();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Ticket $ticket
     * @return bool
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Admin can update all tickets
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can only update tickets for their own events
        if ($user->isOrganizer()) {
            return $ticket->event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Ticket $ticket
     * @return bool
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Admin can delete all tickets
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can only delete tickets for their own events
        if ($user->isOrganizer()) {
            return $ticket->event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Ticket $ticket
     * @return bool
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        // Admin can restore all tickets
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can only restore tickets for their own events
        if ($user->isOrganizer()) {
            return $ticket->event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Ticket $ticket
     * @return bool
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        // Only admin can permanently delete tickets
        return $user->isAdmin();
    }
}
