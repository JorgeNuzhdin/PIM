<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\ProblemaTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can.edit.problemas');
    }

    /**
     * Mostrar listado de tags con filtros, ordenamiento y paginación
     */
    public function index(Request $request)
    {
        // Obtener tags con conteo de apariciones
        $query = Topic::select('pim_topics.id', 'pim_topics.title')
            ->selectRaw('(SELECT COUNT(*) FROM problemas_tags WHERE problemas_tags.tag = pim_topics.title) as count')
            ->groupBy('pim_topics.id', 'pim_topics.title');

        // Búsqueda por título
        if ($request->filled('search')) {
            $query->where('pim_topics.title', 'LIKE', '%' . $request->search . '%');
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'title');
        $sortOrder = $request->get('sort_order', 'asc');

        // Validar campos de ordenamiento permitidos
        $allowedSortFields = ['title', 'count'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'title';
        }

        if ($sortBy === 'count') {
            $query->orderByRaw("(SELECT COUNT(*) FROM problemas_tags WHERE problemas_tags.tag = pim_topics.title) {$sortOrder}");
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginación
        $tags = $query->paginate(30)->appends($request->query());

        // Total de tags
        $totalTags = Topic::count();

        // Verificar si el usuario es admin
        $isAdmin = Auth::user()->rol === 'admin';

        return view('tags.index', compact('tags', 'totalTags', 'isAdmin'));
    }

    /**
     * Actualizar un tag (solo admin)
     */
    public function update(Request $request, $id)
    {
        // Solo admin puede editar
        if (Auth::user()->rol !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $tag = Topic::findOrFail($id);
        $oldTitle = $tag->title;
        $newTitle = $request->title;

        if ($oldTitle === $newTitle) {
            return response()->json(['success' => true, 'message' => 'Sin cambios']);
        }

        // Verificar si el nuevo título ya existe
        $exists = Topic::where('title', $newTitle)->where('id', '!=', $id)->exists();
        if ($exists) {
            return response()->json(['error' => 'Ya existe un tag con ese nombre'], 422);
        }

        DB::transaction(function () use ($tag, $oldTitle, $newTitle) {
            // Actualizar en pim_topics
            $tag->title = $newTitle;
            $tag->save();

            // Actualizar en problemas_tags
            ProblemaTag::where('tag', $oldTitle)->update(['tag' => $newTitle]);
        });

        return response()->json([
            'success' => true,
            'message' => "Tag actualizado de '{$oldTitle}' a '{$newTitle}'"
        ]);
    }

    /**
     * Eliminar un tag (solo admin)
     */
    public function destroy($id)
    {
        // Solo admin puede eliminar
        if (Auth::user()->rol !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $tag = Topic::findOrFail($id);
        $title = $tag->title;

        DB::transaction(function () use ($tag, $title) {
            // Eliminar de problemas_tags
            ProblemaTag::where('tag', $title)->delete();

            // Eliminar de pim_topics
            $tag->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Tag '{$title}' eliminado correctamente"
        ]);
    }
}
