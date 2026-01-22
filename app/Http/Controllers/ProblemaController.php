<?php

namespace App\Http\Controllers;

use App\Models\Problema;
use App\Models\Tema;
use App\Models\Topic;
use App\Models\TopicTema;
use App\Models\ProblemaTag;
use Illuminate\Http\Request;
use App\Helpers\SchoolYearHelper;
use App\Helpers\SourceHelper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use App\Helpers\LatexHelper;




class ProblemaController extends Controller
{
    public function create()
        {
            $temas = Tema::all();
            $schoolYears = SchoolYearHelper::getAllYears();
            return view('problemas.create', compact('temas', 'schoolYears'));
        }

        public function store(Request $request)
        {
            $validated = $request->validate([
                'difficulty' => 'nullable|integer|min:1|max:10',
                'tema_id' => 'nullable|exists:temas,id',
                'school_year' => 'nullable|integer|min:1|max:12',
                'title' => 'nullable|string|max:255',
                'problem_tex' => 'required|string',
                'hints' => 'nullable|string',
                'solution_tex' => 'nullable|string',
                'comments' => 'nullable|string',
                'source' => 'nullable|string|max:255',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:100',
                'imagenes.*' => 'nullable|image|max:5120',
            ]);
            
            DB::beginTransaction();
            
            try {
                $nextId = Problema::max('id') + 1;
                
                $schoolYearText = null;
                if (isset($validated['school_year']) && $validated['school_year']) {
                    $schoolYearText = SchoolYearHelper::getYearName($validated['school_year']);
                }
                
                $problema = Problema::create([
                    'id' => $nextId,
                    'difficulty' => $validated['difficulty'] ?? null,
                    'school_year' => $schoolYearText,
                    'title' => $validated['title'] ?? null,
                    'problem_tex' => $validated['problem_tex'],
                    'problem_html' => $validated['problem_tex'],
                    'hints' => $validated['hints'] ?? null,
                    'solution_tex' => $validated['solution_tex'] ?? null,
                    'solution_html' => $validated['solution_tex'] ?? null,
                    'comments' => $validated['comments'] ?? null,
                    'source' => $validated['source'] ?? null,
                    'proponent_id' => Auth::id(),
                ]);
                
                // Guardar tags
                if ($request->has('tags') && is_array($request->tags)) {
                    foreach ($request->tags as $tag) {
                        if (!empty(trim($tag))) {
                            $tagTrimmed = trim($tag);
                            
                            // 1. Guardar en pim_problem_tags (relación problema-tag)
                            ProblemaTag::create([
                                'problem_id' => $problema->id,
                                'tag' => $tagTrimmed,
                            ]);
                            
                            // 2. Si hay tema seleccionado, gestionar topic_tema
                            if ($request->tema_id) {
                                // Verificar si el tag existe en tags
                                $topicExists = DB::table('tags')
                                    ->where('title', $tagTrimmed)
                                    ->exists();
                                
                                // Si no existe en tags, crearlo primero
                                if (!$topicExists) {
                                    DB::table('tags')->insert([
                                        'title' => $tagTrimmed,
                                        // Agrega otros campos requeridos si los hay
                                    ]);
                                }
                                
                                // Verificar si ya existe la relación en topic_tema
                                $relacionExists = TopicTema::where('tema_id', $request->tema_id)
                                    ->where('topic_title', $tagTrimmed)
                                    ->exists();
                                
                                // Si no existe la relación, crearla
                                if (!$relacionExists) {
                                    TopicTema::create([
                                        'tema_id' => $request->tema_id,
                                        'topic_title' => $tagTrimmed,
                                    ]);
                                }
                            }
                        }
                    }
                }
                
                if ($request->hasFile('imagenes')) {
                    foreach ($request->file('imagenes') as $imagen) {
                        $contenido = file_get_contents($imagen->getRealPath());
                        
                        Figure::create([
                            'title' => $imagen->getClientOriginalName(),
                            'figure' => $contenido,
                        ]);
                    }
                }
                
                DB::commit();
                
                // Si es una petición AJAX, devolver JSON
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'problema_id' => $problema->id,
                        'message' => 'Problema creado exitosamente'
                    ]);
                }
                
                return redirect()->route('problemas.create')->with('success', 'Problema creado exitosamente');
                
            } catch (\Exception $e) {
                DB::rollBack();
                
                \Log::error('Error al crear problema: ' . $e->getMessage(), [
                    'validated' => $validated,
                    'trace' => $e->getTraceAsString()
                ]);
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al crear el problema: ' . $e->getMessage()
                    ], 500);
                }
                
                return back()->withInput()->with('error', 'Error al crear el problema: ' . $e->getMessage());
            }
        }

            public function edit($id)
            {
                $problema = Problema::with('tags')->findOrFail($id);
                $temas = Tema::all();
                $schoolYears = SchoolYearHelper::getAllYears();
                
                return view('problemas.edit', compact('problema', 'temas', 'schoolYears'));
            }

            public function update(Request $request, $id)
            {
                $problema = Problema::findOrFail($id);
                
                $validated = $request->validate([
                    'difficulty' => 'nullable|integer|min:1|max:10',
                    'tema_id' => 'nullable|exists:temas,id',
                    'school_year' => 'nullable|integer|min:1|max:12',
                    'title' => 'nullable|string|max:255',
                    'problem_tex' => 'required|string',
                    'hints' => 'nullable|string',
                    'solution_tex' => 'nullable|string',
                    'comments' => 'nullable|string',
                    'source' => 'nullable|string|max:255',
                    'tags' => 'nullable|array',
                    'tags.*' => 'string|max:100',
                    'imagenes.*' => 'nullable|image|max:5120',
                ]);
                
                DB::beginTransaction();
                
                try {
                    // Convertir el índice numérico a texto del año
                    $schoolYearText = null;
                    if ($validated['school_year']) {
                        $schoolYearText = SchoolYearHelper::getYearName($validated['school_year']);
                    }
                    // Actualizar problema
                    $problema->update([
                        'difficulty' => $validated['difficulty'],
                        'school_year' => $schoolYearText,
                        'title' => $validated['title'],
                        'problem_tex' => $validated['problem_tex'],
                        'problem_html' => $validated['problem_tex'],
                        'hints' => $validated['hints'],
                        'solution_tex' => $validated['solution_tex'],
                        'solution_html' => $validated['solution_tex'],
                        'comments' => $validated['comments'],
                        'source' => $validated['source'],
                    ]);
                    
                    // Eliminar tags antiguos y crear nuevos
                    ProblemaTag::where('problem_id', $problema->id)->delete();
                    
                    // Guardar tags
                    if ($request->has('tags') && is_array($request->tags)) {
                        foreach ($request->tags as $tag) {
                            if (!empty(trim($tag))) {
                                $tagTrimmed = trim($tag);
                                
                                // 1. Guardar en pim_problem_tags (relación problema-tag)
                                ProblemaTag::create([
                                    'problem_id' => $problema->id,
                                    'tag' => $tagTrimmed,
                                ]);
                                
                                // 2. Si hay tema seleccionado, gestionar topic_tema
                                if ($request->tema_id) {
                                    // Verificar si el tag existe en tags
                                    $topicExists = DB::table('tags')
                                        ->where('title', $tagTrimmed)
                                        ->exists();
                                    
                                    // Si no existe en tags, crearlo primero
                                    if (!$topicExists) {
                                        DB::table('tags')->insert([
                                            'title' => $tagTrimmed,
                                            // Agrega otros campos requeridos si los hay
                                        ]);
                                    }
                                    
                                    // Verificar si ya existe la relación en topic_tema
                                    $relacionExists = TopicTema::where('tema_id', $request->tema_id)
                                        ->where('topic_title', $tagTrimmed)
                                        ->exists();
                                    
                                    // Si no existe la relación, crearla
                                    if (!$relacionExists) {
                                        TopicTema::create([
                                            'tema_id' => $request->tema_id,
                                            'topic_title' => $tagTrimmed,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Guardar nuevas imágenes si hay
                    if ($request->hasFile('imagenes')) {
                        foreach ($request->file('imagenes') as $imagen) {
                            $contenido = file_get_contents($imagen->getRealPath());
                            
                            Figure::create([
                                'title' => $imagen->getClientOriginalName(),
                                'figure' => $contenido,
                            ]);
                        }
                    }
                    
                    DB::commit();
                    
                    return redirect()->route('problemas.index')->with('success', 'Problema actualizado exitosamente');
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Error al actualizar el problema: ' . $e->getMessage());
                }
            }

            public function destroy($id)
            {
                try {
                    $problema = Problema::findOrFail($id);
                    
                    // Eliminar tags asociados
                    ProblemaTag::where('problem_id', $id)->delete();
                    
                    // Eliminar el problema
                    $problema->delete();
                    
                    return response()->json(['success' => true, 'message' => 'Problema eliminado exitosamente']);
                    
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
                }
            }



    public function index(Request $request)
{
    $query = Problema::query();
    
    // Filtro por texto
    if ($request->filled('buscar')) {
        $buscar = $request->buscar;
        $query->where(function($q) use ($buscar) {
            $q->where('problem_html', 'LIKE', "%{$buscar}%")
              ->orWhere('solution_html', 'LIKE', "%{$buscar}%");
        });
    }
    
    // Filtro por tema
    if ($request->filled('tema_id')) {
        $topicTitles = TopicTema::where('tema_id', $request->tema_id)
                                ->pluck('topic_title')
                                ->toArray();
        
        $problemIds = ProblemaTag::whereIn('tag', $topicTitles)
                                ->distinct()
                                ->pluck('problem_id')
                                ->toArray();
        
        if (!empty($problemIds)) {
            $query->whereIn('id', $problemIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }
    
    // Filtro por topic específico (tag)
    if ($request->filled('topic_title')) {
        $problemIds = ProblemaTag::where('tag', $request->topic_title)
                                ->distinct()
                                ->pluck('problem_id')
                                ->toArray();
        
        if (!empty($problemIds)) {
            $query->whereIn('id', $problemIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }
    
    // Filtro por dificultad (rango)
    if ($request->filled('difficulty_min')) {
        $query->where('difficulty', '>=', $request->difficulty_min);
    }
    if ($request->filled('difficulty_max')) {
        $query->where('difficulty', '<=', $request->difficulty_max);
    }
    
    // Filtro por año académico (hasta el año seleccionado)
    if ($request->filled('school_year')) {
        $query->where('school_year', '<=', $request->school_year);
    }

    // Filtro por fuente (source) - usando SourceHelper para grupos y comas
    if ($request->filled('source')) {
        SourceHelper::applySourceFilterWithCommas($query, $request->source);
    }

    // Filtro por proponente (proponent_id)
    if ($request->filled('proponent_id')) {
        $query->where('proponent_id', $request->proponent_id);
    }

    // Contar problemas filtrados ANTES de paginar
    $problemasEncontrados = $query->count();
    $totalProblemas = Problema::count();

    // Paginar resultados
    $problemas = $query->with(['tags', 'proponent'])->paginate(20)->appends($request->query());
    $temas = Tema::all();
    $schoolYears = SchoolYearHelper::getAllYears();

    // Obtener fuentes agrupadas para el filtro
    $sourceData = SourceHelper::getGroupedSources();

    // Obtener lista de proponentes para el filtro
    $proponents = \App\Models\User::whereIn('id', function($q) {
                        $q->select('proponent_id')
                          ->from('pim_problems')
                          ->whereNotNull('proponent_id')
                          ->distinct();
                    })
                    ->orderBy('name')
                    ->get(['id', 'name']);

    // Opciones de visualización
    $mostrar = $request->get('mostrar', ['fuente', 'pistas', 'solucion', 'comentarios', 'year']);

    return view('problemas.index', compact('problemas', 'temas', 'totalProblemas', 'problemasEncontrados', 'mostrar', 'schoolYears', 'sourceData', 'proponents'));
}
    
    // API para autocompletar topics
    public function buscarTopics(Request $request)
    {
        $query = $request->get('q', '');

        // Si q está vacío, devolver todos los tags (para cache Levenshtein)
        if (empty($query)) {
            $topics = ProblemaTag::distinct()
                                ->orderBy('tag')
                                ->pluck('tag')
                                ->toArray();
            return response()->json($topics);
        }

        // Para búsquedas, requerir mínimo 2 caracteres
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $topics = ProblemaTag::where('tag', 'LIKE', "%{$query}%")
                            ->distinct()
                            ->pluck('tag')
                            ->take(10)
                            ->toArray();

        return response()->json($topics);
    }
    public function temaDesdeTag(Request $request)
        {
            $tag = $request->get('tag', '');
            
            if (empty($tag)) {
                return response()->json(['tema_id' => null, 'tema_nombre' => null]);
            }
            
            // Buscar el tag en topic_tema
            $topicTema = TopicTema::where('topic_title', $tag)->first();
            
            if ($topicTema) {
                $tema = Tema::find($topicTema->tema_id);
                return response()->json([
                    'tema_id' => $topicTema->tema_id,
                    'tema_nombre' => $tema ? $tema->tema : null
                ]);
            }
            
            return response()->json(['tema_id' => null, 'tema_nombre' => null]);
        }



        public function latexPreview(Request $request)
            {
                try {
                    $latex = $request->input('latex', '');
                    
                    if (empty($latex)) {
                        return response()->json(['html' => '']);
                    }
                    
                    // Procesar LaTeX a HTML
                    $html = LatexHelper::toHtml($latex);
                    
                    return response()->json(['html' => $html]);
                    
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 500);
                }
            }
}