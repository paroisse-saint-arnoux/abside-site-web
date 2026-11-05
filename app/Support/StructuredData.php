<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Données structurées (JSON-LD, schema.org) communes à toutes les pages, en un seul « @graph » :
 * l'association (Organization), le site (WebSite) et la page (WebPage ou sous-type) avec son fil d'Ariane.
 * Les pages peuvent ajouter des nœuds (liste d'événements…) ; les fiches événement ont en plus leur propre bloc Event
 * (events/partials/json-ld), qui renvoie à l'association par son @id.
 *
 * Paramètre $schema du layout : ['type' => 'CollectionPage', 'breadcrumbs' => [[nom, url], …], 'nodes' => [...], 'about' => [...]].
 */
class StructuredData
{
    public const SIREN = '992749168';

    public static function organizationId(): string
    {
        return rtrim(route('home'), '/').'/#organisation';
    }

    public static function websiteId(): string
    {
        return rtrim(route('home'), '/').'/#site';
    }

    /** @param array{type?: string, breadcrumbs?: array<int, array{0: string, 1: string}>, nodes?: array<int, array>, about?: mixed, mainEntity?: mixed} $schema */
    public static function graph(array $schema, string $title, ?string $description, string $url, ?string $image): array
    {
        $page = array_filter([
            '@type' => $schema['type'] ?? 'WebPage',
            '@id' => $url.'#page',
            'url' => $url,
            'name' => $title,
            'description' => $description ?: null,
            'inLanguage' => 'fr-FR',
            'isPartOf' => ['@id' => self::websiteId()],
            'publisher' => ['@id' => self::organizationId()],
            'primaryImageOfPage' => $image ? ['@type' => 'ImageObject', 'url' => $image] : null,
            'about' => $schema['about'] ?? null,
            'mainEntity' => $schema['mainEntity'] ?? null,
            'breadcrumb' => filled($schema['breadcrumbs'] ?? null) ? ['@id' => $url.'#ariane'] : null,
        ]);

        $nodes = [self::organization(), self::website(), $page];
        if (filled($schema['breadcrumbs'] ?? null)) {
            $nodes[] = self::breadcrumbs($schema['breadcrumbs'], $url.'#ariane');
        }

        return ['@context' => 'https://schema.org', '@graph' => [...$nodes, ...($schema['nodes'] ?? [])]];
    }

    public static function organization(): array
    {
        $site = Setting::get('site', []);
        $address = self::address($site['address'] ?? null);

        return array_filter([
            '@type' => 'Organization',
            '@id' => self::organizationId(),
            'name' => config('app.name'),
            'legalName' => $site['legal_name'] ?? null,
            'description' => $site['description'] ?? null,
            'url' => route('home'),
            'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo-abside.png')],
            'image' => asset('images/og-abside.jpg'),
            'email' => $site['email'] ?? null,
            'telephone' => self::phone($site['phone'] ?? null),
            'address' => $address,
            'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Alpes-Maritimes'],
            'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'SIREN', 'value' => self::SIREN],
            'contactPoint' => filled($site['email'] ?? null) || filled($site['phone'] ?? null) ? array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'email' => $site['email'] ?? null,
                'telephone' => self::phone($site['phone'] ?? null),
                'availableLanguage' => 'French',
                'url' => route('contact'),
            ]) : null,
            'sameAs' => array_values(array_filter([$site['facebook_url'] ?? null, $site['instagram_url'] ?? null])) ?: null,
        ]);
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => route('home'),
            'name' => config('app.name'),
            'inLanguage' => 'fr-FR',
            'publisher' => ['@id' => self::organizationId()],
        ];
    }

    /** @param array<int, array{0: string, 1: string}> $items [nom, url] du plus général au plus précis */
    public static function breadcrumbs(array $items, string $id): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => collect([['Accueil', route('home')], ...$items])->values()
                ->map(fn ($item, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1]])->all(),
        ];
    }

    /**
     * Liste d'événements (pages « résumé » : agenda, mot clé, accueil) : Google la relie aux fiches événement.
     *
     * @param  iterable<\App\Models\Event>  $events
     */
    public static function eventList(iterable $events, string $id, ?string $name = null): array
    {
        return array_filter([
            '@type' => 'ItemList',
            '@id' => $id,
            'name' => $name,
            'itemListElement' => collect($events)->unique('id')->values()
                ->map(fn ($event, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $event->url(), 'name' => $event->title])->all(),
        ]);
    }

    /** « 23 avenue Sévigné, 06100 Nice » → adresse postale. */
    public static function address(?string $text): ?array
    {
        if (blank($text)) {
            return null;
        }
        if (preg_match('/^(.+?),\s*(\d{5})\s+(.+)$/u', trim($text), $m)) {
            return ['@type' => 'PostalAddress', 'streetAddress' => $m[1], 'postalCode' => $m[2], 'addressLocality' => $m[3], 'addressRegion' => 'Alpes-Maritimes', 'addressCountry' => 'FR'];
        }

        return ['@type' => 'PostalAddress', 'streetAddress' => $text, 'addressCountry' => 'FR'];
    }

    /** « 06 14 47 30 42 » → « +33 6 14 47 30 42 ». */
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (strlen($digits) !== 10 || $digits[0] !== '0') {
            return $phone ?: null;
        }

        return '+33 '.$digits[1].' '.implode(' ', str_split(substr($digits, 2), 2));
    }
}
