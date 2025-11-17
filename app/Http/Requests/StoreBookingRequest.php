<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Booking Request
 * 
 * Validates booking creation data
 */
class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Booking::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'integer', 'exists:tickets,id'],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10',
                function ($attribute, $value, $fail) {
                    $ticketId = $this->input('ticket_id');
                    if ($ticketId) {
                        $ticket = Ticket::find($ticketId);
                        if ($ticket && $value > $ticket->available_quantity) {
                            $fail("The requested quantity ({$value}) exceeds available tickets ({$ticket->available_quantity}).");
                        }
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
            'ticket_id.exists' => 'The selected ticket does not exist.',
            'quantity.min' => 'You must book at least 1 ticket.',
            'quantity.max' => 'You cannot book more than 10 tickets at once.',
        ];
    }
}
