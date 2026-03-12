<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MetodoErrorReport;
use App\Models\Metodo;

class MetodoErrorReportController extends Controller
{
    /**
     * Store a new error report (any authenticated user).
     */
    public function store(Request $request, $metodoId)
    {
        $request->validate([
            'reporte' => 'required|string',
            'tipo'    => 'required|string|size:5',
        ]);

        MetodoErrorReport::create([
            'metodo_id' => $metodoId,
            'user_id'   => Auth::id(),
            'reporte'   => $request->input('reporte'),
            'tipo'      => $request->input('tipo'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * List methods with pending error reports (editors/admins only).
     */
    public function index()
    {
        $tipoLabels = [
            0 => 'Título',
            1 => 'Tema',
            2 => 'Subtema',
            3 => 'Contenido',
            4 => 'Imágenes',
        ];

        $metodos = Metodo::whereHas('errorReports', fn($q) => $q->where('solved', false))
            ->with([
                'errorReports' => fn($q) => $q->where('solved', false)->with('user'),
                'tema',
            ])
            ->orderBy('id')
            ->paginate(20);

        foreach ($metodos as $metodo) {
            $agregado = '00000';
            foreach ($metodo->errorReports as $er) {
                for ($i = 0; $i < 5; $i++) {
                    if (($er->tipo[$i] ?? '0') === '1') {
                        $agregado[$i] = '1';
                    }
                }
            }
            $metodo->tipoAgregado = $agregado;
        }

        return view('metodos.con-errores', compact('metodos', 'tipoLabels'));
    }
}
