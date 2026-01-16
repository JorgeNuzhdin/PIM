<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Problema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                        ->with('problema')
                        ->orderBy('orden')
                        ->get();
        
        return view('carrito.index', compact('items'));
    }
    
    public function toggle(Request $request)
{
    $problemaId = $request->problema_id;
    $accion = $request->accion; // 'añadir' o null
    
    $item = Carrito::where('user_id', Auth::id())
                  ->where('problema_id', $problemaId)
                  ->first();
    
    if ($item) {
        // Si accion es 'añadir', no hacer nada (ya existe)
        if ($accion === 'añadir') {
            return response()->json(['status' => 'exists', 'count' => $this->getCount()]);
        }
        $item->delete();
        return response()->json(['status' => 'removed', 'count' => $this->getCount()]);
    } else {
        $maxOrden = Carrito::where('user_id', Auth::id())->max('orden') ?? 0;
        Carrito::create([
            'user_id' => Auth::id(),
            'problema_id' => $problemaId,
            'orden' => $maxOrden + 1
        ]);
        return response()->json(['status' => 'added', 'count' => $this->getCount()]);
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
    
    public function count()
    {
        return response()->json(['count' => $this->getCount()]);
    }
    
    private function getCount()
    {
        return Carrito::where('user_id', Auth::id())->count();
    }


    public function descargarTex()
{
    $items = Carrito::where('user_id', Auth::id())
                    ->with('problema')
                    ->orderBy('orden')
                    ->get();
    
    if ($items->isEmpty()) {
        return redirect()->route('carrito.index')->with('error', 'El carrito está vacío');
    }
    
    // Recopilar todos los paquetes únicos
    $packages = [];
    $imagenes = [];
    $contenido = '';
    
    foreach ($items as $index => $item) {
        $problema = $item->problema;

        // Agregar paquetes
        if ($problema->packages) {
            $pkgs = explode(',', $problema->packages);
            foreach ($pkgs as $pkg) {
                $pkg = trim($pkg);
                if ($pkg && !in_array($pkg, $packages)) {
                    $packages[] = $pkg;
                }
            }
        }

        // Construir el contenido del problema
        // Agregar \idtitulo con el ID y título del problema
        $titulo = $problema->title ?? 'sin-titulo';
        $contenido .= "\n\\idtitulo{\\#" . $problema->id . ": " . $titulo . "}\n";

        $contenido .= "\\begin{ejer}\n";
        $contenido .= $problema->problem_tex ?? $problema->problem_html;
        $contenido .= "\n\\end{ejer}\n";
        
        // Pistas
        if ($problema->hints) {
            $contenido .= "\n\\begin{pistas}\n";
            $contenido .= $problema->hints;
            $contenido .= "\n\\end{pistas}\n";
        }
        
        // Solución
        if ($problema->solution_tex || $problema->solution_html) {
            $contenido .= "\n\\begin{proof}[Solución]\n";
            $contenido .= $problema->solution_tex ?? $problema->solution_html;
            $contenido .= "\n\\end{proof}\n";
        }
        
        // Recopilar imágenes mencionadas en el problema
        preg_match_all('/\\\\includegraphics(?:\[.*?\])?\{([^}]+)\}/', $problema->problem_tex . ' ' . $problema->solution_tex, $matches);
        foreach ($matches[1] as $imgName) {
            if (!isset($imagenes[$imgName])) {
                $imagenes[$imgName] = true;
            }
        }
    }
    
    // Crear el preámbulo
    $preambulo = $this->generarPreambulo($packages);
    
    // Contenido completo del TEX
    $texContent = $preambulo . "\n\n\\begin{document}\n\n" . $contenido . "\n\\end{document}";
    
    // Crear ZIP con TEX e imágenes
    return $this->crearZip($texContent, array_keys($imagenes));
}

private function generarPreambulo($packages)
{
    $preambulo = "\\documentclass[a4paper,12pt]{article}\n\n";
    $preambulo .= "% Paquetes básicos\n";
    $preambulo .= "\\usepackage[utf8]{inputenc}\n";
    $preambulo .= "\\usepackage[spanish]{babel}\n";
    $preambulo .= "\\usepackage{amsmath,amssymb,amsthm}\n";
    $preambulo .= "\\usepackage{graphicx}\n";
    $preambulo .= "\\usepackage{enumerate}\n";
    $preambulo .= "\\usepackage{xcolor}\n";
    $preambulo .= "\\usepackage{tikz}\n\n";
    
    // Paquetes adicionales
    if (!empty($packages)) {
        $preambulo .= "% Paquetes adicionales\n";
        foreach ($packages as $pkg) {
            $preambulo .= "\\usepackage{" . $pkg . "}\n";
        }
        $preambulo .= "\n";
    }
    
    // Definir entornos
    $preambulo .= "% Definición de entornos\n";
    $preambulo .= "\\newtheorem{ejer}{Problema}\n";
    $preambulo .= "\\newenvironment{pistas}{\\textbf{Pistas:}\\begin{itemize}}{\\end{itemize}}\n";
    $preambulo .= "\\renewcommand{\\proofname}{Solución}\n";
    $preambulo .= "\\newcommand{\\idtitulo}[1]{\\subsection*{#1}}\n\n";
    
    $preambulo .= "\\title{Problemas de Matemáticas}\n";
    $preambulo .= "\\author{MatemáticaMente}\n";
    $preambulo .= "\\date{\\today}\n";
    
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

    public function limpiar()
{
    Carrito::where('user_id', Auth::id())->delete();
    
    return redirect()->route('carrito.index')->with('success', 'Carrito vaciado correctamente');
}


    
}