<?php

namespace App\Filament\Forms;

use App\Support\Images;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Champ d'envoi d'image : la page utilise une copie redimensionnée et convertie en WebP (voir App\Support\Images).
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
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => self::save($file, $directory, $maxWidth));
    }

    public static function save(TemporaryUploadedFile $file, string $directory, int $maxWidth = Images::MAX_WIDTH): string
    {
        return Images::store($file, $directory, $maxWidth);
    }
}
