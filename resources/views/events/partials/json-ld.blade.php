@php
    use App\Enums\EventType;
    use App\Models\Setting;
    // Une entrée par séance à venir (Google attend une date par événement), sinon la dernière séance.
    $schemaType = match ($event->type) {
        EventType::Theatre => 'TheaterEvent',
        EventType::Conference => 'EducationEvent',
        EventType::Concert => 'MusicEvent',
        EventType::Expo => 'ExhibitionEvent',
        default => 'Event',
    };
    $place = $event->place;
    $site = Setting::get('site', []);
    $items = ($upcoming->isNotEmpty() ? $upcoming : $dates->take(-1))->take(20)->map(fn ($d) => array_filter([
        '@context' => 'https://schema.org',
        '@type' => $schemaType,
        'name' => $d->label ? $event->title.' — '.$d->label : $event->title,
        'description' => $event->intro,
        'startDate' => $d->starts_at->toIso8601String(),
        'endDate' => $d->ends_at?->toIso8601String(),
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'image' => array_values(array_filter([$event->shareImageUrl(), $event->image ? Storage::url($event->image) : null])),
        'url' => $event->url(),
        'isAccessibleForFree' => $event->is_free,
        'location' => $place ? array_filter([
            '@type' => 'Place',
            'name' => $place->name,
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $place->address, 'postalCode' => $place->postal_code, 'addressLocality' => $place->city, 'addressCountry' => 'FR'],
            'geo' => $place->hasCoordinates() ? ['@type' => 'GeoCoordinates', 'latitude' => $place->latitude, 'longitude' => $place->longitude] : null,
        ]) : null,
        'organizer' => array_filter(['@type' => 'Organization', '@id' => \App\Support\StructuredData::organizationId(), 'name' => config('app.name'), 'url' => route('home'), 'email' => $site['email'] ?? null]),
        'inLanguage' => 'fr-FR',
        'keywords' => $event->keywords->pluck('name')->implode(', ') ?: null,
        // Séance complète (jauge atteinte) : « SoldOut ».
        'offers' => $event->is_free ? ['@type' => 'Offer', 'price' => 0, 'priceCurrency' => 'EUR', 'url' => $event->url(),
            'availability' => ($remaining[$d->id] ?? null) === 0 ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'] : null,
    ]))->values();
@endphp
<script type="application/ld+json">{!! json_encode($items->count() === 1 ? $items->first() : $items->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
