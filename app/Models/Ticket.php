<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ticket Model
 *
 * Represents a ticket type for an event.
 * Each ticket has a type (VIP, Standard, Premium, etc.), a price and an available quantity.
 *
 * @property int $id
 * @property string $type Ticket type (VIP, Standard, Premium, etc.)
 * @property float $price Ticket price
 * @property int $quantity Available ticket quantity
 * @property int $event_id ID of the associated event
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event The associated event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 */
class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'price',
        'quantity',
        'event_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Relation: A ticket belongs to an event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relation: A ticket can have multiple bookings
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Calculate the number of available tickets
     */
    public function getAvailableQuantityAttribute(): int
    {
        $bookedQuantity = $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('quantity');

        return max(0, $this->quantity - $bookedQuantity);
    }
}
