<?php

namespace App\Http\Controllers;

use App\Models\EventDate;
use App\Models\Keyword;

class KeywordController extends Controller
{
    /** Page d'un mot clé ; sans page, on renvoie vers l'agenda filtré sur ce mot clé. */
    public function show(string $slug)
    {
        $keyword = Keyword::where('slug', $slug)->firstOrFail();

        if (! $keyword->has_page) {
            return redirect()->route('agenda', ['mot-cle' => $keyword->slug]);
        }

        $dates = EventDate::upcoming()
            ->whereHas('event', fn ($q) => $q->published()->whereHas('keywords', fn ($k) => $k->whereKey($keyword->id)))
            ->with(['event.place', 'event.keywords'])
            ->orderBy('starts_at')
            ->get();

        return view('keywords.show', ['keyword' => $keyword, 'dates' => $dates]);
    }
}
