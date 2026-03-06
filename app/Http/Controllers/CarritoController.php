<?php

namespace App\Http\Controllers;

use App\Helpers\AccessHelper;
use App\Helpers\SchoolYearHelper;
use App\Helpers\SourceHelper;
use App\Models\Carrito;
use App\Models\Metodo;
use App\Models\Problema;
use App\Models\ProblemaTag;
use App\Models\TopicTema;
use App\Models\Figure;
use App\Services\LatexCompilerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CarritoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $items = Carrito::where('user_id', Auth::id())
                        ->with(['problema', 'metodo'])
                        ->orderBy('orden')
                        ->get();

        return view('carrito.index', compact('items'));
    }
    
    public function toggle(Request $request)
    {
        $accion = $request->accion; // 'añadir' o null

        // Determinar si es un problema o un metodo
        if ($request->filled('metodo_id')) {
            $metodoId = $request->metodo_id;
            $item = Carrito::where('user_id', Auth::id())
                          ->where('metodo_id', $metodoId)
                          ->first();

            if ($item) {
                if ($accion === 'añadir') {
                    return response()->json(['status' => 'exists', 'count' => $this->getCount()]);
                }
                $item->delete();
                return response()->json(['status' => 'removed', 'count' => $this->getCount()]);
            } else {
                $currentMetodoCount = Carrito::where('user_id', Auth::id())->whereNotNull('metodo_id')->count();
                if ($currentMetodoCount >= 10) {
                    return response()->json(['status' => 'limit_exceeded', 'message' => 'El carrito ya tiene el máximo de 10 métodos', 'count' => $this->getCount()]);
                }
                // Metodos van ANTES de todo: incrementar orden de existentes e insertar con orden 0
                Carrito::where('user_id', Auth::id())->increment('orden');
                Carrito::create([
                    'user_id' => Auth::id(),
                    'metodo_id' => $metodoId,
                    'orden' => 0
                ]);
                return response()->json(['status' => 'added', 'count' => $this->getCount()]);
            }
        } else {
            $problemaId = $request->problema_id;
            $item = Carrito::where('user_id', Auth::id())
                          ->where('problema_id', $problemaId)
                          ->first();

            if ($item) {
                if ($accion === 'añadir') {
                    return response()->json(['status' => 'exists', 'count' => $this->getCount()]);
                }
                $item->delete();
                return response()->json(['status' => 'removed', 'count' => $this->getCount()]);
            } else {
                $currentProblemaCount = Carrito::where('user_id', Auth::id())->whereNotNull('problema_id')->count();
                if ($currentProblemaCount >= 50) {
                    return response()->json(['status' => 'limit_exceeded', 'message' => 'El carrito ya tiene el máximo de 50 problemas', 'count' => $this->getCount()]);
                }
                $maxOrden = Carrito::where('user_id', Auth::id())->max('orden') ?? 0;
                Carrito::create([
                    'user_id' => Auth::id(),
                    'problema_id' => $problemaId,
                    'orden' => $maxOrden + 1
                ]);
                return response()->json(['status' => 'added', 'count' => $this->getCount()]);
            }
        }
    }
    
    public function updateOrder(Request $request)
    {
        $order = $request->order; // Array de IDs en el nuevo orden
        
        foreach ($order as $index => $id) {
            Carrito::where('id', $id)
                   ->where('user_id', Auth::id())
                   ->update(['orden' => $index]);
        }
        
        return response()->json(['status' => 'success']);
    }
    
    public function addBulk(Request $request)
    {
        $type = $request->input('type');

        if ($type === 'problemas') {
            $query = Problema::query()->select('id');

            $allowedIds = AccessHelper::allowedProblemIds();
            if ($allowedIds !== null) {
                if (empty($allowedIds)) {
                    return response()->json(['error' => 'No hay problemas disponibles'], 200);
                }
                $query->whereIn('id', $allowedIds);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->where('id', $buscar);
                    }
                    $q->orWhere('title', 'LIKE', "%{$buscar}%")
                      ->orWhere('problem_tex', 'LIKE', "%{$buscar}%")
                      ->orWhere('solution_tex', 'LIKE', "%{$buscar}%")
                      ->orWhere('source', 'LIKE', "%{$buscar}%");
                });
            }

            if ($request->filled('tema_id')) {
                $topicTitles = TopicTema::where('tema_id', $request->tema_id)->pluck('topic_title')->toArray();
                $ids = ProblemaTag::whereIn('tag', $topicTitles)->distinct()->pluck('problem_id')->toArray();
                $query->whereIn('id', !empty($ids) ? $ids : [0]);
            }

            $tagFilter = $request->filled('topic_title') ? $request->topic_title
                       : ($request->filled('topic_display') ? $request->topic_display : null);
            if ($tagFilter) {
                $ids = ProblemaTag::where('tag', $tagFilter)->distinct()->pluck('problem_id')->toArray();
                if (empty($ids)) {
                    $ids = ProblemaTag::where('tag', 'LIKE', "%{$tagFilter}%")->distinct()->pluck('problem_id')->toArray();
                }
                $query->whereIn('id', !empty($ids) ? $ids : [0]);
            }

            if ($request->filled('difficulty_min')) {
                $query->where('difficulty', '>=', $request->difficulty_min);
            }
            if ($request->filled('difficulty_max')) {
                $query->where('difficulty', '<=', $request->difficulty_max);
            }

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

            if ($request->filled('source')) {
                SourceHelper::applySourceFilterWithCommas($query, $request->source);
            }

            if ($request->filled('proponent_id')) {
                $query->where('proponent_id', $request->proponent_id);
            } elseif ($request->boolean('solo_mios') && in_array(Auth::user()->rol, ['admin', 'editor'])) {
                $query->where('proponent_id', Auth::id());
            }

            $filteredCount = $query->count();
            if ($filteredCount > 50) {
                return response()->json(['error' => "No puedo añadir más de 50 problemas ({$filteredCount} encontrados con estos filtros)"], 422);
            }

            $currentCount = Carrito::where('user_id', Auth::id())->whereNotNull('problema_id')->count();
            $filteredIds = $query->pluck('id')->toArray();
            $alreadyInCart = Carrito::where('user_id', Auth::id())
                ->whereNotNull('problema_id')->whereIn('problema_id', $filteredIds)
                ->pluck('problema_id')->toArray();
            $toAdd = array_values(array_diff($filteredIds, $alreadyInCart));

            if ($currentCount + count($toAdd) > 50) {
                $canAdd = 50 - $currentCount;
                return response()->json(['error' => "El carrito ya tiene {$currentCount} problemas. Solo puedes añadir {$canAdd} más (límite: 50)"], 422);
            }

            $maxOrden = Carrito::where('user_id', Auth::id())->max('orden') ?? 0;
            foreach ($toAdd as $id) {
                $maxOrden++;
                Carrito::create(['user_id' => Auth::id(), 'problema_id' => $id, 'orden' => $maxOrden]);
            }

            $added = count($toAdd);
            $already = count($alreadyInCart);
            if ($added === 0) {
                $msg = 'Todos los problemas ya estaban en el carrito';
            } else {
                $msg = "{$added} problema" . ($added !== 1 ? 's' : '') . " añadido" . ($added !== 1 ? 's' : '') . " al carrito";
                if ($already > 0) {
                    $msg .= " ({$already} ya estaba" . ($already !== 1 ? 'n' : '') . ")";
                }
            }

            return response()->json(['success' => true, 'message' => $msg, 'count' => $this->getCount()]);
        }

        if ($type === 'metodos') {
            $query = Metodo::query()->select('id');

            if ($request->filled('tema_id')) {
                $query->where('tema_id', $request->tema_id);
            }
            if ($request->filled('subtema_id')) {
                $query->whereRaw('FIND_IN_SET(?, subtema_ids)', [$request->subtema_id]);
            }
            if ($request->filled('institution')) {
                $query->where('institution', $request->institution);
            }

            $filteredCount = $query->count();
            if ($filteredCount > 10) {
                return response()->json(['error' => "No puedo añadir más de 10 métodos ({$filteredCount} encontrados con estos filtros)"], 422);
            }

            $currentCount = Carrito::where('user_id', Auth::id())->whereNotNull('metodo_id')->count();
            $filteredIds = $query->pluck('id')->toArray();
            $alreadyInCart = Carrito::where('user_id', Auth::id())
                ->whereNotNull('metodo_id')->whereIn('metodo_id', $filteredIds)
                ->pluck('metodo_id')->toArray();
            $toAdd = array_values(array_diff($filteredIds, $alreadyInCart));

            if ($currentCount + count($toAdd) > 10) {
                $canAdd = 10 - $currentCount;
                return response()->json(['error' => "El carrito ya tiene {$currentCount} métodos. Solo puedes añadir {$canAdd} más (límite: 10)"], 422);
            }

            foreach ($toAdd as $id) {
                Carrito::where('user_id', Auth::id())->increment('orden');
                Carrito::create(['user_id' => Auth::id(), 'metodo_id' => $id, 'orden' => 0]);
            }

            $added = count($toAdd);
            $already = count($alreadyInCart);
            if ($added === 0) {
                $msg = 'Todos los métodos ya estaban en el carrito';
            } else {
                $msg = "{$added} método" . ($added !== 1 ? 's' : '') . " añadido" . ($added !== 1 ? 's' : '') . " al carrito";
                if ($already > 0) {
                    $msg .= " ({$already} ya estaba" . ($already !== 1 ? 'n' : '') . ")";
                }
            }

            return response()->json(['success' => true, 'message' => $msg, 'count' => $this->getCount()]);
        }

        return response()->json(['error' => 'Tipo no válido'], 400);
    }

    public function count()
    {
        $items = Carrito::where('user_id', Auth::id())->get();
        return response()->json([
            'count' => $items->count(),
            'problema_ids' => $items->whereNotNull('problema_id')->pluck('problema_id')->values()->toArray(),
            'metodo_ids' => $items->whereNotNull('metodo_id')->pluck('metodo_id')->values()->toArray(),
        ]);
    }

    private function getCount()
    {
        return Carrito::where('user_id', Auth::id())->count();
    }


    public function descargarTex()
    {
        if (AccessHelper::isRestricted()) abort(403);

        $items = Carrito::where('user_id', Auth::id())
                        ->with(['problema', 'metodo'])
                        ->orderBy('orden')
                        ->get();

        if ($items->isEmpty()) {
            return redirect()->route('carrito.index')->with('error', 'El carrito está vacío');
        }

        $result = $this->buildTexContent($items);

        // Crear el preámbulo
        $preambulo = $this->generarPreambulo($result['packages']);

        // Contenido completo del TEX
        $texContent = $preambulo . "\n\n\\begin{document}\n\n" . $result['contenido'] . "\n\\end{document}";

        // Crear ZIP con TEX e imágenes
        return $this->crearZip($texContent, array_keys($result['imagenes']));
    }

    private function buildTexContent($items): array
    {
        $packages = [];
        $imagenes = [];
        $contenido = '';

        foreach ($items as $item) {
            if ($item->isMetodo()) {
                $metodo = $item->metodo;
                $contenido .= "\n% --- Método: " . $metodo->title . " ---\n";
                $contenido .= $metodo->method_tex . "\n";

                // Recopilar imágenes del método
                preg_match_all('/\\\\includegraphics(?:\[.*?\])?\{([^}]+)\}/', $metodo->method_tex, $matches);
                foreach ($matches[1] as $imgName) {
                    if (!isset($imagenes[$imgName])) {
                        $imagenes[$imgName] = true;
                    }
                }
            } else {
                $problema = $item->problema;

                // Agregar paquetes del problema
                if ($problema->packages) {
                    $packagesText = preg_replace_callback('/u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $problema->packages);
                    $pkgs = preg_split('/[\n,]+/', $packagesText);
                    foreach ($pkgs as $pkg) {
                        $pkg = trim($pkg);
                        // Solo añadir entradas que parezcan comandos LaTeX válidos
                        if ($pkg && str_starts_with($pkg, '\\') && !in_array($pkg, $packages)) {
                            $packages[] = $pkg;
                        }
                    }
                }

                $titulo = $problema->title ?? 'sin-titulo';
                $contenido .= "\n\\idtitulo{\\#" . $problema->id . ": " . $titulo . "}\n";
                $contenido .= "\\exercise{";
                $contenido .= $this->sanitizeTexForMacroArg($problema->problem_tex);
                $contenido .= "}\n";

                if ($problema->hints) {
                    $contenido .= "\n\\pistas{" . $this->sanitizeTexForMacroArg($problema->hints) . "}\n";
                }

                if ($problema->solution_tex) {
                    $contenido .= "\n\\solution{";
                    $contenido .= $this->sanitizeTexForMacroArg($problema->solution_tex);
                    $contenido .= "}\n";
                }

                // Recopilar imágenes del problema
                $texToSearch = ($problema->problem_tex ?? '') . ' ' . ($problema->solution_tex ?? '');
                preg_match_all('/\\\\includegraphics(?:\[.*?\])?\{([^}]+)\}/', $texToSearch, $matches);
                foreach ($matches[1] as $imgName) {
                    if (!isset($imagenes[$imgName])) {
                        $imagenes[$imgName] = true;
                    }
                }
            }
        }

        return ['packages' => $packages, 'imagenes' => $imagenes, 'contenido' => $contenido];
    }

private function generarPreambulo($packages, bool $withSolutions = true)
{
    $solutionsTrue  = $withSolutions ? '\\showsolutionstrue   % para profesores' : '%\\showsolutionstrue   % para profesores';
    $solutionsFalse = $withSolutions ? '% \\showsolutionsfalse  % para alumnos'  : '\\showsolutionsfalse  % para alumnos';

    $preambulo = <<<'LATEX'
\documentclass[12pt,a4paper]{article}
\pdfpagewidth=210mm
\pdfpageheight=297mm

\newif\ifshowsolutions
LATEX;
    $preambulo .= "\n" . $solutionsTrue . "\n" . $solutionsFalse . "\n";
    $preambulo .= <<<'LATEX'

% ---------- Paquetes ----------
\usepackage[utf8]{inputenc}
\usepackage{gensymb}
\usepackage{subcaption}
\usepackage{amssymb}
\usepackage{mathtools}
\usepackage{float}
\usepackage{amsthm}
\usepackage{graphicx,amssymb,latexsym,amsmath,verbatim,amsthm}
\usepackage{pgfplots}
\pgfplotsset{compat=1.15}
\usepackage{mathrsfs}
\usepackage{tikz}
\usetikzlibrary{arrows}
\usetikzlibrary{arrows.meta}
\usetikzlibrary{math,angles,quotes}
\usepackage{color}
\usepackage{enumitem}
\usepackage{textcomp,gensymb}
\usepackage{multicol}
\usepackage{ifthen}
\usepackage{xcolor}
\usepackage{hyperref}
\usetikzlibrary{positioning}
\usepackage{tcolorbox}
\usetikzlibrary{math,angles,quotes}
\usetikzlibrary{positioning}
\usepackage{subcaption}
\usetikzlibrary{patterns}
\usepackage{tikz-cd}
\usepackage{float}
\usepackage{tikz}
\usepackage{mathtools}
\usepackage{gensymb}
\usepackage{graphicx,amssymb,latexsym,amsmath}
\usepackage{multirow}
\usepackage{color}
\usepackage{tikz}
\usetikzlibrary{patterns}
\usetikzlibrary{angles,quotes}
\usepackage{array}
\usetikzlibrary{arrows}
\usepackage{tikz-cd}
\usepackage{twemojis}

% ---------- Página (igual que plantilla_hoja) ----------
\renewcommand{\thepage}{}
\renewcommand{\baselinestretch}{1}
\setlength{\parindent}{2em}
\setlength{\textwidth}{19cm}
\setlength{\textheight}{27cm}
\setlength{\topmargin}{-2cm}
\setlength{\oddsidemargin}{-1.5cm}
\pagestyle{empty}
\raggedbottom

% ---------- Comandos matemáticos ----------
\DeclareMathOperator{\mcd}{mcd}
\DeclareMathOperator{\cm}{cm}
\renewcommand{\min}{\textup{m\'in}\,}
\newcommand{\modd}[1]{\ (\mathrm{m\acute{o}d}\ #1)}
\newcommand{\ubrace}[2]{\underset{#1}{\underbrace{#2}}}
\newcommand{\arr}{{\fontfamily{ptm}\selectfont @}}
\newcommand{\equis}[1]{\draw[color=zzccqq,line width=2pt](#1)-- ++(-3.5pt,3.5pt)-- ++(7pt,-7pt);\draw[color=zzccqq,line width=2pt](#1)-- ++(3.5pt,3.5pt)-- ++(-7pt,-7pt);}

% ---------- Teoremas ----------
\newtheorem{theorem}{Teorema}
\theoremstyle{definition}
\newtheorem*{definition}{Definición}
\newtheorem{ejer}{Problema}
\newtheorem*{ejem}{Ejemplo resuelto}
\newtheorem*{eje}{Ejemplo}
\newtheorem{defin}{Definición}

% ---------- Macros carrito ----------
\newcommand{\exercise}[1]{\begin{ejer}#1\end{ejer}}
\newcommand{\solution}[1]{\ifshowsolutions\begin{proof}[Soluci\'on]#1\end{proof}\fi}
\newcommand{\idtitulo}[1]{\noindent{\color{red}#1\\}}
\newcommand{\pistas}[1]{\textbf{Pistas:} #1}

LATEX;

    // \pref{N} → enlace al problema N (URL de la app)
    $appUrl = config('app.url', url('/'));
    $preambulo .= "\\newcommand{\\pref}[1]{\\href{" . $appUrl . "/problemas/#1}{Problema~#1}}\n";

    // Añadir paquetes específicos de los problemas (evitar duplicados)
    if (!empty($packages)) {
        $preambulo .= "\n% Paquetes de problemas\n";
        foreach ($packages as $pkg) {
            $pkg = trim($pkg);
            if ($pkg && !str_starts_with($pkg, '%')) {
                $pkgName = $pkg;
                if (preg_match('/\\\\usepackage(?:\[.*?\])?\{(.+?)\}/', $pkg, $m)) {
                    $pkgName = $m[1];
                } elseif (preg_match('/\\\\(?:newcommand|renewcommand|DeclareMathOperator|def)\s*\{?(\\\\[A-Za-z@]+)/', $pkg, $m)) {
                    $pkgName = $m[1];
                }
                if (strpos($preambulo, $pkgName) === false) {
                    $preambulo .= $pkg . "\n";
                }
            }
        }
    }

    return $preambulo;
}

private function crearZip($texContent, $imagenesNombres)
{
    $zipName = 'problemas_' . date('Y-m-d_His') . '.zip';
    $zipPath = storage_path('app/temp/' . $zipName);
    
    // Crear directorio temporal si no existe
    if (!file_exists(storage_path('app/temp'))) {
        mkdir(storage_path('app/temp'), 0755, true);
    }
    
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Agregar archivo TEX
        $zip->addFromString('problemas.tex', $texContent);
        
        // Agregar imágenes
        if (!empty($imagenesNombres)) {
            foreach ($imagenesNombres as $imgName) {
                // Buscar imagen en la base de datos
                $imgNameClean = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imgName);
                
                $figure = \App\Models\Figure::where('title', $imgName)
                                           ->orWhere('title', $imgNameClean)
                                           ->orWhere('title', $imgNameClean . '.pdf')
                                           ->first();
                
                if ($figure && $figure->figure) {
                    // Determinar extensión
                    $header = substr($figure->figure, 0, 4);
                    if (substr($header, 0, 2) === "\xFF\xD8") {
                        $ext = 'jpg';
                    } elseif (substr($header, 0, 4) === "\x89PNG") {
                        $ext = 'png';
                    } elseif (substr($header, 0, 4) === '%PDF') {
                        $ext = 'pdf';
                    } else {
                        $ext = 'png';
                    }
                    
                    // Agregar al ZIP
                    $fileName = pathinfo($imgName, PATHINFO_FILENAME) . '.' . $ext;
                    $zip->addFromString($fileName, $figure->figure);
                }
            }
        }
        
        $zip->close();
        
        // Descargar y luego eliminar
        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
    
    return redirect()->route('carrito.index')->with('error', 'Error al crear el archivo ZIP');
}

    public function presentacion()
    {
        $items = Carrito::where('user_id', Auth::id())
                        ->with(['problema', 'metodo'])
                        ->orderBy('orden')
                        ->get();

        if ($items->isEmpty()) {
            return redirect()->route('carrito.index')->with('error', 'El carrito está vacío');
        }

        return view('carrito.presentacion', compact('items'));
    }

    public function descargarPdf()
    {
        $items = Carrito::where('user_id', Auth::id())
                        ->with(['problema', 'metodo'])
                        ->orderBy('orden')
                        ->get();

        if ($items->isEmpty()) {
            return redirect()->route('carrito.index')->with('error', 'El carrito está vacío');
        }

        $withSolutions = request()->query('solutions', '1') !== '0';

        $result = $this->buildTexContent($items);
        $imagenes = $result['imagenes'];

        $preambulo = $this->generarPreambulo($result['packages'], $withSolutions);

        $logoPath = resource_path('LogoPIMgeneral.png');

        $texContent = $preambulo . "\n\n\\begin{document}\n\n"
            . "\\begin{center}\\includegraphics[height=1.8cm]{LogoPIMgeneral.png}\\end{center}\n"
            . "\\vspace{0.5cm}\n\n"
            . $result['contenido'] . "\n\\end{document}";

        // Recopilar datos binarios de imágenes
        $imageData = [];

        // Logo PIM general
        if (file_exists($logoPath)) {
            $imageData['LogoPIMgeneral.png'] = file_get_contents($logoPath);
        }

        foreach (array_keys($imagenes) as $imgName) {
            $imgNameClean = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imgName);
            $figure = Figure::where('title', $imgName)
                            ->orWhere('title', $imgNameClean)
                            ->first();

            if ($figure && $figure->figure) {
                $imageData[$imgName] = $figure->figure;
            }
        }

        // Compilar PDF
        $compiler = new LatexCompilerService();
        $result = $compiler->compile($texContent, $imageData);

        if (!$result['pdf']) {
            $errorSummary = $result['errorSummary'] ?: 'Error desconocido';
            $ids = $items->map(fn($i) => $i->problema_id ? "P{$i->problema_id}" : "M{$i->metodo_id}")->implode(',');
            Log::error("Error compilando PDF del carrito. IDs: {$ids}. Errores: {$errorSummary}. Temp dir: {$result['tempDir']}");
            // No limpiar tempDir para poder depurar el .tex generado
            return back()->with('error', 'Error al compilar el PDF: ' . $errorSummary);
        }

        $baseName = 'problemas_' . date('Y-m-d_His') . '.pdf';
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

    public function limpiar()
    {
        Carrito::where('user_id', Auth::id())->delete();

        return redirect()->route('carrito.index')->with('success', 'Carrito vaciado correctamente');
    }

    /**
     * Sanitiza contenido LaTeX para que pueda usarse dentro de argumentos de macros.
     * Reemplaza \verb y \begin{verbatim} que no pueden ir dentro de \exercise{...}.
     */
    private function sanitizeTexForMacroArg(string $tex): string
    {
        // Reemplazar \verb|...|, \verb+...+, \verb*|...| etc. con \texttt{...}
        $tex = preg_replace_callback('/\\\\verb\*?(.)(.+?)\1/', function ($matches) {
            $content = $matches[2];
            // Escapar caracteres especiales de LaTeX para \texttt
            $content = str_replace(
                ['\\', '{', '}', '$', '&', '#', '^', '_', '~', '%'],
                ['\\textbackslash{}', '\\{', '\\}', '\\$', '\\&', '\\#', '\\^{}', '\\_', '\\~{}', '\\%'],
                $content
            );
            return '\\texttt{' . $content . '}';
        }, $tex);

        // Reemplazar \begin{verbatim}...\end{verbatim} con \begin{quote}\ttfamily ...\end{quote}
        $tex = preg_replace_callback('/\\\\begin\{verbatim\}(.*?)\\\\end\{verbatim\}/s', function ($matches) {
            $content = $matches[1];
            $content = str_replace(
                ['\\', '{', '}', '$', '&', '#', '^', '_', '~', '%'],
                ['\\textbackslash{}', '\\{', '\\}', '\\$', '\\&', '\\#', '\\^{}', '\\_', '\\~{}', '\\%'],
                $content
            );
            return '\\begin{quote}\\ttfamily ' . $content . '\\end{quote}';
        }, $tex);

        // Eliminar \begin{comment}...\end{comment} (del paquete verbatim, no puede ir en argumentos)
        $tex = preg_replace('/\\\\begin\{comment\}.*?\\\\end\{comment\}/s', '', $tex);

        return $tex;
    }
}