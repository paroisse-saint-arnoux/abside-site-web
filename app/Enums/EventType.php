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

    /** @return array<string, string> valeur => libellé, pour les listes de l'admin */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
