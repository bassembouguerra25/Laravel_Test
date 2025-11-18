<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Event Policy
 *
 * Defines authorization rules for Event model:
 * - Admin: can manage all events
 * - Organizer: can create and manage their own events
 * - Customer: can only view events
 */
class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view events list
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        // All authenticated users can view events
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and organizer can create events
        return $user->isAdmin() || $user->isOrganizer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        // Admin can update all events
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can only update their own events
        if ($user->isOrganizer()) {
            return $event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        // Admin can delete all events
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can only delete their own events
        if ($user->isOrganizer()) {
            return $event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        // Admin can restore all events
        if ($user->isAdmin()) {
            return true;
        }

        // Organizer can only restore their own events
        if ($user->isOrganizer()) {
            return $event->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        // Only admin can permanently delete events
        return $user->isAdmin();
    }
}
