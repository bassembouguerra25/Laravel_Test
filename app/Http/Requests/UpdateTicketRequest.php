<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Ticket Request
 * 
 * Validates ticket update data
 */
class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:999999'],
            'event_id' => ['sometimes', 'required', 'integer', 'exists:events,id'],
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
