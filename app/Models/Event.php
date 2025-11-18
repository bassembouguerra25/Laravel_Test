<?php

namespace App\Models;

use App\Traits\CommonQueryScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Event Model
 *
 * Represents an event that can have multiple ticket types.
 * Each event is created by a user (organizer or admin).
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $date Event date and time
 * @property string $location Event location
 * @property int $created_by ID of the user who created the event
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $organizer The user who created the event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 */
class Event extends Model
{
    use CommonQueryScopes, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    /**
     * Relation: An event belongs to a user (organizer/admin)
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation: An event can have multiple tickets
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
