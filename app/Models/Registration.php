<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Inscription d'un contact à une séance. starts_at recopie la date de la séance (elle reste connue si la séance change).
 *
 * @property Carbon $starts_at
 */
#[Fillable(['event_id', 'event_date_id', 'contact_id', 'starts_at', 'seats', 'reminded_at'])]
class Registration extends Model
{
    /** Nombre de places maximum par inscription. */
    public const MAX_SEATS = 10;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'reminded_at' => 'datetime', 'seats' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Registration $r) => $r->token ??= Str::random(40));
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventDate(): BelongsTo
    {
        return $this->belongsTo(EventDate::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** La séance, ou à défaut une séance reconstituée à partir de la date enregistrée (pour les libellés). */
    public function session(): EventDate
    {
        return $this->eventDate ?? new EventDate(['starts_at' => $this->starts_at]);
    }

    public function icsUrl(): string
    {
        return route('registrations.ics', $this->token);
    }

    public function cancelUrl(): string
    {
        return route('registrations.cancel', $this->token);
    }
}
