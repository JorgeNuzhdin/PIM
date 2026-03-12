<?php

namespace App\Http\Controllers;

use App\Models\Metodo;
use App\Models\MetodoFigure;
use App\Models\Tema;
use App\Models\Subtema;
use App\Helpers\AccessHelper;
use App\Services\LatexCompilerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZipArchive;

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

        $metodosConErrores = \App\Models\MetodoErrorReport::where('solved', false)->distinct()->pluck('metodo_id')->flip()->all();

        return view('metodos.index', compact('metodos', 'temas', 'subtemas', 'institutions', 'metodosConErrores'));
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

        return view('metodos.create', compact('temas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'method_tex'    => 'required|string',
            'tema_id'       => 'required|exists:temas,id',
            'subtema_ids'   => 'required|array|min:1',
            'subtema_ids.*' => 'exists:subtemas,id',
            'institution'   => 'nullable|string|max:256',
            'imagenes.*'    => 'nullable|file|max:5120',
        ]);

        $metodo = Metodo::create([
            'title'       => $request->title,
            'method_tex'  => $request->method_tex,
            'subtema_ids' => implode(',', $request->subtema_ids),
            'tema_id'     => $request->tema_id,
            'user_id'     => Auth::id(),
            'institution' => $request->institution ?? 'PIM',
        ]);

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen && $imagen->isValid()) {
                    MetodoFigure::create([
                        'metodo_id' => $metodo->id,
                        'title'     => $imagen->getClientOriginalName(),
                        'figure'    => file_get_contents($imagen->getRealPath()),
                    ]);
                }
            }
        }

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
        $figuras = MetodoFigure::where('metodo_id', $id)->get();

        $errorReports = \App\Models\MetodoErrorReport::where('metodo_id', $id)->where('solved', false)->get();
        $errorTipo = '00000';
        foreach ($errorReports as $er) {
            for ($i = 0; $i < 5; $i++) {
                if (($er->tipo[$i] ?? '0') === '1') $errorTipo[$i] = '1';
            }
        }
        $hasUnsolvedErrors = $errorReports->isNotEmpty();

        return view('metodos.edit', compact('metodo', 'temas', 'subtemas', 'figuras', 'errorTipo', 'hasUnsolvedErrors'));
    }

    public function update(Request $request, $id)
    {
        $metodo = Metodo::findOrFail($id);

        $user = Auth::user();
        if (!($user->isAdmin() || ($user->canEditProblemas() && $metodo->user_id === $user->id))) {
            abort(403);
        }

        $request->validate([
            'title'         => 'required|string|max:255',
            'method_tex'    => 'required|string',
            'tema_id'       => 'required|exists:temas,id',
            'subtema_ids'   => 'required|array|min:1',
            'subtema_ids.*' => 'exists:subtemas,id',
            'institution'   => 'nullable|string|max:256',
            'imagenes.*'    => 'nullable|file|max:5120',
        ]);

        $metodo->update([
            'title'       => $request->title,
            'method_tex'  => $request->method_tex,
            'subtema_ids' => implode(',', $request->subtema_ids),
            'tema_id'     => $request->tema_id,
            'institution' => $request->institution ?? 'PIM',
        ]);

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen && $imagen->isValid()) {
                    MetodoFigure::updateOrCreate(
                        ['metodo_id' => $metodo->id, 'title' => $imagen->getClientOriginalName()],
                        ['figure' => file_get_contents($imagen->getRealPath())]
                    );
                }
            }
        }

        if ($request->boolean('mark_solved')) {
            \App\Models\MetodoErrorReport::where('metodo_id', $id)->where('solved', false)->update(['solved' => true]);
        }

        return redirect()->route('metodos.show', $metodo->id)->with('success', 'Método actualizado correctamente.');
    }

    public function downloadTex($id)
    {
        if (AccessHelper::isRestricted()) abort(403);
        $metodo = Metodo::findOrFail($id);

        $safeTitle = str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9_\-áéíóúñÁÉÍÓÚÑ ]/u', '', $metodo->title));
        $figuras = MetodoFigure::where('metodo_id', $id)->get();

        if ($figuras->isEmpty()) {
            return response($metodo->method_tex)
                ->header('Content-Type', 'application/x-tex')
                ->header('Content-Disposition', 'attachment; filename="' . $safeTitle . '.tex"');
        }

        // ZIP con .tex + imágenes
        $zipPath = storage_path('app/temp/metodo_' . uniqid() . '.zip');
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString($safeTitle . '.tex', $metodo->method_tex);
        foreach ($figuras as $fig) {
            $zip->addFromString($fig->title, $fig->figure);
        }
        $zip->close();

        return response()->download($zipPath, $safeTitle . '.zip', [
            'Content-Type' => 'application/zip',
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

        MetodoFigure::where('metodo_id', $id)->delete();
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
            'nombre'  => 'required|string|max:255',
            'tema_id' => 'required|exists:temas,id',
        ]);

        $subtema = Subtema::firstOrCreate([
            'nombre'  => $request->nombre,
            'tema_id' => $request->tema_id,
        ]);

        return response()->json(['id' => $subtema->id, 'nombre' => $subtema->nombre]);
    }
}
