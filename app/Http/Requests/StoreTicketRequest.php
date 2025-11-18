<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ticket Request
 *
 * Validates ticket creation data
 */
class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can create tickets in general
        if (! $this->user()->can('create', \App\Models\Ticket::class)) {
            return false;
        }

        // If organizer (and not admin), verify they're creating ticket for their own event
        if ($this->user()->isOrganizer() && ! $this->user()->isAdmin()) {
            $eventId = $this->input('event_id');
            if ($eventId) {
                $event = \App\Models\Event::find($eventId);
                if ($event && $event->created_by !== $this->user()->id) {
                    return false; // Organizer cannot create ticket for other organizer's event
                }
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
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
            'event_id.exists' => 'The selected event does not exist.',
            'price.min' => 'The price must be at least 0.',
            'quantity.min' => 'The quantity must be at least 1.',
        ];
    }
}
