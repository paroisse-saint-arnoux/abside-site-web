<?php

namespace App\Enums;

enum Registration: string
{
    case None = 'none';
    case Optional = 'optional';
    case Required = 'required';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Pas d\'inscription',
            self::Optional => 'Inscription facultative',
            self::Required => 'Inscription obligatoire',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
