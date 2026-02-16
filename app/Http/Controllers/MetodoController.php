<?php

namespace App\Http\Controllers;

use App\Models\Metodo;
use App\Models\Tema;
use App\Models\Subtema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $query = Metodo::with(['tema', 'user']);

        if ($request->filled('tema_id')) {
            $query->where('tema_id', $request->tema_id);
        }

        if ($request->filled('subtema_id')) {
            $query->whereRaw('FIND_IN_SET(?, subtema_ids)', [$request->subtema_id]);
        }

        $metodos = $query->orderBy('id', 'desc')->get();

        $subtemas = $request->filled('tema_id')
            ? Subtema::where('tema_id', $request->tema_id)->orderBy('id')->get()
            : collect();

        return view('metodos.index', compact('metodos', 'temas', 'subtemas'));
    }

    public function show($id)
    {
        $metodo = Metodo::with(['tema', 'user'])->findOrFail($id);

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
            'subtema_ids' => 'required|array|min:1',
            'subtema_ids.*' => 'exists:subtemas,id',
        ]);

        Metodo::create([
            'title' => $request->title,
            'method_tex' => $request->method_tex,
            'method_html' => $request->method_tex,
            'subtema_ids' => implode(',', $request->subtema_ids),
            'tema_id' => $request->tema_id,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('metodos.index')->with('success', 'Método creado correctamente.');
    }

    public function edit($id)
    {
        $metodo = Metodo::findOrFail($id);

        // Solo el proponente (editor) o un admin pueden editar
        $user = Auth::user();
        if (!($user->isAdmin() || ($user->canEditProblemas() && $metodo->user_id === $user->id))) {
            abort(403);
        }

        $temas = Tema::all();
        $subtemas = Subtema::where('tema_id', $metodo->tema_id)->orderBy('id')->get();

        return view('metodos.edit', compact('metodo', 'temas', 'subtemas'));
    }

    public function update(Request $request, $id)
    {
        $metodo = Metodo::findOrFail($id);

        $user = Auth::user();
        if (!($user->isAdmin() || ($user->canEditProblemas() && $metodo->user_id === $user->id))) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'method_tex' => 'required|string',
            'tema_id' => 'required|exists:temas,id',
            'subtema_ids' => 'required|array|min:1',
            'subtema_ids.*' => 'exists:subtemas,id',
        ]);

        $metodo->update([
            'title' => $request->title,
            'method_tex' => $request->method_tex,
            'method_html' => $request->method_tex,
            'subtema_ids' => implode(',', $request->subtema_ids),
            'tema_id' => $request->tema_id,
        ]);

        return redirect()->route('metodos.show', $metodo->id)->with('success', 'Método actualizado correctamente.');
    }

    public function apiSubtemas($temaId)
    {
        $subtemas = Subtema::where('tema_id', $temaId)
                           ->orderBy('id')
                           ->get(['id', 'nombre']);

        return response()->json($subtemas);
    }

    public function apiStoreSubtema(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'tema_id' => 'required|exists:temas,id',
        ]);

        $subtema = Subtema::firstOrCreate([
            'nombre' => $request->nombre,
            'tema_id' => $request->tema_id,
        ]);

        return response()->json(['id' => $subtema->id, 'nombre' => $subtema->nombre]);
    }
}
