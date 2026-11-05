<?php

namespace App\Filament\Forms;

use App\Support\Images;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Champ d'envoi d'image : la page utilise une copie redimensionnée et convertie en WebP, l'original est gardé
 * (voir App\Support\Images). Glisser-déposer sur tout le champ et bouton « Remplacer » : resources/js/admin/images.js.
 * « Recadrer / pivoter » repart de l'original ; l'ancienne copie est supprimée à l'enregistrement de la page.
 */
class ImageUpload
{
    public static function make(string $name, string $directory, int $maxWidth = Images::MAX_WIDTH): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(20 * 1024)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->placeholder('Glissez une image ici ou <span class="filepond--label-action">parcourez vos fichiers</span>')
            ->extraAttributes(['data-image-upload' => true])
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => self::save($file, $directory, $maxWidth))
            ->hintAction(self::editAction($maxWidth));
    }

    public static function save(TemporaryUploadedFile $file, string $directory, int $maxWidth = Images::MAX_WIDTH): string
    {
        return Images::store($file, $directory, $maxWidth);
    }

    /** Chemin de l'image enregistrée (null tant qu'une nouvelle image n'est pas envoyée). */
    public static function storedPath(mixed $state): ?string
    {
        $path = is_array($state) ? collect($state)->first() : $state;

        return is_string($path) && Storage::disk('public')->exists($path) ? $path : null;
    }

    private static function editAction(int $maxWidth): Action
    {
        return Action::make('editImage')
            ->label('Recadrer / pivoter')
            ->icon('heroicon-o-scissors')
            ->visible(fn (FileUpload $component) => self::storedPath($component->getState()) !== null)
            ->modalHeading('Recadrer ou pivoter l\'image')
            ->modalDescription('La retouche part toujours de l\'image d\'origine, en pleine résolution : vous pouvez revenir dessus à tout moment.')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel('Appliquer')
            ->schema(fn (FileUpload $component) => [
                ViewField::make('edit')->hiddenLabel()->view('filament.image-cropper')->viewData(self::cropperData(self::storedPath($component->getState()))),
            ])
            ->action(function (array $data, FileUpload $component) use ($maxWidth) {
                $path = self::storedPath($component->getState());
                try {
                    $new = Images::edit($path, (array) ($data['edit'] ?? []), $maxWidth);
                } catch (\InvalidArgumentException $e) {
                    Notification::make()->title('Retouche impossible')->body($e->getMessage())->danger()->send();

                    return;
                }
                $component->state([(string) Str::uuid() => $new]);
                Notification::make()->title('Image retouchée')->body('Enregistrez la page pour la mettre en ligne.')->success()->send();
            });
    }

    private static function cropperData(?string $path): array
    {
        if (! $path) {
            return ['url' => null, 'edit' => null];
        }
        $source = Images::sourcePath($path);

        return [
            'url' => $source ? Storage::disk('local')->temporaryUrl($source, now()->addHour()) : Storage::disk('public')->url($path),
            // Sans original gardé, on repart de la copie actuelle : pas de retouche précédente à restaurer.
            'edit' => $source ? (Images::meta($path)['edit'] ?? null) : null,
        ];
    }
}
