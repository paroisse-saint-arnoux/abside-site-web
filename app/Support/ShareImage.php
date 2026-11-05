<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Image de partage (Open Graph, 1200 × 630) générée pour chaque événement, au gabarit « Nocturne » validé :
 * photo en plein cadre, dégradé sombre vers le bas, logo blanc en haut à gauche, type, titre et date en bas.
 * Mise en cache sur le disque public ; le nom de fichier change à chaque modification de l'événement.
 */
class ShareImage
{
    private const W = 1200;

    private const H = 630;

    public static function path(Event $event): string
    {
        return 'partage/'.$event->id.'-'.substr(md5($event->updated_at.$event->title), 0, 10).'.jpg';
    }

    /** Chemin du fichier sur le disque public, généré au besoin. */
    public static function ensure(Event $event): string
    {
        $path = self::path($event);
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            // Les anciennes versions de cet événement ne servent plus.
            foreach ($disk->files('partage') as $old) {
                if (str_starts_with(basename($old), $event->id.'-')) {
                    $disk->delete($old);
                }
            }
            $disk->put($path, self::render($event));
        }

        return $path;
    }

    public static function render(Event $event): string
    {
        $source = filled($event->image) && Storage::disk('public')->exists($event->image)
            ? Storage::disk('public')->path($event->image)
            : public_path('images/og-abside.jpg');

        $gd = (new ImageManager(new Driver))->read($source)->cover(self::W, self::H)->core()->native();
        imagealphablending($gd, true);

        // Dégradé : léger en haut (lisibilité du logo), dense en bas (texte).
        for ($y = 0; $y < self::H; $y++) {
            $t = $y / self::H;
            $opacity = $t < .3 ? .55 - $t : ($t < .82 ? .25 + ($t - .3) * 1.37 : .96);
            imageline($gd, 0, $y, self::W, $y, imagecolorallocatealpha($gd, 14, 26, 38, (int) round(127 * (1 - min(1, $opacity)))));
        }

        $logo = imagecreatefrompng(public_path('images/logo-abside-blanc.png'));
        $lw = (int) round(imagesx($logo) * 96 / imagesy($logo));
        imagecopyresampled($gd, $logo, 48, 32, 0, 0, $lw, 96, imagesx($logo), imagesy($logo));

        $white = imagecolorallocate($gd, 255, 255, 255);
        $display = resource_path('fonts/Marcellus-Regular.ttf');
        $body = resource_path('fonts/Figtree-Medium.ttf');

        // Du bas vers le haut : date, titre (jusqu'à 2 lignes), étiquette du type.
        $date = $event->nextDate ?? $event->dates->first();
        $line = collect([$date?->dayLabel(), $event->place?->name.($event->place ? ', '.$event->place->city : '')])->filter()->implode(' · ');
        $y = self::H - 40;
        imagettftext($gd, 20, 0, 48, $y, $white, $body, $line);

        $size = 64;
        $lines = self::wrap($event->title, $display, $size, self::W - 96);
        while (count($lines) > 2 && $size > 40) {
            $size -= 6;
            $lines = self::wrap($event->title, $display, $size, self::W - 96);
        }
        $lines = array_slice($lines, 0, 2);
        $y -= 46;
        foreach (array_reverse($lines) as $text) {
            imagettftext($gd, $size, 0, 46, $y, $white, $display, $text);
            $y -= (int) round($size * 1.28);
        }

        [$r, $g, $b] = sscanf($event->type->color(), '#%02x%02x%02x');
        $label = $event->type->label();
        $box = imagettfbbox(15, 0, $body, $label);
        $chipW = $box[2] - $box[0] + 32;
        $chipY = $y - 4;
        self::pill($gd, 48, $chipY - 30, $chipW, 34, imagecolorallocate($gd, $r, $g, $b));
        imagettftext($gd, 15, 0, 64, $chipY - 7, $white, $body, $label);

        ob_start();
        imagejpeg($gd, null, 86);

        return (string) ob_get_clean();
    }

    /** @return array<int, string> */
    private static function wrap(string $text, string $font, int $size, int $max): array
    {
        $lines = [];
        $current = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $font, $try);
            if ($box[2] - $box[0] > $max && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        $lines[] = $current;

        return $lines;
    }

    private static function pill(\GdImage $gd, int $x, int $y, int $w, int $h, int $color): void
    {
        $r = intdiv($h, 2);
        imagefilledrectangle($gd, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledellipse($gd, $x + $r, $y + $r, $h, $h, $color);
        imagefilledellipse($gd, $x + $w - $r, $y + $r, $h, $h, $color);
    }
}
