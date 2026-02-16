<?php

namespace App\Http\Controllers;

use App\Models\Metodo;
use App\Models\Tema;
use App\Models\Subtema;
use Illuminate\Http\Request;

class MetodoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can.edit.problemas');
    }

    public function index()
    {
        $metodos = Metodo::with(['tema', 'subtema'])->get();
        $temas = Tema::all();

        return view('metodos.index', compact('metodos', 'temas'));
    }

    public function create()
    {
        $temas = Tema::all();

        return view('metodos.create', compact('temas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'method_tex' => 'required|string',
            'tema_id' => 'required|exists:temas,id',
            'subtema_id' => 'required|exists:subtemas,id',
        ]);

        Metodo::create([
            'title' => $request->title,
            'method_tex' => $request->method_tex,
            'method_html' => $request->method_tex,
            'subtema_id' => $request->subtema_id,
            'tema_id' => $request->tema_id,
        ]);

        return redirect()->route('metodos.index')->with('success', 'Método creado correctamente.');
    }

    public function apiSubtemas($temaId)
    {
        $subtemas = Subtema::where('tema_id', $temaId)
                           ->orderBy('id')
                           ->get(['id', 'nombre']);

        return response()->json($subtemas);
    }
}
