<?php

namespace App\Services;

use App\Models\Figure;

class LatexDocumentService
{
    /**
     * Genera el preámbulo LaTeX completo (idéntico al del carrito).
     *
     * @param array  $packages      Líneas de paquetes/comandos adicionales
     * @param bool   $withSolutions Incluir soluciones
     */
    public static function buildPreamble(array $packages = [], bool $withSolutions = true): string
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

% ---------- Página ----------
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

% ---------- Macros ----------
\newcommand{\exercise}[1]{\begin{ejer}#1\end{ejer}}
\newcommand{\solution}[1]{\ifshowsolutions\begin{proof}[Soluci\'on]#1\end{proof}\fi}
\newcommand{\idtitulo}[1]{\noindent{\color{red}#1\\}}
\newcommand{\pistas}[1]{\textbf{Pistas:} #1}
\newcommand{\nivel}[1]{\noindent{\small\color{gray}\textbf{Dificultad:} #1}\par}
\def\year#1{\noindent{\small\color{gray}\textbf{Curso:} #1}\par}

LATEX;

        $appUrl = config('app.url', url('/'));
        $preambulo .= "\\newcommand{\\pref}[1]{\\href{" . $appUrl . "/problemas/#1}{Problema~#1}}\n";

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

    /**
     * Convierte entornos verbatim/verb para que puedan ir dentro de argumentos de macro.
     */
    public static function sanitizeTexForMacroArg(string $tex): string
    {
        $tex = preg_replace_callback('/\\\\verb\*?(.)(.+?)\1/', function ($matches) {
            $content = str_replace(
                ['\\', '{', '}', '$', '&', '#', '^', '_', '~', '%'],
                ['\\textbackslash{}', '\\{', '\\}', '\\$', '\\&', '\\#', '\\^{}', '\\_', '\\~{}', '\\%'],
                $matches[2]
            );
            return '\\texttt{' . $content . '}';
        }, $tex);

        $tex = preg_replace_callback('/\\\\begin\{verbatim\}(.*?)\\\\end\{verbatim\}/s', function ($matches) {
            $content = str_replace(
                ['\\', '{', '}', '$', '&', '#', '^', '_', '~', '%'],
                ['\\textbackslash{}', '\\{', '\\}', '\\$', '\\&', '\\#', '\\^{}', '\\_', '\\~{}', '\\%'],
                $matches[1]
            );
            return '\\begin{quote}\\ttfamily ' . $content . '\\end{quote}';
        }, $tex);

        $tex = preg_replace('/\\\\begin\{comment\}.*?\\\\end\{comment\}/s', '', $tex);

        return $tex;
    }

    /**
     * Busca en la BD las imágenes referenciadas en $texContent y devuelve sus datos binarios.
     */
    public static function gatherImages(string $texContent): array
    {
        $imageData = [];
        preg_match_all('/\\\\includegraphics(?:\[.*?\])?\{([^}]+)\}/', $texContent, $matches);
        foreach ($matches[1] as $imgName) {
            $imgNameClean = preg_replace('/\.(png|jpg|jpeg|gif|pdf)$/i', '', $imgName);
            $figure = Figure::where('title', $imgName)->orWhere('title', $imgNameClean)->first();
            if ($figure && $figure->figure) {
                $imageData[$imgName] = $figure->figure;
            }
        }
        return $imageData;
    }

    /**
     * Parsea el campo packages de un problema y devuelve array de líneas.
     */
    public static function parsePackages(?string $packagesField): array
    {
        if (!$packagesField) return [];
        $packagesText = preg_replace_callback('/u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $packagesField);
        $pkgs = preg_split('/[\n,]+/', $packagesText);
        return array_values(array_filter(array_map('trim', $pkgs), fn($p) => $p && str_starts_with($p, '\\')));
    }
}
