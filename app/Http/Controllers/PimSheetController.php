<?php

namespace App\Http\Controllers;

use App\Models\PimSheet;
use App\Models\Problema;
use App\Models\Tema;
use App\Models\FigureInIntro;
use App\Helpers\LatexHelper;
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

            // Guardar imágenes del preámbulo
            if ($request->hasFile('imagenes_preambulo')) {
                $nombres = $request->input('imagenes_nombres', []);
                $archivos = $request->file('imagenes_preambulo');

                foreach ($archivos as $index => $archivo) {
                    if ($archivo && $archivo->isValid()) {
                        $nombreImagen = $nombres[$index] ?? $archivo->getClientOriginalName();
                        $contenido = file_get_contents($archivo->getRealPath());

                        FigureInIntro::create([
                            'title' => $nombreImagen,
                            'figure' => $contenido,
                            'intro_id' => $sheet->id,
                        ]);
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

        // Añadir logo (intro_id = 0)
        $logo = FigureInIntro::where('intro_id', 0)->first();
        if ($logo && $logo->figure) {
            $logoExtension = $this->detectImageExtension($logo->figure);
            $zip->addFromString($logo->title, $logo->figure);
            Log::info('Logo añadido: ' . $logo->title);
        }

        // Añadir imágenes de la hoja (intro_id = sheet id)
        $imagenes = FigureInIntro::where('intro_id', $id)->get();
        foreach ($imagenes as $imagen) {
            if ($imagen->figure) {
                $zip->addFromString($imagen->title, $imagen->figure);
                Log::info('Imagen añadida: ' . $imagen->title);
            }
        }

        $zip->close();

        Log::info('ZIP creado con ' . (1 + ($logo ? 1 : 0) + count($imagenes)) . ' archivos');

        return response()->download($zipPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
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
