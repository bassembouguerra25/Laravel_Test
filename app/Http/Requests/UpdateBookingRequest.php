<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Booking Request
 *
 * Validates booking update data
 */
class UpdateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booking'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['pending', 'confirmed', 'cancelled']),
            ],
            'quantity' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:10',
                function ($attribute, $value, $fail) {
                    $booking = $this->route('booking');
                    if (! $booking || ! $booking->ticket_id) {
                        return;
                    }

                    // Get ticket directly from database to avoid relation loading issues
                    $ticket = Ticket::find($booking->ticket_id);

                    if (! $ticket) {
                        return;
                    }

                    // Calculate available quantity excluding current booking
                    $currentBookedQuantity = $ticket->bookings()
                        ->where('id', '!=', $booking->id)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->sum('quantity');

                    $availableQuantity = max(0, $ticket->quantity - $currentBookedQuantity);

                    if ($value > $availableQuantity) {
                        $fail("The requested quantity ({$value}) exceeds available tickets ({$availableQuantity}).");
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The status must be one of: pending, confirmed, cancelled.',
            'quantity.min' => 'You must book at least 1 ticket.',
            'quantity.max' => 'You cannot book more than 10 tickets at once.',
        ];
    }
}
