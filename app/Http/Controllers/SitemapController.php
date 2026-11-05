<?php

namespace App\Http\Controllers;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('about'), 'lastmod' => null],
            ['loc' => route('support'), 'lastmod' => null],
            ['loc' => route('contact'), 'lastmod' => null],
        ]);

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
