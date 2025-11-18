<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Booking Model
 *
 * Represents a ticket booking by a user.
 * A booking can be pending, confirmed, or cancelled.
 * Each booking has an associated payment (hasOne relationship).
 *
 * @property int $id
 * @property int $user_id ID of the user who made the booking
 * @property int $ticket_id ID of the booked ticket
 * @property int $quantity Number of tickets booked
 * @property string $status Booking status: pending, confirmed, cancelled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user The user who made the booking
 * @property-read \App\Models\Ticket $ticket The booked ticket
 * @property-read \App\Models\Payment|null $payment The payment associated with the booking
 */
class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'ticket_id',
        'quantity',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // status is already a string by default, no cast needed
        ];
    }

    /**
     * Relation: A booking belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: A booking belongs to a ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relation: A booking has an associated payment
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Check if the booking is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the booking is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if the booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Calculate the total amount of the booking
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->ticket->price * $this->quantity;
    }
}
