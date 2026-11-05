<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\Registration;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'slug', 'type', 'place_id', 'intro', 'body', 'image', 'image_alt', 'poster', 'gallery', 'specs', 'partners',
    'is_free', 'price_text', 'registration', 'capacity', 'is_featured', 'is_spotlight', 'schedule_mode', 'recurrence', 'is_published',
])]
class Event extends Model
{
    /** Nombre maximal de caractéristiques (clé / valeur) par événement. */
    public const MAX_SPECS = 6;

    protected $attributes = [
        'is_free' => true,
        'registration' => 'optional',
        'schedule_mode' => 'dates',
        'is_published' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'registration' => Registration::class,
            'gallery' => 'array',
            'specs' => 'array',
            'partners' => 'array',
            'recurrence' => 'array',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
            'is_spotlight' => 'boolean',
            'is_published' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            $event->preview_token ??= Str::random(40);
        });

        static::saving(function (Event $event) {
            $event->slug = Str::slug(filled($event->slug) ? $event->slug : $event->title);
            $base = $event->slug;
            $i = 2;
            while (static::where('slug', $event->slug)->whereKeyNot($event->getKey())->exists()) {
                $event->slug = $base.'-'.$i++;
            }
        });

        // Horaires récurrents : les séances sont générées à chaque enregistrement.
        static::saved(function (Event $event) {
            if ($event->schedule_mode === 'recurring') {
                $event->regenerateRecurringDates();
            }
        });
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(EventDate::class)->orderBy('starts_at');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(\App\Models\Registration::class);
    }

    public function nextDate(): HasOne
    {
        return $this->hasOne(EventDate::class)->ofMany(['starts_at' => 'min'], fn ($q) => $q->upcoming());
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function url(): string
    {
        return route('events.show', $this->slug);
    }

    public function previewUrl(): string
    {
        return route('events.show', ['slug' => $this->slug, 'apercu' => $this->preview_token]);
    }

    public function shareImageUrl(): string
    {
        return route('events.share-image', ['slug' => $this->slug, 'v' => substr(md5($this->updated_at), 0, 8)]);
    }

    /** Dernière séance terminée ou date de fin : sert au tri des mises en avant. */
    public function endsAt(): ?Carbon
    {
        $last = $this->dates->last();

        return $last ? ($last->ends_at ?? $last->starts_at) : null;
    }

    /** Liste « clé / valeur » limitée et nettoyée. */
    public function specList(): array
    {
        return collect($this->specs ?? [])
            ->filter(fn ($s) => filled($s['key'] ?? null) && filled($s['value'] ?? null))
            ->take(self::MAX_SPECS)
            ->values()
            ->all();
    }

    /** Inscription possible en ligne (facultative ou obligatoire). */
    public function acceptsRegistrations(): bool
    {
        return $this->registration !== Registration::None;
    }

    /**
     * Places restantes par séance (jauge par date), null si la jauge est illimitée.
     *
     * @param  iterable<EventDate>  $dates
     * @return array<int, ?int> id de séance => places restantes
     */
    public function remainingSeats(iterable $dates): array
    {
        $ids = collect($dates)->pluck('id');
        $taken = $this->capacity
            ? \App\Models\Registration::whereIn('event_date_id', $ids)->groupBy('event_date_id')->selectRaw('event_date_id, sum(seats) as n')->pluck('n', 'event_date_id')
            : collect();

        return $ids->mapWithKeys(fn ($id) => [$id => $this->capacity ? max(0, $this->capacity - (int) ($taken[$id] ?? 0)) : null])->all();
    }

    /** Ligne « Entrée » : participation libre et/ou tarif. */
    public function priceLabel(): ?string
    {
        return collect([$this->is_free ? 'Participation libre' : null, $this->price_text])->filter()->implode(' · ') ?: null;
    }

    /**
     * Événements de la ligne « À ne pas manquer » de l'accueil : à venir, d'abord ceux cochés « Mettre en avant »
     * (date de fin la plus proche en premier), complétés au besoin par les prochains événements. Affichés par date.
     *
     * @return Collection<int, Event>
     */
    public static function featuredForHome(int $slots = 4): Collection
    {
        $upcoming = static::published()
            ->whereHas('dates', fn ($q) => $q->upcoming())
            ->with(['place', 'dates', 'nextDate'])
            ->get()
            ->sortBy(fn (Event $e) => $e->endsAt());

        $picked = $upcoming->where('is_featured', true)->take($slots);
        if ($picked->count() < $slots) {
            $others = $upcoming->where('is_featured', false)->sortBy(fn (Event $e) => $e->nextDate?->starts_at)->take($slots - $picked->count());
            $picked = $picked->concat($others);
        }

        return $picked->sortBy(fn (Event $e) => $e->nextDate?->starts_at)->values();
    }

    /**
     * Génère les séances d'un horaire récurrent :
     * recurrence = {from, to, weekdays: [1..7], slots: [{start, end}], exceptions: [Y-m-d]}.
     */
    public function regenerateRecurringDates(): void
    {
        $r = $this->recurrence ?? [];
        if (blank($r['from'] ?? null) || blank($r['to'] ?? null) || blank($r['weekdays'] ?? null) || blank($r['slots'] ?? null)) {
            return;
        }

        $weekdays = array_map('intval', $r['weekdays']);
        $exceptions = collect($r['exceptions'] ?? [])->map(fn ($d) => is_array($d) ? ($d['date'] ?? null) : $d)->filter()->all();
        $rows = [];
        for ($day = Carbon::parse($r['from'])->startOfDay(), $to = Carbon::parse($r['to']); $day->lte($to) && count($rows) < 1000; $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, $weekdays, true) || in_array($day->toDateString(), $exceptions, true)) {
                continue;
            }
            foreach ($r['slots'] as $slot) {
                if (blank($slot['start'] ?? null)) {
                    continue;
                }
                $rows[] = [
                    'event_id' => $this->id,
                    'starts_at' => $day->copy()->setTimeFromTimeString($slot['start']),
                    'ends_at' => filled($slot['end'] ?? null) ? $day->copy()->setTimeFromTimeString($slot['end']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // On garde les séances inchangées (et donc leurs inscriptions) ; seules les autres sont remplacées.
        $key = fn ($start, $end) => Carbon::parse($start)->format('Y-m-d H:i').'|'.($end ? Carbon::parse($end)->format('Y-m-d H:i') : '');
        $wanted = collect($rows)->keyBy(fn ($r) => $key($r['starts_at'], $r['ends_at']));
        $existing = $this->dates()->get()->keyBy(fn (EventDate $d) => $key($d->starts_at, $d->ends_at));
        EventDate::whereIn('id', $existing->diffKeys($wanted)->pluck('id'))->delete();
        EventDate::insert($wanted->diffKeys($existing)->values()->all());
    }
}
