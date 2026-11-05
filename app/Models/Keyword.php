<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'has_page', 'intro', 'body', 'image', 'image_alt', 'gallery', 'when_label', 'where_label'])]
class Keyword extends Model
{
    protected function casts(): array
    {
        return ['has_page' => 'boolean', 'gallery' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Keyword $keyword) {
            $keyword->slug = filled($keyword->slug) ? Str::slug($keyword->slug) : Str::slug($keyword->name);
        });
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }

    /** Un mot clé avec une page mène à sa page ; sinon à l'agenda filtré. */
    public function url(): string
    {
        return $this->has_page ? route('keywords.show', $this->slug) : route('agenda', ['mot-cle' => $this->slug]);
    }
}
