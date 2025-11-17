<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Booking Controller
 * 
 * Handles CRUD operations for bookings
 */
class BookingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Payment service instance
     *
     * @var \App\Services\PaymentService
     */
    protected PaymentService $paymentService;

    /**
     * Create a new controller instance
     *
     * @param \App\Services\PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

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
        if (!$user->can('viewAny', Booking::class)) {
            return $this->forbiddenResponse('You do not have permission to view bookings.');
        }

        $query = Booking::query()->with(['user', 'ticket', 'payment']);

        // Filter by user (customers see only their bookings)
        if ($user->isCustomer() && !$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Filter by organizer (see bookings for their events)
        if ($user->isOrganizer() && !$user->isAdmin()) {
            $query->whereHas('ticket.event', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        // Filter by ticket
        if ($request->has('ticket_id')) {
            $query->where('ticket_id', $request->get('ticket_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $bookings = $query->paginate($request->get('per_page', 15));

        return $this->successResponse([
            'bookings' => BookingResource::collection($bookings->items()),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreBookingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Check if event is past before starting transaction
        $ticket = \App\Models\Ticket::with('event')->findOrFail($validated['ticket_id']);
        
        // Check if event exists and is in the past
        if ($ticket->event_id && $ticket->event && $ticket->event->date) {
            $event = $ticket->event;
            
            // Date is already cast to Carbon in Event model (cast: 'datetime')
            // Use it directly - if it's already Carbon, use it, otherwise parse it
            $eventDate = $event->date instanceof \Illuminate\Support\Carbon 
                ? $event->date 
                : \Illuminate\Support\Carbon::parse($event->date);
            
            // Check if event date is in the past
            // isPast() checks if the date is before now()
            // Use errorResponse() here as ValidationException is not properly handled outside DB transaction
            if ($eventDate->isPast()) {
                return $this->errorResponse(
                    'Cannot book tickets for past events.',
                    422,
                    ['ticket_id' => ['Cannot book tickets for past events.']]
                );
            }
        }

        // Use transaction with lock to prevent race conditions
        $booking = DB::transaction(function () use ($validated, $user) {
            $ticket = \App\Models\Ticket::with('event')->lockForUpdate()->findOrFail($validated['ticket_id']);

            // Double-check availability with lock
            if ($validated['quantity'] > $ticket->available_quantity) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['quantity' => "The requested quantity ({$validated['quantity']}) exceeds available tickets ({$ticket->available_quantity})."]
                );
            }

            // Note: Double booking prevention is handled by PreventDoubleBooking middleware
            // This check is kept as a defense-in-depth measure in the transaction
            // to handle edge cases where multiple requests might bypass the middleware
            $existingBooking = Booking::where('user_id', $user->id)
                ->where('ticket_id', $validated['ticket_id'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->first();

            if ($existingBooking) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['ticket_id' => 'You already have an active booking for this ticket.']
                );
            }

            $validated['user_id'] = $user->id;
            $validated['status'] = 'pending';

            return Booking::create($validated);
        });

        $booking->load(['user', 'ticket', 'payment']);

        return $this->successResponse(
            new BookingResource($booking),
            'Booking created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Booking $booking
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Booking $booking, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('view', $booking)) {
            return $this->forbiddenResponse('You do not have permission to view this booking.');
        }

        $booking->load(['user', 'ticket.event', 'payment']);

        return $this->successResponse(
            new BookingResource($booking)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateBookingRequest $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        $validated = $request->validated();

        // Use transaction for data integrity
        DB::transaction(function () use ($booking, $validated) {
            // Handle quantity change - verify availability
            if (isset($validated['quantity']) && $validated['quantity'] != $booking->quantity) {
                // Load ticket relation if not loaded, then lock it
                if (!$booking->relationLoaded('ticket')) {
                    $booking->load('ticket');
                }
                
                $ticket = \App\Models\Ticket::lockForUpdate()->findOrFail($booking->ticket_id);
                
                // Calculate available quantity excluding current booking
                $currentBookedQuantity = $ticket->bookings()
                    ->where('id', '!=', $booking->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->sum('quantity');
                
                $availableQuantity = max(0, $ticket->quantity - $currentBookedQuantity);
                
                if ($validated['quantity'] > $availableQuantity) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['quantity' => "The requested quantity ({$validated['quantity']}) exceeds available tickets ({$availableQuantity})."]
                    );
                }
            }

            // Handle status change to confirmed - create payment using PaymentService
            if (isset($validated['status']) && $validated['status'] === 'confirmed' && $booking->status !== 'confirmed') {
                $booking->update($validated);

                // Create confirmed payment using PaymentService if not exists
                if (!$booking->payment) {
                    $this->paymentService->createConfirmedPayment($booking->fresh());
                }

                // Send confirmation notification to customer via queue
                $booking->load('user', 'ticket.event');
                $booking->user->notify(new \App\Notifications\BookingConfirmedNotification($booking->fresh()));
            } else {
                $booking->update($validated);
            }
        });

        $booking->load(['user', 'ticket.event', 'payment']);

        return $this->successResponse(
            new BookingResource($booking),
            'Booking updated successfully'
        );
    }

    /**
     * Cancel a booking (alias for update with status cancelled).
     *
     * @param \App\Models\Booking $booking
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Booking $booking, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('cancel', $booking)) {
            return $this->forbiddenResponse('You do not have permission to cancel this booking.');
        }

        if ($booking->status === 'cancelled') {
            return $this->errorResponse('Booking is already cancelled.', 400);
        }

        DB::transaction(function () use ($booking) {
            // If payment exists and is successful, refund using PaymentService
            if ($booking->payment && $booking->payment->isSuccess()) {
                try {
                    $this->paymentService->refundPayment($booking);
                } catch (\Exception $e) {
                    // If refund fails, still cancel the booking but log the error
                    \Illuminate\Support\Facades\Log::error('Refund failed during cancellation', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update booking status to cancelled
            $booking->update(['status' => 'cancelled']);
        });

        $booking->load(['user', 'ticket.event', 'payment']);

        return $this->successResponse(
            new BookingResource($booking),
            'Booking cancelled successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Booking $booking
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Booking $booking, Request $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('delete', $booking)) {
            return $this->forbiddenResponse('You do not have permission to delete this booking.');
        }

        $booking->delete();

        return $this->successResponse(
            null,
            'Booking deleted successfully'
        );
    }
}
