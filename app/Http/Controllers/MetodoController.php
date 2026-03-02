<?php

namespace App\Http\Controllers;

use App\Models\Metodo;
use App\Models\Tema;
use App\Models\Subtema;
use App\Models\User;
use App\Helpers\AccessHelper;
use App\Services\LatexCompilerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MetodoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can.edit.problemas')->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        // Usuarios básicos no tienen acceso a métodos
        if (AccessHelper::isRestricted()) {
            abort(403, 'Los métodos no están disponibles para tu tipo de cuenta.');
        }

        $temas = Tema::all();

        $query = Metodo::with(['tema']);

        if ($request->filled('tema_id')) {
            $query->where('tema_id', $request->tema_id);
        }

        if ($request->filled('subtema_id')) {
            $query->whereRaw('FIND_IN_SET(?, subtema_ids)', [$request->subtema_id]);
        }

        if ($request->filled('institution')) {
            $query->where('institution', $request->institution);
        }

        $metodos = $query->orderBy('id', 'desc')->get();

        // Pre-cargar subtemas para evitar N+1 en la vista
        $allSubtemaIds = $metodos->flatMap(fn($m) => $m->subtema_ids_array)->unique()->values()->toArray();
        $allSubtemas = !empty($allSubtemaIds)
            ? Subtema::whereIn('id', $allSubtemaIds)->get()->keyBy('id')
            : collect();

        // Inyectar subtemas pre-cargados en cada método
        $metodos->each(function ($metodo) use ($allSubtemas) {
            $metodo->setRelation('preloadedSubtemas',
                $allSubtemas->only($metodo->subtema_ids_array)->values()
            );
        });

        $subtemas = $request->filled('tema_id')
            ? Subtema::where('tema_id', $request->tema_id)->orderBy('id')->get()
            : collect();

        $institutions = Metodo::distinct()->orderBy('institution')->pluck('institution');

        return view('metodos.index', compact('metodos', 'temas', 'subtemas', 'institutions'));
    }

    public function show($id)
    {
        if (AccessHelper::isRestricted()) {
            abort(403, 'Los métodos no están disponibles para tu tipo de cuenta.');
        }

        $metodo = Metodo::with(['tema', 'user'])->findOrFail($id);

        return view('metodos.show', compact('metodo'));
    }

    public function create()
    {
        $temas = Tema::all();
        $editores = User::whereIn('rol', ['admin', 'editor'])->orderBy('name')->get();

        return view('metodos.create', compact('temas', 'editores'));
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'method_tex' => 'required|string',
            'tema_id' => 'required|exists:temas,id',
            'subtema_ids' => 'required|array|min:1',
            'subtema_ids.*' => 'exists:subtemas,id',
            'institution' => 'nullable|string|max:256',
        ];

        if (Auth::user()->isAdmin()) {
            $rules['user_id'] = 'nullable|exists:users,id';
        }

        $request->validate($rules);

        $userId = Auth::id();
        if (Auth::user()->isAdmin() && $request->filled('user_id')) {
            $userId = $request->user_id;
        }

        Metodo::create([
            'title' => $request->title,
            'method_tex' => $request->method_tex,
            'subtema_ids' => implode(',', $request->subtema_ids),
            'tema_id' => $request->tema_id,
            'user_id' => $userId,
            'institution' => $request->institution ?? 'PIM',
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
        $editores = User::whereIn('rol', ['admin', 'editor'])->orderBy('name')->get();

        return view('metodos.edit', compact('metodo', 'temas', 'subtemas', 'editores'));
    }

    public function update(Request $request, $id)
    {
        $metodo = Metodo::findOrFail($id);

        $user = Auth::user();
        if (!($user->isAdmin() || ($user->canEditProblemas() && $metodo->user_id === $user->id))) {
            abort(403);
        }

        $rules = [
            'title' => 'required|string|max:255',
            'method_tex' => 'required|string',
            'tema_id' => 'required|exists:temas,id',
            'subtema_ids' => 'required|array|min:1',
            'subtema_ids.*' => 'exists:subtemas,id',
            'institution' => 'nullable|string|max:256',
        ];

        if ($user->isAdmin()) {
            $rules['user_id'] = 'nullable|exists:users,id';
        }

        $request->validate($rules);

        $data = [
            'title' => $request->title,
            'method_tex' => $request->method_tex,
            'subtema_ids' => implode(',', $request->subtema_ids),
            'tema_id' => $request->tema_id,
            'institution' => $request->institution ?? 'PIM',
        ];

        if ($user->isAdmin() && $request->filled('user_id')) {
            $data['user_id'] = $request->user_id;
        }

        $metodo->update($data);

        return redirect()->route('metodos.show', $metodo->id)->with('success', 'Método actualizado correctamente.');
    }

    public function downloadTex($id)
    {
        if (AccessHelper::isRestricted()) abort(403);
        $metodo = Metodo::findOrFail($id);

        $filename = preg_replace('/[^a-zA-Z0-9_\-áéíóúñÁÉÍÓÚÑ ]/u', '', $metodo->title);
        $filename = str_replace(' ', '_', $filename) . '.tex';

        return response($metodo->method_tex)
            ->header('Content-Type', 'application/x-tex')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadPdf($id)
    {
        if (AccessHelper::isRestricted()) abort(403);
        $metodo = Metodo::findOrFail($id);

        $texDocument = "\\documentclass[12pt]{article}\n"
            . "\\usepackage[utf8]{inputenc}\n"
            . "\\usepackage[T1]{fontenc}\n"
            . "\\usepackage[spanish]{babel}\n"
            . "\\usepackage{amsmath,amssymb,amsthm}\n"
            . "\\usepackage{geometry}\n"
            . "\\geometry{a4paper, margin=2.5cm}\n"
            . "\\title{" . $metodo->title . "}\n"
            . "\\date{}\n"
            . "\\begin{document}\n"
            . "\\maketitle\n"
            . $metodo->method_tex . "\n"
            . "\\end{document}\n";

        $compiler = new LatexCompilerService();
        $result = $compiler->compile($texDocument);

        if (!$result['pdf']) {
            $summary = $result['errorSummary'] ?: 'Error desconocido';
            Log::error("Error compilando PDF para método {$id}. Temp dir: {$result['tempDir']}. Errores: {$summary}");
            $compiler->cleanup($result['tempDir']);
            return back()->with('error', 'Error al compilar el PDF: ' . $summary);
        }

        $filename = preg_replace('/[^a-zA-Z0-9_\-áéíóúñÁÉÍÓÚÑ ]/u', '', $metodo->title);
        $filename = str_replace(' ', '_', $filename) . '.pdf';

        $pdfTempPath = storage_path('app/temp/pdf_' . uniqid() . '.pdf');
        if (!is_dir(dirname($pdfTempPath))) {
            mkdir(dirname($pdfTempPath), 0755, true);
        }
        copy($result['pdf'], $pdfTempPath);
        $compiler->cleanup($result['tempDir']);

        return response()->download($pdfTempPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    public function destroy($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $metodo = Metodo::find($id);
        if (!$metodo) {
            return response()->json(['success' => false, 'message' => 'Método no encontrado.'], 404);
        }

        $metodo->delete();

        return response()->json(['success' => true, 'message' => 'Método eliminado correctamente.']);
    }

    public function checkDuplicates(Request $request)
    {
        $items = $request->input('items', []);
        $results = [];
        $allMetodos = Metodo::whereNotNull('method_tex')
            ->selectRaw('id, title, SUBSTRING(method_tex, 1, 100) AS method_tex')
            ->get();

        foreach ($items as $index => $item) {
            $title = trim($item['title'] ?? '');
            $contentPrefix = $this->normalizePrefix($item['content_prefix'] ?? '');
            $titleMatch = null;
            $contentMatch = null;

            foreach ($allMetodos as $m) {
                if ($contentMatch === null && $contentPrefix !== '' &&
                    $this->normalizePrefix($m->method_tex) === $contentPrefix) {
                    $contentMatch = ['id' => $m->id];
                }
                if ($titleMatch === null && $title !== '' &&
                    trim((string) $m->title) !== '' &&
                    mb_strtolower(trim((string) $m->title)) === mb_strtolower($title)) {
                    $titleMatch = ['id' => $m->id];
                }
                if ($titleMatch && $contentMatch) break;
            }

            $results[] = ['index' => $index, 'title_match' => $titleMatch, 'content_match' => $contentMatch];
        }

        return response()->json($results);
    }

    private function normalizePrefix(string $text, int $len = 100): string
    {
        return substr(trim(preg_replace('/\s+/', ' ', $text)), 0, $len);
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
