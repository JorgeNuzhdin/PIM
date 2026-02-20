<?php

namespace App\Http\Controllers;

use App\Models\Problema;
use App\Models\Tema;
use App\Models\Topic;
use App\Models\TopicTema;
use App\Models\ProblemaTag;
use App\Models\Figure;
use Illuminate\Http\Request;
use App\Helpers\SchoolYearHelper;
use App\Helpers\SourceHelper;
use App\Helpers\SheetHelper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use App\Helpers\LatexHelper;
use App\Helpers\TagHelper;
use App\Helpers\AccessHelper;




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
                    'hints' => $validated['hints'] ?? null,
                    'solution_tex' => $validated['solution_tex'] ?? null,
                    'comments' => $validated['comments'] ?? null,
                    'source' => $validated['source'] ?? null,
                    'proponent_id' => Auth::id(),
                ]);
                
                // Guardar tags (normalizados con Levenshtein)
                if ($request->has('tags') && is_array($request->tags)) {
                    $normalizedTags = TagHelper::normalizeArray($request->tags);
                    foreach ($normalizedTags as $tagTrimmed) {
                        if (!empty($tagTrimmed)) {
                            
                            // 1. Guardar en problemas_tags (relación problema-tag)
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
                            'problem_id' => $problema->id,
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

            public function edit(Request $request, $id)
            {
                $problema = Problema::with('tags')->findOrFail($id);
                $temas = Tema::all();
                $schoolYears = SchoolYearHelper::getAllYears();
                $figuras = Figure::where('problem_id', $id)->get();

                // Lista de proponentes para admin (solo admins y editores pueden proponer)
                $proponents = [];
                if (Auth::user()->isAdmin()) {
                    $proponents = \App\Models\User::whereIn('rol', ['admin', 'editor'])->orderBy('name')->get();
                }

                // URL de retorno (para volver a la misma página/filtros)
                $returnUrl = $request->get('return') ? urldecode($request->get('return')) : null;

                return view('problemas.edit', compact('problema', 'temas', 'schoolYears', 'figuras', 'proponents', 'returnUrl'));
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
                    // Datos a actualizar
                    $updateData = [
                        'difficulty' => $validated['difficulty'],
                        'school_year' => $schoolYearText,
                        'title' => $validated['title'],
                        'problem_tex' => $validated['problem_tex'],
                        'hints' => $validated['hints'],
                        'solution_tex' => $validated['solution_tex'],
                        'comments' => $validated['comments'],
                        'source' => $validated['source'],
                    ];

                    // Solo admin puede cambiar el proponente
                    if (Auth::user()->isAdmin() && $request->has('proponent_id')) {
                        $updateData['proponent_id'] = $request->proponent_id ?: null;
                    }

                    // Actualizar problema
                    $problema->update($updateData);
                    
                    // Eliminar tags antiguos y crear nuevos
                    ProblemaTag::where('problem_id', $problema->id)->delete();
                    
                    // Guardar tags (normalizados con Levenshtein)
                    if ($request->has('tags') && is_array($request->tags)) {
                        $normalizedTags = TagHelper::normalizeArray($request->tags);
                        foreach ($normalizedTags as $tagTrimmed) {
                            if (!empty($tagTrimmed)) {
                                // 1. Guardar en problemas_tags (relación problema-tag)
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
                    
                    // Guardar/actualizar imágenes (si existe una con el mismo nombre, se sustituye)
                    if ($request->hasFile('imagenes')) {
                        foreach ($request->file('imagenes') as $imagen) {
                            $contenido = file_get_contents($imagen->getRealPath());

                            Figure::updateOrCreate(
                                [
                                    'problem_id' => $problema->id,
                                    'title' => $imagen->getClientOriginalName(),
                                ],
                                [
                                    'figure' => $contenido,
                                ]
                            );
                        }
                    }

                    DB::commit();

                    // Redirigir a la URL de retorno si existe, si no al índice
                    $returnUrl = $request->input('return_url');
                    if ($returnUrl) {
                        return redirect($returnUrl)->with('success', 'Problema actualizado exitosamente');
                    }
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

    // Restricción para usuarios básicos (rol 'user'): solo problemas usados en hojas accesibles
    $allowedProblemIds = AccessHelper::allowedProblemIds();
    if ($allowedProblemIds !== null) {
        if (empty($allowedProblemIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('id', $allowedProblemIds);
        }
    }

    // Filtro por texto (busca en ID, problema, solución y fuente)
    if ($request->filled('buscar')) {
        $buscar = $request->buscar;
        $query->where(function($q) use ($buscar) {
            // Si es un número, buscar primero por ID exacto
            if (is_numeric($buscar)) {
                $q->where('id', $buscar);
            }
            // Buscar también en contenido y fuente
            $q->orWhere('problem_tex', 'LIKE', "%{$buscar}%")
              ->orWhere('solution_tex', 'LIKE', "%{$buscar}%")
              ->orWhere('source', 'LIKE', "%{$buscar}%");
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
    $tagFilter = $request->filled('topic_title') ? $request->topic_title : ($request->filled('topic_display') ? $request->topic_display : null);
    if ($tagFilter) {
        // Intentar match exacto primero, luego LIKE
        $problemIds = ProblemaTag::where('tag', $tagFilter)
                                ->distinct()
                                ->pluck('problem_id')
                                ->toArray();

        if (empty($problemIds)) {
            // Fallback: búsqueda parcial
            $problemIds = ProblemaTag::where('tag', 'LIKE', "%{$tagFilter}%")
                                    ->distinct()
                                    ->pluck('problem_id')
                                    ->toArray();
        }

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
    
    // Filtro por año académico (rango Desde - Hasta)
    if ($request->filled('school_year_min') || $request->filled('school_year_max')) {
        $allYears = SchoolYearHelper::getAllYears();
        $min = $request->input('school_year_min', 1);
        $max = $request->input('school_year_max', 12);
        $validYears = array_filter($allYears, fn($k) => $k >= $min && $k <= $max, ARRAY_FILTER_USE_KEY);
        if (!empty($validYears)) {
            $query->whereIn('school_year', array_values($validYears));
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    // Filtro por fuente (source) - usando SourceHelper para grupos y comas
    if ($request->filled('source')) {
        SourceHelper::applySourceFilterWithCommas($query, $request->source);
    }

    // Filtro por proponente
    if ($request->filled('proponent_id')) {
        $query->where('proponent_id', $request->proponent_id);
    } elseif ($request->boolean('solo_mios') && in_array(Auth::user()->rol, ['admin', 'editor'])) {
        $query->where('proponent_id', Auth::id());
    }

    // Contar problemas filtrados ANTES de paginar
    $problemasEncontrados = $query->count();
    $totalProblemas = Problema::count();

    // Ordenar: si se busca por número, poner el ID exacto primero
    if ($request->filled('buscar') && is_numeric($request->buscar)) {
        $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$request->buscar]);
    }

    // Ordenar por dificultad si se solicita
    if ($request->filled('sort_difficulty')) {
        $sortDirection = $request->sort_difficulty === 'desc' ? 'desc' : 'asc';
        $query->orderBy('difficulty', $sortDirection);
    }

    // Ordenar por año académico si se solicita
    if ($request->filled('sort_year')) {
        $sortDirection = $request->sort_year === 'desc' ? 'desc' : 'asc';
        // Ordenar usando FIELD() para respetar el orden lógico de los años
        $allYears = SchoolYearHelper::getAllYears();
        $yearList = implode(',', array_map(fn($y) => "'" . addslashes($y) . "'", array_values($allYears)));
        $query->orderByRaw("FIELD(school_year, {$yearList}) {$sortDirection}");
    }

    // Ordenar por ID si se solicita
    if ($request->filled('sort_id')) {
        $sortDirection = $request->sort_id === 'asc' ? 'asc' : 'desc';
        $query->orderBy('id', $sortDirection);
    }

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

    // Obtener problemas usados en hojas con sus años
    $problemasUsados = SheetHelper::getProblemasUsadosConAnio();

    return view('problemas.index', compact('problemas', 'temas', 'totalProblemas', 'problemasEncontrados', 'mostrar', 'schoolYears', 'sourceData', 'proponents', 'problemasUsados'));
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