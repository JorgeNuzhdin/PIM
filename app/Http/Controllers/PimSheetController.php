<?php

namespace App\Http\Controllers;

use App\Models\PimSheet;
use App\Models\Tema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'institution' => 'nullable|string|max:256',
            'theme' => 'nullable|exists:temas,id',
            'problems' => 'nullable|string|max:2048',
            'preambles' => 'nullable|string',
            'tex_sols' => 'required|file|mimes:tex,txt|max:10240',
        ]);

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

        PimSheet::create($data);

        return redirect()->route('pim-sheets.index')->with('success', 'Hoja de problemas subida correctamente.');
    }

    /**
     * Descargar archivo TEX
     */
    public function download($id)
    {
        $sheet = PimSheet::where('id', $id)->first();

        if (!$sheet) {
            abort(404, 'Hoja no encontrada.');
        }

        if (empty($sheet->tex_sols)) {
            abort(404, 'Archivo TEX no disponible.');
        }

        // Generar nombre de archivo
        $filename = str_replace(' ', '_', $sheet->title) . '_' . $sheet->date_year . '.tex';

        return response($sheet->tex_sols)
            ->header('Content-Type', 'text/x-tex')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
