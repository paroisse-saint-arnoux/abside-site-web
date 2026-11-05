<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Une séance d'un événement. L'agenda affiche une ligne par séance.
 *
 * @property Carbon $starts_at
 * @property ?Carbon $ends_at
 */
#[Fillable(['starts_at', 'ends_at', 'label'])]
class EventDate extends Model
{
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Séances à venir (ou en cours) : on garde une séance jusqu'à sa fin. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(fn ($q) => $q->where('ends_at', '>=', now())
            ->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay())));
    }

    public function scopePast(Builder $query): void
    {
        $query->where(fn ($q) => $q->where('ends_at', '<', now())
            ->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '<', now()->startOfDay())));
    }

    /** « 20h30 » ou « 14h30 – 16h » */
    public function timeLabel(): string
    {
        $fmt = fn (Carbon $d) => $d->minute ? $d->format('G\hi') : $d->format('G\h');
        $start = $fmt($this->starts_at);

        return $this->ends_at && $this->ends_at->isSameDay($this->starts_at) ? $start.' – '.$fmt($this->ends_at) : $start;
    }

    /** « Samedi 12 décembre 2026 » */
    public function dayLabel(bool $withYear = true): string
    {
        $day = $this->starts_at->day === 1 ? '1er' : $this->starts_at->day;

        return Str::ucfirst($this->starts_at->translatedFormat('l')).' '.$day.' '.$this->starts_at->translatedFormat('F').($withYear ? ' '.$this->starts_at->year : '');
    }

    /** « Samedi 12 décembre 2026, 20h30 » */
    public function fullLabel(): string
    {
        return $this->dayLabel().', '.$this->timeLabel();
    }
}
