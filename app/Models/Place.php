<?php

namespace App\Models;

use App\Support\Geocoder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'postal_code', 'city', 'latitude', 'longitude'])]
class Place extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    protected static function booted(): void
    {
        // Coordonnées laissées vides : on les cherche à partir de l'adresse (ou on les recalcule si l'adresse change).
        static::saving(function (Place $place) {
            $addressChanged = $place->isDirty(['address', 'postal_code', 'city']) && ! $place->isDirty(['latitude', 'longitude']);
            if ((! $place->hasCoordinates() || ($place->exists && $addressChanged)) && filled($place->city)) {
                [$place->latitude, $place->longitude] = Geocoder::locate($place->fullAddress(), $place->postal_code) ?? [$place->latitude, $place->longitude];
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** « 32 rue Vernier, 06000 Nice » */
    public function fullAddress(): string
    {
        return collect([$this->address, trim($this->postal_code.' '.$this->city)])->filter()->implode(', ');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function mapsUrl(): string
    {
        $query = $this->hasCoordinates() ? $this->latitude.','.$this->longitude : $this->name.', '.$this->fullAddress();

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }
}
