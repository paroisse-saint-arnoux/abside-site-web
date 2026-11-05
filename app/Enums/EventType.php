<?php

namespace App\Enums;

/**
 * Les six types d'événement : menu principal, filtre de l'agenda, couleur des étiquettes.
 * « Événements » regroupe tout ce qui n'entre pas dans les cinq autres.
 */
enum EventType: string
{
    case Theatre = 'theatre';
    case Conference = 'conference';
    case Concert = 'concert';
    case Expo = 'expo';
    case Visite = 'visite';
    case Evenement = 'evenement';

    /** Libellé d'un événement (« Théâtre », « Conférence »…). */
    public function label(): string
    {
        return match ($this) {
            self::Theatre => 'Théâtre',
            self::Conference => 'Conférence',
            self::Concert => 'Concert',
            self::Expo => 'Expo',
            self::Visite => 'Visite guidée',
            self::Evenement => 'Événement',
        };
    }

    /** Libellé du menu et des filtres (« Conférences », « Visites guidées »…). */
    public function menuLabel(): string
    {
        return match ($this) {
            self::Theatre => 'Théâtre',
            self::Conference => 'Conférences',
            self::Concert => 'Concerts',
            self::Expo => 'Expo',
            self::Visite => 'Visites guidées',
            self::Evenement => 'Événements',
        };
    }

    /** Couleur de l'étiquette (texte blanc dessus), identique à $type-colors dans _variables.scss. */
    public function color(): string
    {
        return match ($this) {
            self::Theatre => '#1F4E8C',
            self::Conference => '#B4492C',
            self::Concert => '#6A3D7C',
            self::Expo => '#7A5A12',
            self::Visite => '#2F5D50',
            self::Evenement => '#152E47',
        };
    }

    /** Tracé de l'icône (SVG 24×24, trait) : marqueurs de la carte. */
    public function icon(): string
    {
        return match ($this) {
            self::Theatre => 'M4 4h16v8a8 8 0 0 1-16 0z M9 10h.01 M15 10h.01 M9 15c2 2 4 2 6 0',
            self::Conference => 'M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3z M5 11a7 7 0 0 0 14 0 M12 18v3',
            self::Concert => 'M9 18V5l12-2v13 M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0z M21 16a3 3 0 1 1-6 0 3 3 0 0 1 6 0z',
            self::Expo => 'M3 4h18v16H3z M3 16l5-5 4 4 3-3 6 6',
            self::Visite => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M15.5 8.5l-2 5-5 2 2-5z',
            self::Evenement => 'M12 3l2.7 5.6 6.3.9-4.5 4.4 1 6.1-5.5-2.9-5.5 2.9 1-6.1L3 9.5l6.3-.9z',
        };
    }

    /** @return array<string, string> valeur => libellé, pour les listes de l'admin */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
