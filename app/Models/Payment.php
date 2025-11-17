<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Model
 * 
 * Represents a payment associated with a booking.
 * A payment can be successful, failed, or refunded.
 * HasOne relationship with Booking (one booking has one payment).
 * 
 * @property int $id
 * @property int $booking_id ID of the associated booking
 * @property float $amount Payment amount
 * @property string $status Payment status: success, failed, refunded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Booking $booking The associated booking
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'amount',
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
            'amount' => 'decimal:2',
            'status' => 'string',
        ];
    }

    /**
     * Relation: A payment belongs to a booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Check if the payment was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the payment failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the payment was refunded
     *
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}
