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
        // Images remplacées ou retirées (ou fiche supprimée) : copie et original supprimés s'ils ne servent plus ailleurs.
        static::saved(fn ($model) => \App\Support\Images::cleanup(
            \Illuminate\Support\Arr::only($model->getOriginal(), ['image', 'gallery']),
            \Illuminate\Support\Arr::only($model->attributesToArray(), ['image', 'gallery']),
        ));
        static::deleted(fn ($model) => \App\Support\Images::cleanup(\Illuminate\Support\Arr::only($model->attributesToArray(), ['image', 'gallery']), []));

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
