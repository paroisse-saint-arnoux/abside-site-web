<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Keyword;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

/**
 * Toutes les images envoyées sont redimensionnées et converties en WebP avant d'être stockées sur le disque public.
 *
 * L'original est gardé à part (disque privé « local », dossier image-sources/, même nom que la copie publique) avec
 * un fichier .json (largeur maximale, dernière retouche) : le recadrage et la rotation repartent toujours de l'original
 * en pleine résolution. Quand une image n'est plus utilisée nulle part, sa copie et son original sont supprimés.
 */
class Images
{
    public const MAX_WIDTH = 1920;

    private const SOURCES = 'image-sources';

    public static function store(UploadedFile|string $file, string $directory, int $maxWidth = self::MAX_WIDTH, ?string $name = null): string
    {
        $source = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $name ??= $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);

        $path = trim($directory, '/').'/'.(Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'image').'-'.Str::random(6).'.webp';
        self::write($source, $path, $maxWidth);

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'jpg';
        $local = Storage::disk('local');
        $local->put(self::sourceBase($path).'.'.$extension, file_get_contents($source));
        $local->put(self::sourceBase($path).'.json', json_encode(['max_width' => $maxWidth]));

        return $path;
    }

    /** Écrit (ou réécrit) à $path la version retraitée de l'image source. */
    public static function write(string $source, string $path, int $maxWidth = self::MAX_WIDTH): void
    {
        $image = (new ImageManager(new Driver))->read($source)->scaleDown(width: $maxWidth);
        Storage::disk('public')->put($path, (string) $image->toWebp(quality: 82));
    }

    /** Chemin (disque local) de l'original d'une image publique, s'il a été gardé. */
    public static function sourcePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }
        $base = self::sourceBase($path);
        foreach (Storage::disk('local')->files(dirname($base)) as $file) {
            if (str_starts_with($file, $base.'.') && ! str_ends_with($file, '.json')) {
                return $file;
            }
        }

        return null;
    }

    /** Réglages gardés avec l'original : max_width et la dernière retouche (zone, rotation, miroir). */
    public static function meta(string $path): array
    {
        $json = Storage::disk('local')->get(self::sourceBase($path).'.json');

        return $json ? (array) json_decode($json, true) : [];
    }

    /**
     * Recadre / pivote à partir de l'original et renvoie le chemin de la nouvelle copie publique (nouveau nom,
     * pour que les navigateurs et les images de partage ne gardent pas l'ancienne). L'ancienne copie est supprimée
     * au moment où la page est enregistrée, comme pour un remplacement.
     *
     * @param  array{x?: float, y?: float, width?: float, height?: float, rotate?: float, scaleX?: float, scaleY?: float}  $edit
     */
    public static function edit(string $path, array $edit, ?int $maxWidth = null): string
    {
        $local = Storage::disk('local');
        $source = self::sourcePath($path);
        if (! $source) {
            // Image antérieure à la conservation des originaux : sa copie publique devient l'original.
            throw_unless(Storage::disk('public')->exists($path), new InvalidArgumentException('Image introuvable.'));
            $source = self::sourceBase($path).'.webp';
            $local->put($source, Storage::disk('public')->get($path));
        }
        $meta = self::meta($path);
        $maxWidth ??= (int) ($meta['max_width'] ?? self::MAX_WIDTH);

        $image = (new ImageManager(new Driver))->read($local->path($source));
        // Ordre de Cropper.js : miroir, rotation par quarts de tour (sens horaire), puis recadrage.
        if (($edit['scaleX'] ?? 1) < 0) {
            $image->flop();
        }
        if (($edit['scaleY'] ?? 1) < 0) {
            $image->flip();
        }
        $rotate = ((int) round(($edit['rotate'] ?? 0) / 90) * 90) % 360;
        if ($rotate !== 0) {
            $image->rotate(-$rotate);
        }
        if (isset($edit['width'], $edit['height'])) {
            $x = max(0, (int) round($edit['x'] ?? 0));
            $y = max(0, (int) round($edit['y'] ?? 0));
            $width = min((int) round($edit['width']), $image->width() - $x);
            $height = min((int) round($edit['height']), $image->height() - $y);
            if ($width < 10 || $height < 10) {
                throw new InvalidArgumentException('La zone à recadrer est trop petite.');
            }
            if ($width < $image->width() || $height < $image->height()) {
                $image->crop($width, $height, $x, $y);
            }
        }

        $new = preg_replace('/-[A-Za-z0-9]{6}\.webp$/', '', $path).'-'.Str::random(6).'.webp';
        Storage::disk('public')->put($new, (string) $image->scaleDown(width: $maxWidth)->toWebp(quality: 82));

        // L'original (inchangé) suit la nouvelle copie ; l'ancienne copie garde un lien vers lui jusqu'à l'enregistrement.
        $local->copy($source, self::sourceBase($new).'.'.pathinfo($source, PATHINFO_EXTENSION));
        $local->put(self::sourceBase($new).'.json', json_encode(['max_width' => $maxWidth, 'edit' => $edit]));

        return $new;
    }

    /** Supprime la copie publique et l'original. */
    public static function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
        $local = Storage::disk('local');
        $local->delete(array_filter([self::sourcePath($path), self::sourceBase($path).'.json']));
    }

    /**
     * Après un enregistrement : supprime les images qui étaient présentes avant et ne le sont plus,
     * sauf si une autre page les utilise encore (événement dupliqué, par exemple).
     */
    public static function cleanup(mixed $before, mixed $after): void
    {
        $removed = array_diff(self::pathsIn($before), self::pathsIn($after));
        foreach ($removed as $path) {
            if (! self::isUsed($path)) {
                self::delete($path);
            }
        }
    }

    /** Chemins d'images (copies publiques WebP) trouvés dans une valeur : texte, tableau, JSON. */
    public static function pathsIn(mixed $value): array
    {
        $paths = [];
        array_walk_recursive($value, function ($v) use (&$paths) {
            if (is_string($v) && preg_match('#^[a-z0-9][a-z0-9/_-]*\.webp$#i', $v)) {
                $paths[] = $v;
            }
        });
        if (! is_array($value) && is_string($value) && preg_match('#^[a-z0-9][a-z0-9/_-]*\.webp$#i', $value)) {
            $paths[] = $value;
        }

        return array_values(array_unique($paths));
    }

    /** L'image est-elle encore utilisée par un événement, un mot clé ou un réglage ? */
    public static function isUsed(string $path): bool
    {
        // Le JSON stocke les « / » échappés : on cherche le nom du fichier, unique grâce à son suffixe aléatoire.
        $like = '%'.basename($path).'%';

        return Event::where('image', $path)->orWhere('poster', $path)->orWhere('gallery', 'like', $like)->orWhere('partners', 'like', $like)->orWhere('body', 'like', $like)->exists()
            || Keyword::where('image', $path)->orWhere('gallery', 'like', $like)->orWhere('body', 'like', $like)->exists()
            || Setting::where('value', 'like', $like)->exists();
    }

    /** Vignette JPEG (pour montrer les images à Claude sans lui envoyer les fichiers en pleine taille). */
    public static function thumbnail(string $path, int $width = 400): string
    {
        $image = (new ImageManager(new Driver))->read(Storage::disk('public')->path($path));

        return (string) $image->scaleDown(width: $width)->toJpeg(quality: 70);
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : Storage::disk('public')->url($path);
    }

    private static function sourceBase(string $path): string
    {
        return self::SOURCES.'/'.preg_replace('/\.webp$/', '', $path);
    }
}
