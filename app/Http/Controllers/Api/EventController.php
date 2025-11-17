<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Event Controller
 * 
 * Handles CRUD operations for events
 */
class EventController extends Controller
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
        if (!$user->can('viewAny', Event::class)) {
            return $this->forbiddenResponse('You do not have permission to view events.');
        }

        // Build cache key based on user role and request parameters
        $cacheKey = 'events_list_' . md5(
            $user->id . '_' .
            ($user->isOrganizer() && !$user->isAdmin() ? 'organizer' : 'all') . '_' .
            $request->get('search') . '_' .
            $request->get('date_from') . '_' .
            $request->get('date_to') . '_' .
            $request->get('sort_by', 'date') . '_' .
            $request->get('sort_order', 'asc') . '_' .
            $request->get('per_page', 15)
        );

        // Cache events list for 60 minutes (frequently accessed)
        $events = Cache::remember($cacheKey, 3600, function () use ($user, $request) {
            $query = Event::query()->with(['organizer', 'tickets']);

            // Filter by organizer for non-admin users
            if ($user->isOrganizer() && !$user->isAdmin()) {
                $query->where('created_by', $user->id);
            }

            // Search by title and location using trait scope
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->searchByTitle($search)
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }

            // Filter by date range using trait scope
            $query->filterByDate(
                $request->get('date_from'),
                $request->get('date_to')
            );

            // Sort
            $sortBy = $request->get('sort_by', 'date');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            return $query->paginate($request->get('per_page', 15));
        });

        return $this->successResponse([
            'events' => EventResource::collection($events->items()),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreEventRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $event = Event::create($validated);
        $event->load(['organizer', 'tickets']);

        // Clear events cache when new event is created
        // Note: In production with Redis, you could use Cache::tags(['events'])->flush()
        // For now, we'll flush all cache (simple approach)
        Cache::flush();

        return $this->successResponse(
            new EventResource($event),
            'Event created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Event $event
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Event $event, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('view', $event)) {
            return $this->forbiddenResponse('You do not have permission to view this event.');
        }

        $event->load(['organizer', 'tickets']);

        return $this->successResponse(
            new EventResource($event)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateEventRequest $request
     * @param \App\Models\Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();
        $event->update($validated);
        $event->load(['organizer', 'tickets']);

        // Clear events cache when event is updated
        Cache::flush();

        return $this->successResponse(
            new EventResource($event),
            'Event updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Event $event
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Event $event, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('delete', $event)) {
            return $this->forbiddenResponse('You do not have permission to delete this event.');
        }

        $event->delete();

        // Clear events cache when event is deleted
        Cache::flush();

        return $this->successResponse(
            null,
            'Event deleted successfully'
        );
    }
}
