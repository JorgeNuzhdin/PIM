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
        $this->middleware('can.edit.problemas')->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        $temas = Tema::all();

        $query = Metodo::with(['tema', 'subtema']);

        if ($request->filled('tema_id')) {
            $query->where('tema_id', $request->tema_id);
        }

        if ($request->filled('subtema_id')) {
            $query->where('subtema_id', $request->subtema_id);
        }

        $metodos = $query->orderBy('id', 'desc')->get();

        $subtemas = $request->filled('tema_id')
            ? Subtema::where('tema_id', $request->tema_id)->orderBy('id')->get()
            : collect();

        return view('metodos.index', compact('metodos', 'temas', 'subtemas'));
    }

    public function show($id)
    {
        $metodo = Metodo::with(['tema', 'subtema'])->findOrFail($id);

        return view('metodos.show', compact('metodo'));
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
