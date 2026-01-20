<?php

namespace App\Http\Controllers;

use App\Models\Tag;
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
        // Obtener tags únicos de problemas_tags con conteo de apariciones
        $query = ProblemaTag::select('tag as title')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('tag');

        // Búsqueda por título
        if ($request->filled('search')) {
            $query->where('tag', 'LIKE', '%' . $request->search . '%');
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
            $query->orderBy('count', $sortOrder);
        } else {
            $query->orderBy('tag', $sortOrder);
        }

        // Paginación
        $tags = $query->paginate(30)->appends($request->query());

        // Total de tags únicos
        $totalTags = ProblemaTag::distinct('tag')->count('tag');

        // Verificar si el usuario es admin
        $isAdmin = Auth::user()->rol === 'admin';

        return view('tags.index', compact('tags', 'totalTags', 'isAdmin'));
    }

    /**
     * Actualizar un tag (solo admin)
     */
    public function update(Request $request, $title)
    {
        // Solo admin puede editar
        if (Auth::user()->rol !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $oldTitle = urldecode($title);
        $newTitle = $request->title;

        if ($oldTitle === $newTitle) {
            return response()->json(['success' => true, 'message' => 'Sin cambios']);
        }

        // Verificar que el tag original existe en problemas_tags
        $tagExists = ProblemaTag::where('tag', $oldTitle)->exists();
        if (!$tagExists) {
            return response()->json(['error' => 'Tag no encontrado'], 404);
        }

        // Verificar si el nuevo título ya existe en problemas_tags
        $newExists = ProblemaTag::where('tag', $newTitle)->exists();
        if ($newExists) {
            return response()->json(['error' => 'Ya existe un tag con ese nombre'], 422);
        }

        DB::transaction(function () use ($oldTitle, $newTitle) {
            // Actualizar en problemas_tags
            ProblemaTag::where('tag', $oldTitle)->update(['tag' => $newTitle]);

            // Actualizar en tags si existe
            Tag::where('title', $oldTitle)->update(['title' => $newTitle]);
        });

        return response()->json([
            'success' => true,
            'message' => "Tag actualizado de '{$oldTitle}' a '{$newTitle}'"
        ]);
    }

    /**
     * Eliminar un tag (solo admin)
     */
    public function destroy($title)
    {
        // Solo admin puede eliminar
        if (Auth::user()->rol !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $title = urldecode($title);

        // Verificar que el tag existe en problemas_tags
        $tagExists = ProblemaTag::where('tag', $title)->exists();
        if (!$tagExists) {
            return response()->json(['error' => 'Tag no encontrado'], 404);
        }

        DB::transaction(function () use ($title) {
            // Eliminar de problemas_tags
            ProblemaTag::where('tag', $title)->delete();

            // Eliminar de tags si existe
            Tag::where('title', $title)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Tag '{$title}' eliminado correctamente"
        ]);
    }
}
