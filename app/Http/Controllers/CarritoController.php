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

        // Agregar paquetes y comandos
        if ($problema->packages) {
            // Decodificar escapes Unicode primero
            $packagesText = preg_replace('/u([0-9a-fA-F]{4})/', '', $problema->packages);

            // Dividir por saltos de línea o comas
            $pkgs = preg_split('/[\n,]+/', $packagesText);

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

        $contenido .= "\\exercise{";
        $contenido .= $problema->problem_tex ?? $problema->problem_html;
        $contenido .= "}\n";

        // Pistas
        if ($problema->hints) {
            $contenido .= "\n\\pistas{";
            $contenido .= $problema->hints;
            $contenido .= "}\n";
        }

        // Solución
        if ($problema->solution_tex || $problema->solution_html) {
            $contenido .= "\n\\solution{";
            $contenido .= $problema->solution_tex ?? $problema->solution_html;
            $contenido .= "}\n";
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
    $preambulo = <<<'LATEX'
\documentclass[12pt,a4paper]{article}
\usepackage{amsmath}

%%%%%%%%%%%%%%%%%%%%%
\newif\ifshowsolutions
\newif\ifshowinfo
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%%%%%%%%%%% Setting %%%%%%%%%%%%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%\showsolutionstrue   % para profesores
 \showsolutionsfalse    % para alumnos
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
\showinfotrue  % para ver grupos y títulos en versión generica
%\showinfofalse % para genérica para publicar
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 0 for genérica
% 1 for Neptuno
% 2 for Marte
% 3 for Urano
% 4 for Júpiter
% 5 for Venus
% 6 for Mercurio
\def\group{6}
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
\def\logo{logo2526pim.png}
\def\title{Desigualdades}
\def\dates{6, 13, 20 y 27 de febrero de  2026}
\def\datefir{6 de febrero}
\def\datesec{13 de febrero}
\def\datethi{20 de febrero}
\def\datefou{27 de febrero}
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


\usepackage[utf8]{inputenc}
\usepackage{gensymb}
\usepackage{subcaption}
\usepackage{amssymb}
\usepackage{mathtools}
\usepackage{float}
\usepackage{amsthm}
\usepackage{graphicx,amssymb,latexsym,amsmath,verbatim, amsthm}
\usepackage{pgfplots}
\pgfplotsset{compat=1.15}
\usepackage{mathrsfs}
\usepackage{tikz}
\usetikzlibrary{arrows}
\usetikzlibrary{arrows.meta}
\usetikzlibrary{math,angles,quotes}
\usepackage{color}
\usepackage{geometry}
\usepackage{enumitem}
\usepackage{textcomp,gensymb}
\usepackage{multicol}
\usepackage{ifthen}
\usepackage{gensymb}
\usepackage{graphicx}
\usepackage{amssymb}
\usepackage{amsthm}
\usepackage{xcolor}
\usepackage{tikz}
\usepackage{float}
\usepackage{hyperref}
\usetikzlibrary{positioning}
\usepackage{mathtools}
\usepackage{tcolorbox}

%\usepackage{subcaption}

\usetikzlibrary{math,angles,quotes}
\usepackage{tikz}
\usepackage{circuitikz}

\DeclareMathOperator{\mcd}{mcd}
\renewcommand{\min}{\textup{m\'in}\,}
\usetikzlibrary{positioning}
\usepackage{amsthm}
\usepackage{subcaption}
\usetikzlibrary{patterns}
\usepackage{tikz-cd}
\usepackage{float}
\usepackage{tikz}
\usepackage{mathtools}
\usepackage{gensymb}

\usepackage{graphicx,amssymb,latexsym,amsmath}
%\renewcommand{\thepage}{}
\renewcommand{\baselinestretch}{1}
\setlength{\parindent}{2em} \setlength{\textwidth}{19cm}
\setlength{\textheight}{25cm} \setlength{\topmargin}{-2cm}
\setlength{\oddsidemargin}{-1.5cm}

\usepackage{multirow}
\usepackage{color}
\usepackage{tikz}
\usetikzlibrary{patterns}
\usetikzlibrary{angles,quotes}
\usepackage{array}
\usetikzlibrary{arrows}
\newcommand{\modd}[1]{\ (\mathrm{m\acute{o}d}\ #1)}
\usepackage{tikz-cd}
\usepackage{twemojis}
%%%%%%%%%%%%%%%%%%%%%%%%%
%\pagestyle{empty}

\newcommand{\equis}[1]{	\draw[color=zzccqq,line width=2pt](#1)--	++(-3.5pt,3.5pt)-- ++(7pt,-7pt);\draw[color=zzccqq,line width=2pt](#1)--	++(3.5pt,3.5pt)-- ++(-7pt,-7pt);}

\newcommand{\arr}{%
	{\fontfamily{ptm}\selectfont @}%
}
\DeclareMathOperator{\cm}{cm}
\newcommand{\ubrace}[2]{\underset{#1}{\underbrace{#2}}}

%%%%%%%%%%%%%%%%%%%%%%%%%%%
\newtheorem{theorem}{Teorema}
\theoremstyle{definition}
\newtheorem*{definition}{Definición}
\newtheorem{ejer}{Problema}
\newtheorem*{ejem}{Ejemplo resuelto}
\newtheorem*{eje}{Ejemplo}
\newtheorem{defin} {Definición}


\newif\ifnep
\newcommand{\N}{\neptrue}
\newcommand{\NN}{\nepfalse}
\newif\ifmar
\newcommand{\M}{\martrue}
\newcommand{\MM}{\marfalse}
\newif\ifura
\newcommand{\U}{\uratrue}
\newcommand{\UU}{\urafalse}
\newif\ifjup
\newcommand{\J}{\juptrue}
\newcommand{\JJ}{\jupfalse}
\newif\ifven
\newcommand{\V}{\ventrue}
\newcommand{\VV}{\venfalse}
\newif\ifmer
\newcommand{\X}{\mertrue}
\newcommand{\XX}{\merfalse}


\newif\ifpreamble

\newcommand{\exercise}[1]{
\ifpreamble{\begin{ejer}#1\end{ejer}}\else{
\ifnum\group=0{
\ifshowinfo{\noindent\color{blue}\ifnep{N}\fi\ifmar{M}\fi\ifura{U}\fi\ifjup{J}\fi\ifven{V}\fi\ifmer{X}\fi}\fi
\begin{ejer}#1\end{ejer}}\fi
\ifnum\group=1{\ifnep{\begin{ejer}#1\end{ejer}}\fi}\fi
\ifnum\group=2{\ifmar{\begin{ejer}#1\end{ejer}}\fi}\fi
\ifnum\group=3{\ifura{\begin{ejer}#1\end{ejer}}\fi}\fi
\ifnum\group=4{\ifjup{\begin{ejer}#1\end{ejer}}\fi}\fi
\ifnum\group=5{\ifven{\begin{ejer}#1\end{ejer}}\fi}\fi
\ifnum\group=6{\ifmer{\begin{ejer}#1\end{ejer}}\fi}\fi
}\fi
}



\newcommand{\solution}[1]{
\ifshowsolutions{
\ifpreamble{\begin{proof}[Solución]#1\end{proof}}\else{
\ifnum\group=0{\begin{proof}[Solución]#1\end{proof}}\fi
\ifnum\group=1{\ifnep{{\begin{proof}[Solución]#1\end{proof}}}\fi}\fi
\ifnum\group=2{\ifmar{\begin{proof}[Solución]#1\end{proof}}\fi}\fi
\ifnum\group=3{\ifura{\begin{proof}[Solución]#1\end{proof}}\fi}\fi
\ifnum\group=4{\ifjup{\begin{proof}[Solución]#1\end{proof}}\fi}\fi
\ifnum\group=5{\ifven{\begin{proof}[Solución]#1\end{proof}}\fi}\fi
\ifnum\group=6{\ifmer{\begin{proof}[Solución]#1\end{proof}}\fi}\fi
}\fi
}\fi
\NN\MM\UU\JJ\VV\XX}

\newcommand{\idtitulo}[1]{
\ifnum\group=0 \ifshowinfo \noindent{\color{red}#1\\}\fi\fi
}


\newcommand{\pistas}[1]{\textbf{Pistas:} #1}

%%%%%%%%%%%%%%%%%%%%%%%%%%%


LATEX;

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