<?php

namespace App\Http\Controllers;

use App\Support\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /** Nombre de séances affichées avant « Voir plus ». */
    public const PER_PAGE = 30;

    public function __invoke(Request $request)
    {
        $agenda = new Agenda($request);
        $rows = $agenda->rows();
        $limit = max(self::PER_PAGE, (int) $request->query('nb', self::PER_PAGE));

        $data = [
            'agenda' => $agenda,
            'rows' => $rows->take($limit),
            'more' => $rows->count() > $limit ? $agenda->url(['nb' => $limit + self::PER_PAGE]) : null,
            'total' => $rows->count(),
        ];

        // Filtres et déplacement de la carte sans recharger la page (resources/js/modules/agenda.js).
        if ($request->header('X-Agenda') === '1') {
            return response()->json([
                'results' => view('agenda.partials.results', $data)->render(),
                'filters' => view('agenda.partials.filters', $data)->render(),
                'pins' => $agenda->pins(),
            ]);
        }

        return view('agenda.index', [...$data, 'pins' => $agenda->pins()]);
    }
}
