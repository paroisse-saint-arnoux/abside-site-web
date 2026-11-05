<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Keyword;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $events = Event::published()->get(['slug', 'updated_at']);

        $urls = collect([
            ['loc' => route('home'), 'lastmod' => $events->max('updated_at')],
            ['loc' => route('agenda'), 'lastmod' => $events->max('updated_at')],
            ...collect(EventType::cases())->map(fn (EventType $t) => ['loc' => route('agenda', ['type' => $t->value]), 'lastmod' => null]),
            ...$events->map(fn (Event $e) => ['loc' => $e->url(), 'lastmod' => $e->updated_at]),
            ...Keyword::where('has_page', true)->get()->map(fn (Keyword $k) => ['loc' => $k->url(), 'lastmod' => $k->updated_at]),
            ['loc' => route('about'), 'lastmod' => null],
            ['loc' => route('support'), 'lastmod' => null],
            ['loc' => route('contact'), 'lastmod' => null],
        ]);

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
