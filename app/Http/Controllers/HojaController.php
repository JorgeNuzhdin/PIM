<?php

namespace App\Http\Controllers;

use App\Models\Hoja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HojaController extends Controller
{
    /**
     * Verificar que el usuario puede gestionar hojas
     */
    private function canManageHojas(): bool
    {
        return in_array(Auth::user()->rol, ['admin', 'editor', 'profesor']);
    }

    /**
     * Listar hojas con filtros
     */
    public function index(Request $request)
    {
        if (!$this->canManageHojas()) {
            abort(403, 'No tienes permiso para acceder a las hojas.');
        }

        $query = Hoja::with('user');

        // Si no es admin, solo ve sus propias hojas
        if (Auth::user()->rol !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        // Filtros
        if ($request->filled('nombre_hoja')) {
            $query->where('nombre_hoja', 'like', '%' . $request->nombre_hoja . '%');
        }

        if ($request->filled('nombre_grupo')) {
            $query->where('nombre_grupo', 'like', '%' . $request->nombre_grupo . '%');
        }

        if ($request->filled('tema')) {
            $query->where('tema', 'like', '%' . $request->tema . '%');
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // Solo admin puede filtrar por profesor
        if (Auth::user()->rol === 'admin' && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $hojas = $query->orderBy('created_at', 'desc')->paginate(20);

        // Lista de profesores para el filtro (solo para admin)
        $profesores = null;
        if (Auth::user()->rol === 'admin') {
            $profesores = User::whereIn('rol', ['admin', 'editor', 'profesor'])
                              ->orderBy('name')
                              ->get();
        }

        return view('hojas.index', compact('hojas', 'profesores'));
    }

    /**
     * Guardar el carrito actual como hoja
     */
    public function store(Request $request)
    {
        if (!$this->canManageHojas()) {
            return response()->json(['error' => 'No tienes permiso'], 403);
        }

        $request->validate([
            'nombre_hoja' => 'required|string|max:255',
            'nombre_grupo' => 'nullable|string|max:255',
            'tema' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:2000|max:2100',
            'problemas' => 'required|array',
            'problemas.*' => 'integer|exists:pim_problems,id',
        ]);

        $hoja = Hoja::create([
            'user_id' => Auth::id(),
            'nombre_hoja' => $request->nombre_hoja,
            'nombre_grupo' => $request->nombre_grupo,
            'tema' => $request->tema,
            'year' => $request->year,
        ]);

        // Guardar problemas con orden
        foreach ($request->problemas as $orden => $problemId) {
            $hoja->problems()->attach($problemId, ['orden' => $orden]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hoja guardada correctamente.',
            'hoja' => $hoja
        ]);
    }

    /**
     * Cargar una hoja al carrito
     */
    public function load(Hoja $hoja)
    {
        if (!$this->canManageHojas()) {
            abort(403);
        }

        // Si no es admin, solo puede cargar sus propias hojas
        if (Auth::user()->rol !== 'admin' && $hoja->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para cargar esta hoja.');
        }

        // Solo devolver los IDs para evitar problemas de encoding
        $problemIds = $hoja->problems()->pluck('pim_problems.id')->toArray();

        return response()->json([
            'success' => true,
            'hoja_id' => $hoja->id,
            'nombre_hoja' => $hoja->nombre_hoja,
            'problema_ids' => $problemIds
        ]);
    }

    /**
     * Eliminar una hoja
     */
    public function destroy(Hoja $hoja)
    {
        if (!$this->canManageHojas()) {
            abort(403);
        }

        // Si no es admin, solo puede eliminar sus propias hojas
        if (Auth::user()->rol !== 'admin' && $hoja->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para eliminar esta hoja.');
        }

        $hoja->delete();

        return back()->with('success', 'Hoja eliminada correctamente.');
    }
}