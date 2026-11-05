<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\ShareImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $event = $this->find($request, $slug);

        return view('events.show', [
            'event' => $event,
            'dates' => $event->dates,
            'upcoming' => $event->dates->filter(fn ($d) => ($d->ends_at ?? $d->starts_at->copy()->endOfDay())->isFuture())->values(),
            'preview' => ! $event->is_published,
        ]);
    }

    /** Image de partage générée (Open Graph). */
    public function shareImage(Request $request, string $slug)
    {
        $event = $this->find($request, $slug);
        $path = ShareImage::ensure($event);

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** Événement publié, ou brouillon avec le lien d'aperçu (ou pour un utilisateur connecté à l'admin). */
    private function find(Request $request, string $slug): Event
    {
        $event = Event::where('slug', $slug)->with(['place', 'keywords', 'dates', 'nextDate'])->firstOrFail();

        abort_unless(
            $event->is_published || $request->query('apercu') === $event->preview_token || $request->user()?->is_active,
            404
        );

        return $event;
    }
}
