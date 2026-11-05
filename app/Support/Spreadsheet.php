<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Event;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Exports Excel (.xlsx) de l'administration : inscrits d'un événement, carnet de contacts. */
class Spreadsheet
{
    public static function registrations(Event $event): StreamedResponse
    {
        $rows = $event->registrations()->with(['contact', 'eventDate'])->orderBy('starts_at')->orderBy('created_at')->get()
            ->map(fn ($r) => [
                $r->starts_at, $r->eventDate?->label, $r->contact->last_name, $r->contact->first_name, $r->contact->email,
                $r->contact->phone, $r->seats, $r->contact->newsletter_at !== null, $r->created_at,
            ]);

        return self::download(
            'inscrits-'.$event->slug.'.xlsx',
            ['Séance', 'Sous-titre', 'Nom', 'Prénom', 'E-mail', 'Téléphone', 'Places', 'Newsletter', 'Inscrit le'],
            $rows->all(),
            [18, 18, 20, 18, 32, 16, 8, 12, 18],
        );
    }

    /** @param Builder<Contact> $query */
    public static function contacts(Builder $query): StreamedResponse
    {
        $rows = $query->withStats()->orderBy('last_name')->orderBy('first_name')->get()
            ->map(fn (Contact $c) => [
                $c->last_name, $c->first_name, $c->email, $c->phone, (int) $c->events_count,
                $c->last_event_at ? \Illuminate\Support\Carbon::parse($c->last_event_at) : null, $c->created_at, $c->newsletter_at !== null,
            ]);

        return self::download(
            'contacts-abside-'.now()->format('Y-m-d').'.xlsx',
            ['Nom', 'Prénom', 'E-mail', 'Téléphone', 'Événements suivis', 'Dernier événement', 'Ajouté le', 'Newsletter'],
            $rows->all(),
            [20, 18, 32, 16, 12, 18, 18, 12],
        );
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, float>  $widths
     */
    public static function download(string $filename, array $headers, array $rows, array $widths = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $widths) {
            $options = new Options;
            foreach ($widths as $i => $w) {
                $options->setColumnWidth($w, $i + 1);
            }
            $writer = new Writer($options);
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers, (new Style)->setFontBold()->setBackgroundColor('F4DE9E')));
            $date = (new Style)->setFormat('dd/mm/yyyy hh:mm');
            foreach ($rows as $row) {
                $writer->addRow(new Row(array_map(fn ($v) => self::cell($v, $date), $row)));
            }
            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Les textes restent des textes (pas de formule, même s'ils commencent par « = »). */
    private static function cell(mixed $value, Style $date): Cell
    {
        return match (true) {
            $value instanceof DateTimeInterface => new Cell\DateTimeCell($value, $date),
            is_bool($value) => new Cell\StringCell($value ? 'Oui' : 'Non', null),
            is_int($value), is_float($value) => new Cell\NumericCell($value, null),
            blank($value) => new Cell\EmptyCell(null, null),
            default => new Cell\StringCell((string) $value, null),
        };
    }
}
