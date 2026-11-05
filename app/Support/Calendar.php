<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventDate;
use Illuminate\Support\Carbon;

/** « Ajouter à mon agenda » : fichier .ics (Apple, Outlook…) et lien Google Agenda pour une séance. */
class Calendar
{
    public static function ics(Event $event, EventDate $date, ?string $uid = null): string
    {
        $utc = fn (Carbon $d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//L\'Abside//abside-culture.fr//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.($uid ?? 'seance-'.$date->id).'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$utc(now()),
            'DTSTART:'.$utc($date->starts_at),
            'DTEND:'.$utc($date->endsAtOrDefault()),
            'SUMMARY:'.self::escape($event->title),
            'DESCRIPTION:'.self::escape(trim(($event->intro ? $event->intro."\n\n" : '').$event->url())),
            'URL:'.$event->url(),
        ];
        if ($event->place) {
            $lines[] = 'LOCATION:'.self::escape($event->place->name.', '.$event->place->fullAddress());
            if ($event->place->hasCoordinates()) {
                $lines[] = 'GEO:'.$event->place->latitude.';'.$event->place->longitude;
            }
        }
        $lines = [...$lines, 'END:VEVENT', 'END:VCALENDAR'];

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public static function googleUrl(Event $event, EventDate $date): string
    {
        $utc = fn (Carbon $d) => $d->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event->title,
            'dates' => $utc($date->starts_at).'/'.$utc($date->endsAtOrDefault()),
            'details' => trim(($event->intro ? $event->intro."\n\n" : '').$event->url()),
            'location' => $event->place ? $event->place->name.', '.$event->place->fullAddress() : '',
        ]);
    }

    private static function escape(string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Lignes de 75 octets maximum (RFC 5545), sans couper un caractère multi-octet. */
    private static function fold(string $line): string
    {
        $out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
