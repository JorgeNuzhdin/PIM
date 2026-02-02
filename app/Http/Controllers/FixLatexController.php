<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FixLatexController extends Controller
{
    private $latexCommands = [
        // Matemáticas básicas
        'frac', 'sqrt', 'text', 'textbf', 'textit', 'mathbb', 'mathcal', 'mathbf', 'mathrm',
        // Operadores
        'sum', 'prod', 'int', 'oint', 'lim', 'limsup', 'liminf', 'max', 'min', 'sup', 'inf',
        // Funciones trigonométricas
        'sin', 'cos', 'tan', 'cot', 'sec', 'csc', 'arcsin', 'arccos', 'arctan',
        'sinh', 'cosh', 'tanh', 'coth',
        // Logaritmos
        'log', 'ln', 'lg', 'exp',
        // Símbolos
        'infty', 'cdot', 'cdots', 'ldots', 'dots', 'times', 'div', 'pm', 'mp',
        // Relaciones
        'leq', 'geq', 'neq', 'equiv', 'approx', 'cong', 'sim', 'simeq', 'propto',
        'subset', 'subseteq', 'supset', 'supseteq', 'in', 'notin', 'ni',
        // Conjuntos
        'cup', 'cap', 'setminus', 'emptyset', 'varnothing',
        // Lógica
        'wedge', 'vee', 'neg', 'forall', 'exists', 'nexists', 'implies', 'iff',
        // Letras griegas
        'alpha', 'beta', 'gamma', 'Gamma', 'delta', 'Delta', 'epsilon', 'varepsilon',
        'zeta', 'eta', 'theta', 'Theta', 'vartheta', 'iota', 'kappa', 'lambda', 'Lambda',
        'mu', 'nu', 'xi', 'Xi', 'pi', 'Pi', 'rho', 'varrho', 'sigma', 'Sigma', 'varsigma',
        'tau', 'upsilon', 'Upsilon', 'phi', 'Phi', 'varphi', 'chi', 'psi', 'Psi', 'omega', 'Omega',
        // Delimitadores
        'left', 'right', 'bigl', 'bigr', 'Bigl', 'Bigr', 'biggl', 'biggr', 'Biggl', 'Biggr',
        // Entornos y estructura
        'begin', 'end', 'item', 'label', 'ref', 'eqref', 'cite',
        // Flechas
        'to', 'mapsto', 'rightarrow', 'leftarrow', 'leftrightarrow', 'Rightarrow', 'Leftarrow', 'Leftrightarrow',
        // Espaciado
        'quad', 'qquad', 'hspace', 'vspace',
        // Acentos y modificadores
        'hat', 'bar', 'tilde', 'vec', 'overline', 'underline', 'overbrace', 'underbrace',
        // Otros
        'binom', 'choose', 'over', 'above', 'atop', 'mod', 'pmod', 'bmod',
    ];

    public function index()
    {
        // Solo el usuario Georgy Nuzhdin puede acceder
        if (Auth::user()->name !== 'Georgy Nuzhdin') {
            abort(403, 'No tienes permiso para acceder a esta herramienta.');
        }

        return view('admin.fix_latex');
    }

    public function scan()
    {
        // Solo el usuario Georgy Nuzhdin puede acceder
        if (Auth::user()->name !== 'Georgy Nuzhdin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            // Obtener problemas primero
            $problemas = DB::table('pim_problems')
                ->where(function($query) {
                    $query->whereNotNull('problem_tex')
                          ->orWhereNotNull('solution_tex');
                })
                ->limit(100) // Limitar para pruebas
                ->get();

            $problemasConErrores = [];
            $ejemplos = [];

            // Procesar cada comando LaTeX individualmente para evitar regex demasiado complejo
            foreach ($problemas as $problema) {
                $cambios = [];

                // Revisar problem_tex
                if ($problema->problem_tex) {
                    $original = $problema->problem_tex;
                    $corregido = $this->fixBackslashesSimple($original);

                    if ($original !== $corregido) {
                        $cambios['problem_tex'] = [
                            'original' => mb_substr($original, 0, 300),
                            'corregido' => mb_substr($corregido, 0, 300),
                        ];
                    }
                }

                // Revisar solution_tex
                if ($problema->solution_tex) {
                    $original = $problema->solution_tex;
                    $corregido = $this->fixBackslashesSimple($original);

                    if ($original !== $corregido) {
                        $cambios['solution_tex'] = [
                            'original' => mb_substr($original, 0, 300),
                            'corregido' => mb_substr($corregido, 0, 300),
                        ];
                    }
                }

                if (!empty($cambios)) {
                    $problemasConErrores[$problema->id] = $cambios;

                    // Guardar los primeros 5 ejemplos
                    if (count($ejemplos) < 5) {
                        $ejemplos[] = [
                            'id' => $problema->id,
                            'cambios' => $cambios,
                        ];
                    }
                }
            }

            return response()->json([
                'total_problemas' => count($problemasConErrores),
                'total_campos' => array_sum(array_map('count', $problemasConErrores)),
                'ejemplos' => $ejemplos,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en scan: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al escanear: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    public function fix(Request $request)
    {
        // Solo el usuario Georgy Nuzhdin puede acceder
        if (Auth::user()->name !== 'Georgy Nuzhdin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $problemas = DB::table('pim_problems')
                ->where(function($query) {
                    $query->whereNotNull('problem_tex')
                          ->orWhereNotNull('solution_tex');
                })
                ->get();

            $procesados = 0;
            $errores = 0;

            foreach ($problemas as $problema) {
                try {
                    $updates = [];

                    // Revisar problem_tex
                    if ($problema->problem_tex) {
                        $original = $problema->problem_tex;
                        $corregido = $this->fixBackslashesSimple($original);

                        if ($original !== $corregido) {
                            $updates['problem_tex'] = $corregido;
                        }
                    }

                    // Revisar solution_tex
                    if ($problema->solution_tex) {
                        $original = $problema->solution_tex;
                        $corregido = $this->fixBackslashesSimple($original);

                        if ($original !== $corregido) {
                            $updates['solution_tex'] = $corregido;
                        }
                    }

                    if (!empty($updates)) {
                        DB::table('pim_problems')
                            ->where('id', $problema->id)
                            ->update($updates);
                        $procesados++;
                    }

                } catch (\Exception $e) {
                    \Log::error('Error procesando problema ' . $problema->id . ': ' . $e->getMessage());
                    $errores++;
                }
            }

            return response()->json([
                'success' => true,
                'procesados' => $procesados,
                'errores' => $errores,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en fix: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al aplicar cambios: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    private function buildPattern()
    {
        return '(?<!\\\\)\\b(' . implode('|', array_map('preg_quote', $this->latexCommands)) . ')\\s*([{[(])';
    }

    private function fixBackslashes($text, $pattern)
    {
        return preg_replace_callback(
            '/' . $pattern . '/u',
            function($matches) {
                return '\\' . $matches[0];
            },
            $text
        );
    }

    /**
     * Versión simplificada que procesa comando por comando
     */
    private function fixBackslashesSimple($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Procesar solo los comandos más comunes para evitar regex complejo
        $comandos = ['frac', 'sqrt', 'sum', 'int', 'lim', 'sin', 'cos', 'tan', 'log', 'ln',
                     'alpha', 'beta', 'gamma', 'delta', 'infty', 'leq', 'geq', 'cdot',
                     'text', 'mathbb', 'mathbf', 'left', 'right', 'begin', 'end'];

        foreach ($comandos as $cmd) {
            // Patrón: palabra sin barra seguida de { o (
            $pattern = '/(?<!\\\\)\b' . preg_quote($cmd, '/') . '\s*([{(])/u';
            $text = preg_replace($pattern, '\\' . $cmd . '$1', $text);
        }

        return $text;
    }
}
