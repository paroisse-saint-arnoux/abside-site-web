<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Personne inscrite à au moins un événement (une fiche par adresse e-mail).
 * created_at = date d'ajout au carnet.
 */
#[Fillable(['email', 'first_name', 'last_name', 'phone', 'newsletter_at'])]
class Contact extends Model
{
    /** Durée de conservation après la dernière participation (RGPD). */
    public const RETENTION_YEARS = 3;

    protected function casts(): array
    {
        return ['newsletter_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(fn (Contact $c) => $c->email = mb_strtolower(trim($c->email)));
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class)->orderByDesc('starts_at');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Ajoute events_count (événements distincts) et last_event_at (dernière séance). */
    public function scopeWithStats(Builder $query): void
    {
        $query->addSelect([
            'events_count' => Registration::selectRaw('count(distinct event_id)')->whereColumn('contact_id', 'contacts.id'),
            'last_event_at' => Registration::selectRaw('max(starts_at)')->whereColumn('contact_id', 'contacts.id'),
        ]);
    }

    /** Contacts à supprimer : dernière participation (ou ajout, faute d'inscription) il y a plus de 3 ans. */
    public function scopeExpired(Builder $query): void
    {
        $limit = now()->subYears(self::RETENTION_YEARS);
        $query->where('created_at', '<', $limit)
            ->whereDoesntHave('registrations', fn ($q) => $q->where('starts_at', '>=', $limit));
    }
}
