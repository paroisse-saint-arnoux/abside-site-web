<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Markdown;
use App\Support\StructuredData;

class PageController extends Controller
{
    public function about()
    {
        // Page « À propos » : elle décrit l'association.
        return $this->editable('about', ['type' => 'AboutPage', 'mainEntity' => ['@id' => StructuredData::organizationId()]]);
    }

    public function support()
    {
        return $this->editable('support', ['about' => ['@id' => StructuredData::organizationId()]]);
    }

    public function legal()
    {
        return $this->page('Mentions légales', 'legal.mentions');
    }

    public function privacy()
    {
        return $this->page('Politique de confidentialité', 'legal.privacy');
    }

    /** Page entièrement éditable dans Admin > Réglages du site > Pages (titre, chapô, texte). */
    private function editable(string $key, array $schema = [])
    {
        $page = Setting::get("pages.{$key}", []);

        return view('page', [
            'title' => $page['title'] ?? '',
            'intro' => $page['intro'] ?? null,
            'html' => Markdown::render($page['body'] ?? null),
            'schema' => $schema,
        ]);
    }

    private function page(string $title, string $setting)
    {
        return view('page', ['title' => $title, 'html' => Markdown::render(Setting::get($setting))]);
    }
}
