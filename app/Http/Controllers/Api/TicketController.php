<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket Controller
 * 
 * Handles CRUD operations for tickets
 */
class TicketController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check authorization
        if (!$user->can('viewAny', Ticket::class)) {
            return $this->forbiddenResponse('You do not have permission to view tickets.');
        }

        $query = Ticket::query()->with(['event', 'bookings']);

        // Filter by event
        if ($request->has('event_id')) {
            $query->where('event_id', $request->get('event_id'));
        }

        // Filter by organizer for non-admin users
        if ($user->isOrganizer() && !$user->isAdmin()) {
            $query->whereHas('event', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by available tickets only (available_quantity > 0)
        if ($request->has('available_only') && $request->boolean('available_only')) {
            $query->whereRaw('quantity > (
                SELECT COALESCE(SUM(quantity), 0)
                FROM bookings
                WHERE bookings.ticket_id = tickets.id
                AND bookings.status IN (?, ?)
            )', ['pending', 'confirmed']);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'price');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $tickets = $query->paginate($request->get('per_page', 15));

        return $this->successResponse([
            'tickets' => TicketResource::collection($tickets->items()),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreTicketRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ticket = Ticket::create($validated);
        $ticket->load(['event', 'bookings']);

        return $this->successResponse(
            new TicketResource($ticket),
            'Ticket created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Ticket $ticket
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Ticket $ticket, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('view', $ticket)) {
            return $this->forbiddenResponse('You do not have permission to view this ticket.');
        }

        $ticket->load(['event', 'bookings']);

        return $this->successResponse(
            new TicketResource($ticket)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateTicketRequest $request
     * @param \App\Models\Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validated();
        $ticket->update($validated);
        $ticket->load(['event', 'bookings']);

        return $this->successResponse(
            new TicketResource($ticket),
            'Ticket updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Ticket $ticket
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Ticket $ticket, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('delete', $ticket)) {
            return $this->forbiddenResponse('You do not have permission to delete this ticket.');
        }

        $ticket->delete();

        return $this->successResponse(
            null,
            'Ticket deleted successfully'
        );
    }
}
