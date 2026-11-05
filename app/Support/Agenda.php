<?php

namespace App\Support;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Keyword;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Recherche de l'agenda : une ligne par séance, filtres type / mois / mot clé / lieu / zone de la carte.
 * Le volume d'une association (quelques centaines de séances par an) permet de filtrer en mémoire,
 * ce qui rend simple le calcul des compteurs : chaque filtre compte ses résultats avec les autres filtres actifs.
 */
class Agenda
{
    public const FILTERS = ['type', 'mois', 'mot-cle', 'lieu', 'zone'];

    /** @var array<string, ?string> */
    public array $filters;

    public bool $past;

    /** @var Collection<int, EventDate> séances de la période (à venir ou passées), avant filtres */
    private Collection $pool;

    public function __construct(Request $request)
    {
        $this->past = $request->boolean('passes');
        $this->filters = collect(self::FILTERS)->mapWithKeys(fn ($f) => [$f => $request->filled($f) ? (string) $request->query($f) : null])->all();

        $this->pool = EventDate::query()
            ->{$this->past ? 'past' : 'upcoming'}()
            ->whereHas('event', fn ($q) => $q->published())
            ->with(['event.place', 'event.keywords'])
            ->orderBy('starts_at', $this->past ? 'desc' : 'asc')
            ->get();
    }

    /** Séances qui passent tous les filtres, sauf celui qu'on demande d'ignorer (pour les compteurs). */
    private function matching(?string $skip = null): Collection
    {
        $f = $this->filters;
        $zone = $this->zone();

        return $this->pool->filter(function (EventDate $d) use ($f, $skip, $zone) {
            $e = $d->event;

            return ($skip === 'type' || ! $f['type'] || $e->type->value === $f['type'])
                && ($skip === 'mois' || ! $f['mois'] || $d->starts_at->format('Y-m') === $f['mois'])
                && ($skip === 'mot-cle' || ! $f['mot-cle'] || $e->keywords->contains('slug', $f['mot-cle']))
                && ($skip === 'lieu' || ! $f['lieu'] || (string) $e->place_id === $f['lieu'])
                && ($skip === 'zone' || ! $zone || $this->inZone($e->place, $zone));
        });
    }

    public function rows(): Collection
    {
        return $this->matching()->values();
    }

    /** @return ?array{0: float, 1: float, 2: float, 3: float} ouest, sud, est, nord */
    public function zone(): ?array
    {
        $parts = array_map('floatval', explode(',', (string) $this->filters['zone']));

        return count($parts) === 4 ? $parts : null;
    }

    private function inZone(?Place $place, array $zone): bool
    {
        [$w, $s, $e, $n] = $zone;

        return $place?->hasCoordinates() && $place->longitude >= $w && $place->longitude <= $e && $place->latitude >= $s && $place->latitude <= $n;
    }

    /** Boutons d'un filtre : libellé, nombre, actif, adresse (un second clic retire le filtre). */
    private function facet(string $key, Collection $values): Collection
    {
        $base = $this->matching($key);

        return $values->map(function (array $v) use ($key, $base) {
            [$value, $label, $test] = $v;
            $count = $base->filter($test)->count();
            $active = $this->filters[$key] === (string) $value;

            return (object) [
                'value' => $value, 'label' => $label, 'count' => $count, 'active' => $active,
                'url' => $this->url([$key => $active ? null : $value]),
            ];
        })->filter(fn ($b) => $b->count > 0 || $b->active)->values();
    }

    public function types(): Collection
    {
        return $this->facet('type', collect(EventType::cases())->map(fn (EventType $t) => [
            $t->value, $t->menuLabel(), fn (EventDate $d) => $d->event->type === $t,
        ]));
    }

    public function keywords(): Collection
    {
        $keywords = $this->pool->flatMap(fn (EventDate $d) => $d->event->keywords)->unique('id')->sortBy('name');

        return $this->facet('mot-cle', $keywords->map(fn (Keyword $k) => [
            $k->slug, $k->name, fn (EventDate $d) => $d->event->keywords->contains('id', $k->id),
        ]));
    }

    public function places(): Collection
    {
        $places = $this->pool->map(fn (EventDate $d) => $d->event->place)->filter()->unique('id')->sortBy('name');

        return $this->facet('lieu', $places->map(fn (Place $p) => [
            $p->id, $p->name, fn (EventDate $d) => $d->event->place_id === $p->id,
        ]));
    }

    /** Mois proposés dans la liste déroulante. */
    public function months(): Collection
    {
        return $this->matching('mois')
            ->groupBy(fn (EventDate $d) => $d->starts_at->format('Y-m'))
            ->map(fn ($dates, $key) => (object) [
                'value' => $key,
                'label' => \Illuminate\Support\Str::ucfirst($dates->first()->starts_at->translatedFormat('F Y')),
                'count' => $dates->count(),
            ])
            ->values();
    }

    /** Marqueurs de la carte : lieux des séances qui passent les autres filtres (la zone n'en retire aucun). */
    public function pins(): array
    {
        return $this->matching('zone')
            ->groupBy(fn (EventDate $d) => $d->event->place_id)
            ->map(function ($dates) {
                $place = $dates->first()->event->place;
                if (! $place?->hasCoordinates()) {
                    return null;
                }
                $type = $dates->first()->event->type;

                return [
                    'name' => $place->name, 'city' => $place->city, 'lat' => $place->latitude, 'lng' => $place->longitude,
                    'count' => $dates->count(), 'color' => $type->color(), 'icon' => $type->icon(),
                ];
            })
            ->filter()->values()->all();
    }

    /** Événement en exergue : coché « En exergue sur l'agenda », à venir, et qui correspond aux filtres. */
    public function spotlight(): ?EventDate
    {
        if ($this->past) {
            return null;
        }

        return $this->matching()->first(fn (EventDate $d) => $d->event->is_spotlight);
    }

    /** Filtres actifs, sous forme d'étiquettes qu'on peut retirer. */
    public function chips(): Collection
    {
        $labels = [
            'type' => fn ($v) => EventType::tryFrom($v)?->menuLabel(),
            'mois' => fn ($v) => $this->months()->firstWhere('value', $v)?->label ?? $v,
            'mot-cle' => fn ($v) => Keyword::firstWhere('slug', $v)?->name ?? $v,
            'lieu' => fn ($v) => Place::find($v)?->name,
            'zone' => fn () => 'Recherche par lieu',
        ];

        return collect($this->filters)->filter()->map(fn ($v, $k) => (object) [
            'key' => $k, 'label' => $labels[$k]($v) ?? $v, 'url' => $this->url([$k => null]),
        ])->values();
    }

    public function hasFilters(): bool
    {
        return collect($this->filters)->filter()->isNotEmpty();
    }

    /** Adresse de l'agenda avec les filtres actuels modifiés. */
    public function url(array $changes = [], ?bool $past = null): string
    {
        $query = array_filter([...$this->filters, ...$changes], fn ($v) => filled($v));
        if ($past ?? $this->past) {
            $query['passes'] = 1;
        }

        return route('agenda', $query);
    }
}
