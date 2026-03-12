<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ErrorReport;
use App\Models\Problema;

class ErrorReportController extends Controller
{
    /**
     * Store a new error report (any authenticated user).
     */
    public function store(Request $request, $problemaId)
    {
        $request->validate([
            'reporte' => 'required|string',
            'tipo'    => 'required|string|size:9',
        ]);

        ErrorReport::create([
            'problema_id' => $problemaId,
            'user_id'     => Auth::id(),
            'reporte'     => $request->input('reporte'),
            'tipo'        => $request->input('tipo'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * List problems with pending error reports (editors/admins only).
     */
    public function index()
    {
        $problemas = Problema::whereHas('errorReports', fn($q) => $q->where('solved', false))
            ->with(['errorReports' => fn($q) => $q->where('solved', false)->with('user'), 'tags'])
            ->orderBy('id')
            ->paginate(20);

        // Attach aggregated tipo to each problema
        $tipoLabels = [
            0 => 'Nivel',
            1 => 'Tema',
            2 => 'Año académico',
            3 => 'Título',
            4 => 'Enunciado',
            5 => 'Pistas',
            6 => 'Solución',
            7 => 'Fuente',
            8 => 'Imágenes',
        ];

        foreach ($problemas as $problema) {
            $agregado = '000000000';
            foreach ($problema->errorReports as $er) {
                for ($i = 0; $i < 9; $i++) {
                    if (($er->tipo[$i] ?? '0') === '1') {
                        $agregado[$i] = '1';
                    }
                }
            }
            $problema->tipoAgregado = $agregado;
        }

        return view('problemas.con-errores', compact('problemas', 'tipoLabels'));
    }
}
