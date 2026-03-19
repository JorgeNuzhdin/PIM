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
                
                $approved = Auth::user()->isAutoApproved() ? 1 : 0;

                $problema = Problema::create([
                    'id' => $nextId,
                    'difficulty' => $validated['difficulty'] ?? null,
                    'tema_id' => $validated['tema_id'] ?? null,
                    'school_year' => $schoolYearText,
                    'title' => self::decodeUnicodeEscapes($validated['title'] ?? null),
                    'problem_tex' => self::decodeUnicodeEscapes($validated['problem_tex']),
                    'hints' => self::decodeUnicodeEscapes($validated['hints'] ?? null),
                    'solution_tex' => self::decodeUnicodeEscapes($validated['solution_tex'] ?? null),
                    'comments' => $validated['comments'] ?? null,
                    'source' => $validated['source'] ?? null,
                    'proponent_id' => Auth::id(),
                    'approved' => $approved,
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
                
                $msg = $approved
                    ? 'Problema creado exitosamente.'
                    : 'Tu problema ha sido enviado y está pendiente de aprobación. Solo tú y los administradores podéis verlo hasta que sea aprobado.';

                return redirect()->route('problemas.create')->with($approved ? 'success' : 'info', $msg);
                
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

                // Lista de proponentes para admin (todos los usuarios que han aportado al menos un problema)
                $proponents = [];
                if (Auth::user()->isAdmin()) {
                    $proponents = \App\Models\User::whereIn('id', function ($q) {
                        $q->select('proponent_id')->from('pim_problems')->whereNotNull('proponent_id');
                    })->orderBy('name')->get();
                }

                // URL de retorno (para volver a la misma página/filtros)
                $returnUrl = $request->get('return') ? urldecode($request->get('return')) : null;

                // Calcular tipo agregado de errores reportados pendientes (OR de todos los reportes sin resolver)
                $errorReports = \App\Models\ErrorReport::where('problema_id', $id)->where('solved', false)->get();
                $errorTipo = '000000000';
                foreach ($errorReports as $er) {
                    for ($i = 0; $i < 9; $i++) {
                        if (($er->tipo[$i] ?? '0') === '1') {
                            $errorTipo[$i] = '1';
                        }
                    }
                }
                $hasUnsolvedErrors = $errorReports->isNotEmpty();

                return view('problemas.edit', compact('problema', 'temas', 'schoolYears', 'figuras', 'proponents', 'returnUrl', 'errorTipo', 'hasUnsolvedErrors'));
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
                        'tema_id' => $validated['tema_id'] ?? null,
                        'school_year' => $schoolYearText,
                        'title' => self::decodeUnicodeEscapes($validated['title']),
                        'problem_tex' => self::decodeUnicodeEscapes($validated['problem_tex']),
                        'hints' => self::decodeUnicodeEscapes($validated['hints']),
                        'solution_tex' => self::decodeUnicodeEscapes($validated['solution_tex']),
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

                    // Marcar reportes de error como resueltos solo si el usuario lo solicitó
                    if ($request->boolean('mark_solved')) {
                        \App\Models\ErrorReport::where('problema_id', $id)->where('solved', false)->update(['solved' => true]);
                    }

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

    public function pendientes()
    {
        $problemas = Problema::where('approved', 0)
            ->with(['proponent', 'tags'])
            ->orderByDesc('id')
            ->paginate(20);
        return view('admin.problemas-pendientes', compact('problemas'));
    }

    public function aprobar($id)
    {
        Problema::findOrFail($id)->update(['approved' => 1]);
        return back()->with('success', "Problema #{$id} aprobado correctamente.");
    }

            public function destroy($id)
            {
                try {
                    $problema = Problema::findOrFail($id);

                    // Eliminar tags asociados
                    ProblemaTag::where('problem_id', $id)->delete();

                    // Eliminar el problema
                    $problema->delete();

                    if (request()->expectsJson()) {
                        return response()->json(['success' => true, 'message' => 'Problema eliminado exitosamente']);
                    }
                    return back()->with('success', "Problema #{$id} eliminado correctamente.");

                } catch (\Exception $e) {
                    if (request()->expectsJson()) {
                        return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
                    }
                    return back()->with('error', 'Error al eliminar: ' . $e->getMessage());
                }
            }

    public function show($id)
    {
        $problema = Problema::with(['tags', 'proponent'])->findOrFail($id);

        // Restricción para rol 'user'
        $allowedIds = AccessHelper::allowedProblemIds();
        if ($allowedIds !== null && !in_array($problema->id, $allowedIds)) {
            abort(403);
        }

        // Problemas no aprobados: solo autor y admins
        if (!$problema->approved
            && !Auth::user()->isAdmin()
            && $problema->proponent_id !== Auth::id()) {
            abort(403);
        }

        LatexHelper::resetCounters();
        $problema->problem_html_processed  = LatexHelper::toHtml($problema->problem_tex  ?? '');
        $problema->solution_html_processed = LatexHelper::toHtml($problema->solution_tex ?? '');

        $enCarrito = false;
        if (auth()->check()) {
            $enCarrito = \App\Models\Carrito::where('user_id', auth()->id())
                ->where('problema_id', $problema->id)
                ->exists();
        }

        return view('problemas.show', compact('problema', 'enCarrito'));
    }

    public function index(Request $request)
{
    $query = Problema::query();

    // Restricción para usuarios básicos (rol 'user'): solo problemas usados en hojas accesibles
    // Los problemas propios del usuario siempre son visibles (incluso pendientes de aprobación)
    $allowedProblemIds = AccessHelper::allowedProblemIds();
    if ($allowedProblemIds !== null) {
        $userId = Auth::id();
        if (empty($allowedProblemIds)) {
            $query->where('proponent_id', $userId);
        } else {
            $query->where(function ($q) use ($allowedProblemIds, $userId) {
                $q->whereIn('id', $allowedProblemIds)
                  ->orWhere('proponent_id', $userId);
            });
        }
    }

    // Filtro de visibilidad: los no aprobados solo los ve su autor y los admins
    if (!Auth::check() || !Auth::user()->isAdmin()) {
        $query->where(function ($q) {
            $q->where('approved', 1)
              ->orWhere('proponent_id', Auth::id());
        });
    }

    // Filtro por texto (busca en ID, título, problema, solución y fuente)
    if ($request->filled('buscar')) {
        $buscar = $request->buscar;
        $query->where(function($q) use ($buscar) {
            // Si es un número, buscar primero por ID exacto
            if (is_numeric($buscar)) {
                $q->where('id', $buscar);
            }
            // Buscar también en título, contenido y fuente
            $q->orWhere('title', 'LIKE', "%{$buscar}%")
              ->orWhere('problem_tex', 'LIKE', "%{$buscar}%")
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
    } elseif ($request->boolean('solo_mios') && Auth::check() && in_array(Auth::user()->rol, ['admin', 'editor'])) {
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

    // IDs de problemas con reportes de error (para mostrar el icono en amarillo)
    $problemasConErrores = \App\Models\ErrorReport::where('solved', false)->distinct()->pluck('problema_id')->flip()->all();

    return view('problemas.index', compact('problemas', 'temas', 'totalProblemas', 'problemasEncontrados', 'mostrar', 'schoolYears', 'sourceData', 'proponents', 'problemasUsados', 'problemasConErrores'));
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
        // Acepta ?tags[]=xxx&tags[]=yyy  o  ?tag=xxx (legacy)
        $tags = $request->get('tags', []);
        if (empty($tags)) {
            $single = $request->get('tag', '');
            if (!empty($single)) $tags = [$single];
        }
        if (empty($tags)) {
            return response()->json(['tema_id' => null, 'tema_nombre' => null, 'conflict' => false]);
        }

        // Reglas fijas: mb_strtolower(tag) → mb_strtolower(nombre_tema)
        $tagRules = [
            // Geometría
            'geometría'=>'geometría','geometría analítica'=>'geometría','geometría discreta'=>'geometría',
            'geometría. paridad.'=>'geometría','ángulo central'=>'geometría','ángulos'=>'geometría',
            'ángulos en una circunferencia'=>'geometría','ángulos exteriores'=>'geometría','ángulos inscritos'=>'geometría',
            'área del círculo'=>'geometría','área del triángulo'=>'geometría','áreas'=>'geometría',
            'árbol de steiner'=>'geometría','bisectriz'=>'geometría','círculo de los nueve puntos'=>'geometría',
            'círculos'=>'geometría','circuncentro'=>'geometría','circunferencia inscrita'=>'geometría',
            'circunferencias'=>'geometría','congruencias'=>'geometría','construcción'=>'geometría',
            'construcción complementaria'=>'geometría','construcción con regla y compás'=>'geometría',
            'construcciones'=>'geometría','coordenadas'=>'geometría','cuadrados'=>'geometría',
            'cuadrilátero cíclico'=>'geometría','cuadriláteros'=>'geometría','cubo'=>'geometría',
            'diagonales'=>'geometría','distancias'=>'geometría','división de figuras'=>'geometría',
            'esfera'=>'geometría','estereometría'=>'geometría','figuras convexas'=>'geometría',
            'fórmula de euler generalizada'=>'geometría','fórmula de herón'=>'geometría',
            'homotecia'=>'geometría','incentro'=>'geometría','inversión'=>'geometría','isometrías'=>'geometría',
            'línea media'=>'geometría','longitud'=>'geometría','lugar geométrico'=>'geometría',
            'medianas'=>'geometría','mediatriz'=>'geometría','ortocentro'=>'geometría','ortopolo'=>'geometría',
            'parábola'=>'geometría','paralelogramos'=>'geometría','pentágonos'=>'geometría','perímetro'=>'geometría',
            'pitágoras'=>'geometría','planimetría'=>'geometría','poliedros'=>'geometría','polígonos'=>'geometría',
            'potencia de punto'=>'geometría','producto escalar'=>'geometría','producto vectorial'=>'geometría',
            'proyección ortogonal'=>'geometría','puntos notables'=>'geometría','razón áurea'=>'geometría',
            'recta de euler'=>'geometría','recta de simpson'=>'geometría','rectas'=>'geometría',
            'semejanza'=>'geometría','semicírculo'=>'geometría','simetría'=>'geometría','tales'=>'geometría',
            'tangencia'=>'geometría','tangentes'=>'geometría','teorema de ceva'=>'geometría',
            'teorema de la bisectriz'=>'geometría','teorema de pitágoras'=>'geometría',
            'teorema de tales'=>'geometría','teorema del seno'=>'geometría','ternas pitagóricas'=>'geometría',
            'teselaciones'=>'geometría','tetraedro'=>'geometría','transformaciones'=>'geometría',
            'transformaciones del plano'=>'geometría','trapecios'=>'geometría','triangulaciones'=>'geometría',
            'triángulo inscrito'=>'geometría','triángulos'=>'geometría','triángulos equiláteros'=>'geometría',
            'triángulos isósceles'=>'geometría','triángulos semejantes'=>'geometría','triángulos similares'=>'geometría',
            'trigonometría'=>'geometría','vectores'=>'geometría','visión espacial'=>'geometría',
            // Aritmética
            'aritmética'=>'aritmética','aritmética modular'=>'aritmética','algoritmo de euclides'=>'aritmética',
            'algoritmo de la división'=>'aritmética','bases de numeración'=>'aritmética',
            'criterios de divisibilidad'=>'aritmética','descenso infinito'=>'aritmética',
            'descomposición en factores'=>'aritmética','dígitos'=>'aritmética','divisibilidad'=>'aritmética',
            'divisibilidad por 11'=>'aritmética','división'=>'aritmética','divisores'=>'aritmética',
            'factoriales'=>'aritmética','factorización'=>'aritmética','función de euler'=>'aritmética',
            'función sigma'=>'aritmética','gran teorema de fermat'=>'aritmética','identidad de bézout'=>'aritmética',
            'irracionalidad'=>'aritmética','máximo común divisor'=>'aritmética','máximo divisor común'=>'aritmética',
            'múltiplos'=>'aritmética','números enteros'=>'aritmética','números irracionales'=>'aritmética',
            'números naturales'=>'aritmética','números primos'=>'aritmética','parte entera'=>'aritmética',
            'pequeño teorema de fermat'=>'aritmética','potencias'=>'aritmética','potencias de 2'=>'aritmética',
            'primos'=>'aritmética','producto de euler'=>'aritmética','raíces cuadradas'=>'aritmética',
            'raíces de la unidad'=>'aritmética','repunits'=>'aritmética','restos'=>'aritmética',
            'sistema binario'=>'aritmética','sistema decimal'=>'aritmética','sistema posicional'=>'aritmética',
            'sistema ternario'=>'aritmética','sistemas de numeración'=>'aritmética','suma de las cifras'=>'aritmética',
            'teorema chino del resto'=>'aritmética','teorema de bezout'=>'aritmética','teorema de fermat'=>'aritmética',
            'teorema fundamental de la aritmética'=>'aritmética','teoría de números'=>'aritmética','última cifra'=>'aritmética',
            // Combinatoria
            'combinatoria'=>'combinatoria','combinatoria geométrica'=>'combinatoria','árboles'=>'combinatoria',
            'árboles generadores'=>'combinatoria','asignaciones'=>'combinatoria','binomio de newton'=>'combinatoria',
            'biyecciones'=>'combinatoria','cadenas de markov'=>'combinatoria','caminos'=>'combinatoria',
            'caminos de dyck'=>'combinatoria','cartas'=>'combinatoria','característica de euler'=>'combinatoria',
            'centro del grafo'=>'combinatoria','cerillas'=>'combinatoria','ciclo hamiltoniano'=>'combinatoria',
            'ciclos'=>'combinatoria','codificación'=>'combinatoria','códigos'=>'combinatoria',
            'coloración'=>'combinatoria','coloración por aristas'=>'combinatoria','coloraciones'=>'combinatoria',
            'combinaciones'=>'combinatoria','conectividad'=>'combinatoria','conexidad'=>'combinatoria',
            'conjuntos'=>'combinatoria','conteo doble'=>'combinatoria','cortes'=>'combinatoria',
            'cuadrículas'=>'combinatoria','dados'=>'combinatoria','demostración probabilística'=>'combinatoria',
            'distribución binomial'=>'combinatoria','distribución de poisson'=>'combinatoria','doble conteo'=>'combinatoria',
            'emparejamientos perfectos'=>'combinatoria','esperanza'=>'combinatoria','estadística'=>'combinatoria',
            'etiquetado'=>'combinatoria','exclusión'=>'combinatoria','funciones generatrices'=>'combinatoria',
            'grafo bipartito regular'=>'combinatoria','grafo de petersen'=>'combinatoria','grafo dual'=>'combinatoria',
            'grafos'=>'combinatoria','grafos bipartitos'=>'combinatoria','grafos completos'=>'combinatoria',
            'grafos dirigidos'=>'combinatoria','grafos eulerianos'=>'combinatoria','grafos hamiltonianos'=>'combinatoria',
            'grafos planares'=>'combinatoria','grafos regulares'=>'combinatoria','hamiltonicidad'=>'combinatoria',
            'handshaking lemma'=>'combinatoria','isomorfismo'=>'combinatoria','juegos'=>'combinatoria',
            'juegos de estrategia'=>'combinatoria','manhattan'=>'combinatoria','método probabilístico'=>'combinatoria',
            'nim'=>'combinatoria','nim-sumas'=>'combinatoria','número cromático'=>'combinatoria',
            'números combinatorios'=>'combinatoria','números de catalán'=>'combinatoria','números de fibonacci'=>'combinatoria',
            'números de lucas'=>'combinatoria','números de ramsey'=>'combinatoria','números de stirling'=>'combinatoria',
            'números triangulares'=>'combinatoria','palabras'=>'combinatoria','palomar'=>'combinatoria',
            'particiones'=>'combinatoria','paseo aleatorio'=>'combinatoria','permutaciones'=>'combinatoria',
            'plano de fano'=>'combinatoria','poliminos'=>'combinatoria',
            'principio de exclusión-inclusión'=>'combinatoria','principio de inclusión-exclusión'=>'combinatoria',
            'principio del palomar'=>'combinatoria','probabilidad'=>'combinatoria',
            'probabilidad condicional'=>'combinatoria','probabilidad geométrica'=>'combinatoria',
            'random walk'=>'combinatoria','recorrido euleriano'=>'combinatoria','recorrido hamiltoniano'=>'combinatoria',
            'recurrencias'=>'combinatoria','rompecabezas'=>'combinatoria','subconjuntos'=>'combinatoria',
            'sucesión de fibonacci'=>'combinatoria','tableros'=>'combinatoria','tablas'=>'combinatoria',
            'teorema de hall'=>'combinatoria','teorema de mantel'=>'combinatoria','teorema de turán'=>'combinatoria',
            'teoría de juegos'=>'combinatoria','torneos'=>'combinatoria','triángulo de pascal'=>'combinatoria',
            'varianza'=>'combinatoria','votaciones'=>'combinatoria',
            // Métodos
            'invariantes'=>'métodos','inducción'=>'métodos','semiinvariantes'=>'métodos',
            'reducción al absurdo'=>'métodos','reductio ad absurdum'=>'métodos',
            'construcción de ejemplos'=>'métodos','contraejemplos'=>'métodos',
            'método de pequeñas variaciones'=>'métodos','principio del extremo'=>'métodos',
            'principio de la palanca'=>'métodos','cambio de lenguaje'=>'métodos','cambio de variable'=>'métodos',
            'nuevas variables'=>'métodos','análisis de casos'=>'métodos','estudio de casos'=>'métodos',
            'estrategia'=>'métodos','estrategias'=>'métodos','razonamientos largos'=>'métodos',
            'pensamiento lateral'=>'métodos','iteraciones'=>'métodos','demostraciones'=>'métodos',
            'algoritmo voraz'=>'métodos',
        ];

        $temasRaw     = Tema::select('id', 'tema')->get();
        $temaIdByLc   = $temasRaw->mapWithKeys(fn($t) => [mb_strtolower($t->tema) => $t->id]);
        $temaNameById = $temasRaw->pluck('tema', 'id');

        $resolvedLc = collect($tags)
            ->map(fn($tag) => $tagRules[mb_strtolower($tag)] ?? null)
            ->filter()->unique()->values();

        $temaIds = $resolvedLc->map(fn($lc) => $temaIdByLc[$lc] ?? null)->filter()->unique()->values();

        if ($temaIds->count() === 1) {
            $id = $temaIds->first();
            return response()->json([
                'tema_id'     => $id,
                'tema_nombre' => $temaNameById[$id] ?? null,
                'conflict'    => false,
            ]);
        }

        if ($temaIds->count() > 1) {
            return response()->json([
                'tema_id'  => null,
                'conflict' => true,
                'options'  => $temaIds->map(fn($id) => [
                    'id'     => $id,
                    'nombre' => $temaNameById[$id] ?? '?',
                ])->values(),
            ]);
        }

        // Fallback: topic_tema (para tags no cubiertos por las reglas)
        foreach ($tags as $tag) {
            $tt = TopicTema::where('topic_title', $tag)->first();
            if ($tt) {
                $tema = Tema::find($tt->tema_id);
                return response()->json([
                    'tema_id'     => $tt->tema_id,
                    'tema_nombre' => $tema ? $tema->tema : null,
                    'conflict'    => false,
                ]);
            }
        }

        return response()->json(['tema_id' => null, 'tema_nombre' => null, 'conflict' => false]);
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

    public function checkDuplicates(Request $request)
    {
        $items = $request->input('items', []);
        $results = [];

        // Cargar 300 chars (100 post-normalización + margen para espacios/saltos iniciales)
        $allProblems = Problema::whereNotNull('problem_tex')
            ->selectRaw('id, title, SUBSTRING(problem_tex, 1, 300) AS problem_tex')
            ->get();

        foreach ($items as $index => $item) {
            $title = trim($item['title'] ?? '');
            $contentPrefix = $this->normalizePrefix($item['content_prefix'] ?? '');

            $titleMatch = null;
            $contentMatch = null;

            foreach ($allProblems as $p) {
                if ($contentMatch === null && $contentPrefix !== '' &&
                    levenshtein($this->normalizePrefix($p->problem_tex), $contentPrefix) <= 7) {
                    $contentMatch = ['id' => $p->id];
                }
                if ($titleMatch === null && $title !== '' &&
                    trim((string) $p->title) !== '' &&
                    mb_strtolower(trim((string) $p->title)) === mb_strtolower($title)) {
                    $titleMatch = ['id' => $p->id];
                }
                if ($titleMatch && $contentMatch) break;
            }

            $results[] = [
                'index' => $index,
                'title_match' => $titleMatch,
                'content_match' => $contentMatch,
            ];
        }

        return response()->json($results);
    }

    private function normalizePrefix(string $text, int $len = 100): string
    {
        return substr(trim(preg_replace('/\s+/', ' ', $text)), 0, $len);
    }

    /**
     * Decodifica secuencias uXXXX (p.ej. u005c → \) que pueden aparecer si el texto
     * pasó por JSON.stringify sin un JSON.parse correcto o por algún export externo.
     */
    private static function decodeUnicodeEscapes(?string $text): ?string
    {
        if ($text === null) return null;
        return preg_replace_callback('/u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $text);
    }

    public function previewPdf(Request $request, $id)
    {
        $problema = Problema::findOrFail($id);

        $problemTex  = $request->input('problem_tex',  $problema->problem_tex  ?? '');
        $solutionTex = $request->input('solution_tex', $problema->solution_tex ?? '');
        $packages    = $request->input('packages',     $problema->packages     ?? '');

        // Preamble (mismo conjunto de paquetes que el carrito)
        $preambulo = $this->buildPreviewPreamble($packages);

        $body  = "\\exercise{" . $problemTex . "}\n";
        if (trim($solutionTex) !== '') {
            $body .= "\n\\solution{" . $solutionTex . "}\n";
        }

        $texContent = $preambulo . "\n\n\\begin{document}\n\n" . $body . "\n\\end{document}";

        // Recopilar imágenes referenciadas en el tex
        $imageData = [];
        preg_match_all('/\\\\includegraphics(?:\[.*?\])?\{([^}]+)\}/', $problemTex . ' ' . $solutionTex, $matches);
        foreach ($matches[1] as $imgName) {
            $imgNameClean = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imgName);
            $figure = Figure::where('problem_id', $id)
                            ->where(function ($q) use ($imgName, $imgNameClean) {
                                $q->where('title', $imgName)->orWhere('title', $imgNameClean);
                            })->first();
            if ($figure && $figure->figure) {
                $imageData[$imgName] = $figure->figure;
            }
        }

        $compiler = new \App\Services\LatexCompilerService();
        $result   = $compiler->compile($texContent, $imageData);

        if (!$result['pdf']) {
            $compiler->cleanup($result['tempDir']);
            $summary = $result['errorSummary'] ?: 'Error desconocido de compilación';
            return response()->json(['error' => $summary], 422);
        }

        $pdfContent = file_get_contents($result['pdf']);
        $compiler->cleanup($result['tempDir']);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    private function buildPreviewPreamble(string $packages = ''): string
    {
        $preamble = <<<'LATEX'
\documentclass[12pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage{amssymb,mathtools,amsmath,amsthm}
\usepackage{graphicx,latexsym}
\usepackage{pgfplots}
\pgfplotsset{compat=1.15}
\usepackage{mathrsfs}
\usepackage{tikz}
\usetikzlibrary{arrows,arrows.meta,math,angles,quotes,positioning,patterns}
\usepackage{tikz-cd}
\usepackage{color,xcolor}
\usepackage{enumitem}
\usepackage{textcomp,gensymb}
\usepackage{multicol}
\usepackage{tcolorbox}
\usepackage{subcaption}
\usepackage{float,multirow,array}
\usepackage{hyperref}
\usepackage{ifthen}
\usepackage{twemojis}

\newif\ifshowsolutions
\showsolutionstrue

\setlength{\textwidth}{19cm}
\setlength{\textheight}{27cm}
\setlength{\topmargin}{-2cm}
\setlength{\oddsidemargin}{-1.5cm}
\pagestyle{empty}
\raggedbottom

\DeclareMathOperator{\mcd}{mcd}
\newcommand{\modd}[1]{\ (\mathrm{m\acute{o}d}\ #1)}
\newcommand{\ubrace}[2]{\underset{#1}{\underbrace{#2}}}

\newtheorem{ejer}{Problema}
\theoremstyle{definition}
\newtheorem*{definition}{Definición}
\newtheorem*{ejem}{Ejemplo resuelto}

\newcommand{\exercise}[1]{\begin{ejer}#1\end{ejer}}
\newcommand{\solution}[1]{\ifshowsolutions\begin{proof}[Solución]#1\end{proof}\fi}
\newcommand{\pistas}[1]{\textbf{Pistas:} #1}
LATEX;

        if ($packages) {
            $pkgsText = preg_replace_callback('/u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $packages);
            foreach (preg_split('/[\n,]+/', $pkgsText) as $pkg) {
                $pkg = trim($pkg);
                if ($pkg && str_starts_with($pkg, '\\') && strpos($preamble, $pkg) === false) {
                    $preamble .= "\n" . $pkg;
                }
            }
        }

        return $preamble;
    }
}