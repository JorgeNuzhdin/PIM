<?php

namespace App\Http\Controllers;

use App\Models\PimSheet;
use App\Models\Problema;
use App\Models\Tema;
use App\Models\FigureInIntro;
use App\Models\Figure;
use App\Models\Metodo;
use App\Models\Subtema;
use App\Helpers\LatexHelper;
use App\Helpers\AccessHelper;
use App\Services\LatexCompilerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PimSheetController extends Controller
{
    /**
     * Mostrar listado de sheets con filtros, ordenamiento y búsqueda
     */
    public function index(Request $request)
    {
        $query = PimSheet::with('tema');

        // Restricción para usuarios básicos (rol 'user')
        $allowedYears = AccessHelper::allowedYears();
        if ($allowedYears !== null) {
            $query->whereIn('date_year', $allowedYears);
        }

        // Búsqueda por título
        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        // Filtro por año
        if ($request->filled('year')) {
            $query->where('date_year', $request->year);
        }

        // Filtro por grupo (planet)
        if ($request->filled('planet')) {
            $query->where('planet', $request->planet);
        }

        // Filtro por institución
        if ($request->filled('institution')) {
            $query->where('institution', 'LIKE', '%' . $request->institution . '%');
        }

        // Filtro por tema
        if ($request->filled('theme')) {
            $query->where('theme', $request->theme);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'date_year');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validar campos de ordenamiento permitidos
        $allowedSortFields = ['date_year', 'planet', 'title', 'institution'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date_year';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Ordenar por ID si se solicita
        if ($request->filled('sort_id')) {
            $sortDirection = $request->sort_id === 'asc' ? 'asc' : 'desc';
            $query->orderBy('id', $sortDirection);
        }

        // Paginación
        $sheets = $query->paginate(20)->appends($request->query());

        // Obtener listas para filtros
        $years = PimSheet::distinct()->orderBy('date_year', 'desc')->pluck('date_year');
        $planets = PimSheet::distinct()->orderBy('planet')->pluck('planet');
        $institutions = PimSheet::distinct()->orderBy('institution')->pluck('institution');
        $temas = Tema::orderBy('tema')->get();

        return view('pim_sheets.index', compact('sheets', 'years', 'planets', 'institutions', 'temas'));
    }

    /**
     * Mostrar formulario para subir nueva sheet
     */
    public function create()
    {
        // Solo editores y administradores pueden subir sheets
        if (!Auth::user()->canEditProblemas()) {
            abort(403, 'No tienes permiso para subir hojas de problemas.');
        }

        $temas = Tema::orderBy('tema')->get();
        $currentYear = date('Y');

        return view('pim_sheets.create', compact('temas', 'currentYear'));
    }

    /**
     * Guardar nueva sheet
     */
    public function store(Request $request)
    {
        Log::info('=== PimSheet Store ===');
        Log::info('Request data: ' . json_encode($request->except(['tex_sols', 'imagenes_preambulo'])));
        Log::info('Has tex_sols: ' . ($request->hasFile('tex_sols') ? 'yes' : 'no'));
        Log::info('Has imagenes_preambulo: ' . ($request->hasFile('imagenes_preambulo') ? 'yes' : 'no'));

        // Solo editores y administradores pueden subir sheets
        if (!Auth::user()->canEditProblemas()) {
            abort(403, 'No tienes permiso para subir hojas de problemas.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'date_year' => 'required|integer|min:1900|max:2100',
            'planet' => 'nullable|string|max:255',
            'institution' => 'required|string|max:256',
            'theme' => 'required|exists:temas,id',
            'problems' => 'nullable|string|max:2048',
            'preambles' => 'nullable|string',
            'tex_sols' => 'required|file|mimes:tex,txt|max:10240',
            'imagenes_preambulo.*' => 'nullable|file|max:5120',
            'metodos_json' => 'nullable|string',
        ], [
            'title.required' => 'El título es obligatorio.',
            'date_year.required' => 'El año es obligatorio.',
            'institution.required' => 'La institución es obligatoria.',
            'theme.required' => 'El tema es obligatorio.',
            'tex_sols.required' => 'El archivo TEX es obligatorio.',
        ]);

        DB::beginTransaction();

        try {
            $data = [
                'title' => $request->title,
                'date_year' => $request->date_year,
                'access' => $request->access ?? 0,
                'planet' => $request->planet,
                'institution' => $request->institution,
                'problems' => $request->problems,
                'preambles' => $request->preambles,
                'theme' => $request->theme,
                'tex_sols' => file_get_contents($request->file('tex_sols')->getRealPath()),
            ];

            $sheet = PimSheet::create($data);
            Log::info('Sheet creada con ID: ' . $sheet->id);

            // Guardar imágenes del preámbulo
            if ($request->hasFile('imagenes_preambulo')) {
                $nombres = $request->input('imagenes_nombres', []);
                $archivos = $request->file('imagenes_preambulo');
                Log::info('Procesando ' . count($archivos) . ' imágenes');
                Log::info('Nombres recibidos: ' . json_encode($nombres));

                foreach ($archivos as $index => $archivo) {
                    if ($archivo && $archivo->isValid()) {
                        $nombreImagen = $nombres[$index] ?? $archivo->getClientOriginalName();
                        $contenido = file_get_contents($archivo->getRealPath());
                        Log::info('Guardando imagen: ' . $nombreImagen . ' (' . strlen($contenido) . ' bytes)');

                        FigureInIntro::create([
                            'title' => $nombreImagen,
                            'figure' => $contenido,
                            'intro_id' => $sheet->id,
                        ]);
                        Log::info('Imagen guardada correctamente');
                    } else {
                        Log::warning('Archivo no válido en índice: ' . $index);
                    }
                }
            } else {
                Log::info('No hay imágenes de preámbulo en la request');
            }

            // Guardar métodos extraídos del preámbulo
            if ($request->filled('metodos_json')) {
                $metodosData = json_decode($request->metodos_json, true);

                if (is_array($metodosData) && count($metodosData) > 0) {
                    Log::info('Procesando ' . count($metodosData) . ' métodos del preámbulo');

                    foreach ($metodosData as $metodoItem) {
                        if (empty($metodoItem['title']) || empty($metodoItem['method_tex'])) {
                            Log::warning('Método inválido, saltando: ' . json_encode($metodoItem));
                            continue;
                        }

                        // Soportar subtema_ids (array) o subtema_id (legacy single)
                        $subtemaIds = [];
                        if (!empty($metodoItem['subtema_ids']) && is_array($metodoItem['subtema_ids'])) {
                            $subtemaIds = $metodoItem['subtema_ids'];
                        } elseif (!empty($metodoItem['subtema_id'])) {
                            $subtemaIds = [$metodoItem['subtema_id']];
                        }

                        if (empty($subtemaIds)) {
                            Log::warning('Método sin subtemas, saltando: ' . json_encode($metodoItem));
                            continue;
                        }

                        // Validar que los subtemas existen para este tema
                        $validIds = Subtema::whereIn('id', $subtemaIds)
                                           ->where('tema_id', $request->theme)
                                           ->pluck('id')
                                           ->toArray();

                        if (empty($validIds)) {
                            Log::warning('Ningún subtema válido para método: ' . $metodoItem['title']);
                            continue;
                        }

                        Metodo::create([
                            'title'       => $metodoItem['title'],
                            'method_tex'  => $metodoItem['method_tex'],
                            'subtema_ids' => implode(',', $validIds),
                            'tema_id'     => (int) $request->theme,
                            'user_id'     => Auth::id(),
                            'institution' => $request->institution ?? 'PIM',
                        ]);

                        Log::info('Método creado: ' . $metodoItem['title']);
                    }
                }
            }

            DB::commit();

            return redirect()->route('pim-sheets.index')->with('success', 'Hoja de problemas subida correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear hoja de problemas: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al crear la hoja: ' . $e->getMessage());
        }
    }

    /**
     * Descargar ZIP con archivo TEX, logo e imágenes
     */
    public function download($id)
    {
        if (AccessHelper::isRestricted()) abort(403);

        Log::info('=== PimSheet Download ===');
        Log::info('ID recibido: ' . $id);

        $sheet = PimSheet::where('id', $id)->first();

        if (!$sheet) {
            Log::error('Hoja no encontrada para ID: ' . $id);
            abort(404, 'Hoja no encontrada.');
        }

        if (empty($sheet->tex_sols)) {
            Log::error('tex_sols vacío para sheet ID: ' . $id);
            abort(404, 'Archivo TEX no disponible.');
        }

        // Generar nombres de archivo
        $baseName = str_replace(' ', '_', $sheet->title) . '_' . $sheet->date_year;
        $texFilename = $baseName . '.tex';
        $zipFilename = $baseName . '.zip';

        // Crear archivo ZIP temporal
        $zipPath = storage_path('app/temp_' . $id . '.zip');
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        // Añadir archivo TEX
        $zip->addFromString($texFilename, $sheet->tex_sols);

        // Extraer nombres de imágenes del contenido TEX
        $imagenesEnTex = $this->extractImageNamesFromTex($sheet->tex_sols);
        $imagenesAnadidas = [];
        $contadorImagenes = 0;

        // Buscar y añadir cada imagen encontrada en el TEX
        foreach ($imagenesEnTex as $imageName) {
            if (isset($imagenesAnadidas[$imageName])) {
                continue; // Ya añadida
            }

            // Buscar primero en intro_id = 0 (logos/imágenes globales)
            $figura = FigureInIntro::where('intro_id', 0)->where('title', $imageName)->first();

            // Si no se encuentra, buscar en intro_id = sheet_id
            if (!$figura) {
                $figura = FigureInIntro::where('intro_id', $id)->where('title', $imageName)->first();
            }

            // Si no se encuentra, buscar sin extensión
            if (!$figura) {
                $imageNameSinExt = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imageName);
                $figura = FigureInIntro::where('intro_id', 0)->where('title', $imageNameSinExt)->first();
                if (!$figura) {
                    $figura = FigureInIntro::where('intro_id', $id)->where('title', $imageNameSinExt)->first();
                }
            }

            if ($figura && $figura->figure) {
                $zip->addFromString($figura->title, $figura->figure);
                $imagenesAnadidas[$imageName] = true;
                $contadorImagenes++;
                Log::info('Imagen añadida desde TEX: ' . $figura->title);
            }
        }

        // Añadir también todas las imágenes de la hoja que no se hayan añadido ya
        $imagenesHoja = FigureInIntro::where('intro_id', $id)->get();
        foreach ($imagenesHoja as $imagen) {
            if ($imagen->figure && !isset($imagenesAnadidas[$imagen->title])) {
                $zip->addFromString($imagen->title, $imagen->figure);
                $imagenesAnadidas[$imagen->title] = true;
                $contadorImagenes++;
                Log::info('Imagen añadida de la hoja: ' . $imagen->title);
            }
        }

        // Añadir imágenes de los problemas (desde pim_figures por problem_id)
        if (!empty($sheet->problems)) {
            $problemIds = array_filter(array_map('trim', explode(',', $sheet->problems)));
            if (!empty($problemIds)) {
                // Buscar todas las figuras de los problemas de la hoja
                $figurasProblemas = Figure::whereIn('problem_id', $problemIds)->get();

                foreach ($figurasProblemas as $figura) {
                    if ($figura->figure && !isset($imagenesAnadidas[$figura->title])) {
                        $zip->addFromString($figura->title, $figura->figure);
                        $imagenesAnadidas[$figura->title] = true;
                        $contadorImagenes++;
                        Log::info('Imagen añadida del problema ' . $figura->problem_id . ': ' . $figura->title);
                    }
                }
            }
        }

        $zip->close();

        Log::info('ZIP creado con ' . (1 + $contadorImagenes) . ' archivos');

        return response()->download($zipPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Descargar PDF compilado con pdflatex
     */
    public function downloadPdf($id)
    {
        $sheet = PimSheet::where('id', $id)->first();

        if (!$sheet) {
            abort(404, 'Hoja no encontrada.');
        }

        if (empty($sheet->tex_sols)) {
            abort(404, 'Archivo TEX no disponible.');
        }

        // Preprocesar TEX: corregir \def\group sin valor
        $texContent = $sheet->tex_sols;
        if (strpos($texContent, '\def\group{') === false) {
            $texContent = str_replace('\def\group', '\def\group{0}', $texContent);
        }

        // Recopilar imágenes
        $images = $this->gatherImages($sheet);

        // Compilar
        $compiler = new LatexCompilerService();
        $result = $compiler->compile($texContent, $images);

        if (!$result['pdf']) {
            $summary = $result['errorSummary'] ?: 'Error desconocido';
            Log::error("Error compilando PDF para sheet {$id}. Temp dir: {$result['tempDir']}. Errores: {$summary}");
            // No limpiar para poder inspeccionar el log: $result['tempDir']/document.log
            return back()->with('error', 'Error al compilar el PDF: ' . $summary);
        }

        $baseName = str_replace(' ', '_', $sheet->title) . '_' . $sheet->date_year . '.pdf';

        // Copiar PDF fuera del tempDir para poder limpiarlo
        $pdfTempPath = storage_path('app/temp/pdf_' . uniqid() . '.pdf');
        if (!is_dir(dirname($pdfTempPath))) {
            mkdir(dirname($pdfTempPath), 0755, true);
        }
        copy($result['pdf'], $pdfTempPath);
        $compiler->cleanup($result['tempDir']);

        return response()->download($pdfTempPath, $baseName, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Recopilar todas las imágenes asociadas a una sheet.
     * Devuelve array asociativo [nombre => datos_binarios]
     */
    private function gatherImages(PimSheet $sheet): array
    {
        $images = [];
        $texContent = $sheet->tex_sols ?? '';

        // Extraer nombres de imágenes del TEX (todo el contenido para PDF)
        $imageNames = $this->extractAllImageNamesFromTex($texContent);

        // Buscar cada imagen
        foreach ($imageNames as $imageName) {
            if (isset($images[$imageName])) {
                continue;
            }

            // Buscar en intro_id = 0 (logos/imágenes globales)
            $figura = FigureInIntro::where('intro_id', 0)->where('title', $imageName)->first();

            // Si no, buscar en intro_id = sheet_id
            if (!$figura) {
                $figura = FigureInIntro::where('intro_id', $sheet->id)->where('title', $imageName)->first();
            }

            // Buscar sin extensión
            if (!$figura) {
                $sinExt = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imageName);
                $figura = FigureInIntro::where('intro_id', 0)->where('title', $sinExt)->first();
                if (!$figura) {
                    $figura = FigureInIntro::where('intro_id', $sheet->id)->where('title', $sinExt)->first();
                }
            }

            // Buscar con LIKE (underscores como comodín)
            if (!$figura) {
                $likePattern = str_replace('_', '%', $imageName);
                $figura = FigureInIntro::where('intro_id', 0)->where('title', 'LIKE', $likePattern)->first();
                if (!$figura) {
                    $figura = FigureInIntro::where('intro_id', $sheet->id)->where('title', 'LIKE', $likePattern)->first();
                }
                if (!$figura) {
                    $sinExt = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imageName);
                    $likePatternSinExt = str_replace('_', '%', $sinExt);
                    $figura = FigureInIntro::where('intro_id', 0)->where('title', 'LIKE', $likePatternSinExt)->first();
                    if (!$figura) {
                        $figura = FigureInIntro::where('intro_id', $sheet->id)->where('title', 'LIKE', $likePatternSinExt)->first();
                    }
                }
            }

            // Buscar en pim_figures (imágenes de problemas)
            if (!$figura) {
                $figura = Figure::where('title', $imageName)->first();
                if (!$figura) {
                    $sinExt = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imageName);
                    $figura = Figure::where('title', $sinExt)->first();
                }
                if (!$figura) {
                    $sinGuiones = str_replace('_', '', $imageName);
                    $figura = Figure::where('title', $sinGuiones)->first();
                }
            }

            if ($figura && $figura->figure) {
                $images[$imageName] = $figura->figure;
            } else {
                Log::warning("Imagen no encontrada en BD para PDF: {$imageName}");
            }
        }

        Log::info("PDF: " . count($imageNames) . " imágenes en TEX, " . count($images) . " encontradas en BD");

        // Añadir también imágenes de la hoja que no estén ya
        $imagenesHoja = FigureInIntro::where('intro_id', $sheet->id)->get();
        foreach ($imagenesHoja as $imagen) {
            if ($imagen->figure && !isset($images[$imagen->title])) {
                $images[$imagen->title] = $imagen->figure;
            }
        }

        // Añadir imágenes de los problemas
        if (!empty($sheet->problems)) {
            $problemIds = array_filter(array_map('trim', explode(',', $sheet->problems)));
            if (!empty($problemIds)) {
                $figurasProblemas = Figure::whereIn('problem_id', $problemIds)->get();
                foreach ($figurasProblemas as $figura) {
                    if ($figura->figure && !isset($images[$figura->title])) {
                        $images[$figura->title] = $figura->figure;
                    }
                }
            }
        }

        return $images;
    }

    /**
     * Extraer nombres de imágenes de todo el contenido TEX (para compilación PDF)
     */
    private function extractAllImageNamesFromTex(string $texContent): array
    {
        // Extraer macros \def\nombre{valor} para resolver referencias
        $macros = [];
        if (preg_match_all('/\\\\def\\\\(\w+)\{([^}]+)\}/', $texContent, $defMatches)) {
            foreach ($defMatches[1] as $i => $macroName) {
                $macros[$macroName] = $defMatches[2][$i];
            }
        }

        $images = [];
        if (preg_match_all('/\\\\includegraphics\s*(?:\[[^\]]*\])?\s*\{([^}]+)\}/', $texContent, $matches)) {
            foreach ($matches[1] as $imageName) {
                $imageName = trim($imageName);
                // Resolver macros: \logo -> valor de \def\logo{...}
                if (preg_match('/^\\\\(\w+)$/', $imageName, $m) && isset($macros[$m[1]])) {
                    $imageName = $macros[$m[1]];
                }
                if (!in_array($imageName, $images)) {
                    $images[] = $imageName;
                }
            }
        }
        return $images;
    }

    /**
     * Extraer nombres de imágenes del contenido TEX
     * Solo busca ANTES de \preambletrue y DENTRO del preámbulo (entre \preambletrue y \preamblefalse)
     * NO busca en la sección de problemas (después de \preamblefalse)
     */
    private function extractImageNamesFromTex($texContent)
    {
        $images = [];
        $searchArea = '';

        // Buscar posición de \preambletrue
        $preambleStart = strpos($texContent, '\\preambletrue');

        if ($preambleStart !== false) {
            // Incluir contenido ANTES de \preambletrue (logo, cabecera)
            $searchArea = substr($texContent, 0, $preambleStart);

            // Incluir contenido del preámbulo (entre \preambletrue y \preamblefalse)
            $preambleEnd = strpos($texContent, '\\preamblefalse', $preambleStart);
            if ($preambleEnd !== false) {
                $searchArea .= substr($texContent, $preambleStart, $preambleEnd - $preambleStart);
            } else {
                // Si no hay \preamblefalse, incluir hasta el final del preámbulo marcado
                $searchArea .= substr($texContent, $preambleStart);
            }
        } else {
            // Sin marcadores de preámbulo, buscar en todo el contenido (fallback)
            $searchArea = $texContent;
        }

        // Buscar \includegraphics con o sin opciones, permitiendo espacios
        if (preg_match_all('/\\\\includegraphics\s*(?:\[[^\]]*\])?\s*\{([^}]+)\}/', $searchArea, $matches)) {
            foreach ($matches[1] as $imageName) {
                $imageName = trim($imageName);
                if (!in_array($imageName, $images)) {
                    $images[] = $imageName;
                }
            }
        }

        return $images;
    }

    /**
     * Detectar extensión de imagen por magic bytes
     */
    private function detectImageExtension($data)
    {
        $header = substr($data, 0, 4);

        if (substr($header, 0, 2) === "\xFF\xD8") {
            return 'jpg';
        } elseif (substr($header, 0, 4) === "\x89PNG") {
            return 'png';
        } elseif (substr($header, 0, 4) === '%PDF') {
            return 'pdf';
        } elseif (substr($header, 0, 3) === 'GIF') {
            return 'gif';
        }

        return 'png'; // fallback
    }

    /**
     * Mostrar hoja de problemas (preámbulo + problemas)
     */
    public function show(Request $request, $id)
    {
        $sheet = PimSheet::with('tema')->find($id);

        if (!$sheet) {
            abort(404, 'Hoja no encontrada.');
        }

        // Procesar preámbulo con LatexHelper (usa "Reto resuelto" para ejercicios)
        $preambleHtml = '';
        if (!empty($sheet->preambles)) {
            $preambleHtml = LatexHelper::toHtmlPreamble($sheet->preambles);
        }

        // Obtener los problemas de la hoja
        $problemas = collect();
        if (!empty($sheet->problems)) {
            $problemIds = array_filter(array_map('trim', explode(',', $sheet->problems)));
            if (!empty($problemIds)) {
                $problemas = Problema::whereIn('id', $problemIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $problemIds) . ')')
                    ->get();
            }
        }

        // Opciones de visualización (checkboxes)
        $mostrarArray = $request->input('mostrar', ['pistas', 'solucion', 'nivel']);
        if (!is_array($mostrarArray)) {
            $mostrarArray = ['pistas', 'solucion', 'nivel'];
        }

        return view('pim_sheets.show', compact('sheet', 'preambleHtml', 'problemas', 'mostrarArray'));
    }

    /**
     * Formulario para editar metadatos de una hoja (solo admin)
     */
    public function edit($id)
    {
        $sheet = PimSheet::findOrFail($id);
        $temas = Tema::orderBy('tema')->get();
        return view('pim_sheets.edit', compact('sheet', 'temas'));
    }

    /**
     * Guardar edición de metadatos (solo admin)
     */
    public function update(Request $request, $id)
    {
        $sheet = PimSheet::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'date_year'   => 'required|integer|min:1900|max:2100',
            'planet'      => 'nullable|string|max:255',
            'institution' => 'required|string|max:256',
            'theme'       => 'required|exists:temas,id',
        ]);

        $sheet->update([
            'title'       => $request->title,
            'date_year'   => $request->date_year,
            'planet'      => $request->planet,
            'institution' => $request->institution,
            'theme'       => $request->theme,
        ]);

        return redirect()->route('pim-sheets.index')->with('success', 'Hoja actualizada correctamente.');
    }

    public function checkDuplicates(Request $request)
    {
        $items = $request->input('items', []);
        $results = [];
        $allSheets = PimSheet::whereNotNull('title')->get(['id', 'title']);

        foreach ($items as $index => $item) {
            $title = trim($item['title'] ?? '');
            $titleMatch = null;

            foreach ($allSheets as $s) {
                if ($titleMatch === null && $title !== '' &&
                    trim((string) $s->title) !== '' &&
                    mb_strtolower(trim((string) $s->title)) === mb_strtolower($title)) {
                    $titleMatch = ['id' => $s->id];
                    break;
                }
            }

            $results[] = ['index' => $index, 'title_match' => $titleMatch];
        }

        return response()->json($results);
    }

    /**
     * Eliminar una hoja de problemas (solo admin)
     */
    public function destroy($id)
    {
        // Solo administradores pueden eliminar sheets
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para eliminar hojas.'], 403);
        }

        $sheet = PimSheet::find($id);

        if (!$sheet) {
            return response()->json(['success' => false, 'message' => 'Hoja no encontrada.'], 404);
        }

        $sheet->delete();

        return response()->json(['success' => true, 'message' => 'Hoja eliminada correctamente.']);
    }
}
