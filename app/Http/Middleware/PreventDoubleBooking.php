<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent Double Booking Middleware
 * 
 * Prevents users from creating multiple active bookings for the same ticket.
 * This middleware checks if the authenticated user already has a pending or confirmed
 * booking for the ticket they are trying to book.
 */
class PreventDoubleBooking
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to booking creation requests
        if (!$request->user() || !$request->has('ticket_id')) {
            return $next($request);
        }

        $user = $request->user();
        $ticketId = $request->input('ticket_id');

        // Check if user already has an active booking for this ticket
        $existingBooking = Booking::where('user_id', $user->id)
            ->where('ticket_id', $ticketId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingBooking) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You already have an active booking for this ticket.',
                'errors' => [
                    'ticket_id' => ['You already have an active booking for this ticket.'],
                ],
            ], 422);
        }

        return $next($request);
    }
}
