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
            \Log::info('=== Iniciando escaneo de LaTeX ===');

            set_time_limit(300); // 5 minutos máximo
            ini_set('memory_limit', '512M'); // Aumentar límite de memoria

            // Obtener solo IDs primero para procesar en lotes
            $totalProblemas = DB::table('pim_problems')
                ->where(function($query) {
                    $query->whereNotNull('problem_tex')
                          ->orWhereNotNull('solution_tex');
                })
                ->count();

            \Log::info('Total problemas a escanear: ' . $totalProblemas);

            $problemasConErrores = [];
            $ejemplos = [];
            $batchSize = 50; // Procesar de 50 en 50
            $offset = 0;

            while ($offset < $totalProblemas) {
                // Obtener lote de problemas
                $problemas = DB::table('pim_problems')
                    ->where(function($query) {
                        $query->whereNotNull('problem_tex')
                              ->orWhereNotNull('solution_tex');
                    })
                    ->skip($offset)
                    ->take($batchSize)
                    ->get();

                \Log::info('Procesando lote ' . ($offset / $batchSize + 1) . ' (' . $offset . ' - ' . ($offset + $batchSize) . ')');

                // Procesar cada problema del lote
                foreach ($problemas as $problema) {
                try {
                    $cambios = [];

                    // Revisar problem_tex
                    if (!empty($problema->problem_tex) && is_string($problema->problem_tex)) {
                        $original = $problema->problem_tex;
                        $corregido = $this->fixBackslashesSimple($original);

                        if ($original !== $corregido) {
                            $cambios['problem_tex'] = [
                                'original' => $this->truncateText($original, 300),
                                'corregido' => $this->truncateText($corregido, 300),
                            ];
                        }
                    }

                    // Revisar solution_tex
                    if (!empty($problema->solution_tex) && is_string($problema->solution_tex)) {
                        $original = $problema->solution_tex;
                        $corregido = $this->fixBackslashesSimple($original);

                        if ($original !== $corregido) {
                            $cambios['solution_tex'] = [
                                'original' => $this->truncateText($original, 300),
                                'corregido' => $this->truncateText($corregido, 300),
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
                } catch (\Exception $e) {
                    \Log::error('Error procesando problema ID ' . $problema->id . ': ' . $e->getMessage());
                    // Continuar con el siguiente problema
                }
                }

                $offset += $batchSize;

                // Liberar memoria
                unset($problemas);
                gc_collect_cycles();
            }

            \Log::info('Problemas con errores: ' . count($problemasConErrores));

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
        if (empty($text) || !is_string($text)) {
            return $text;
        }

        // Procesar solo los comandos más comunes para evitar regex complejo
        $comandos = ['frac', 'sqrt', 'sum', 'int', 'lim', 'sin', 'cos', 'tan', 'log', 'ln',
                     'alpha', 'beta', 'gamma', 'delta', 'epsilon', 'infty', 'leq', 'geq', 'cdot',
                     'text', 'mathbb', 'mathbf', 'left', 'right', 'begin', 'end', 'times', 'neq'];

        foreach ($comandos as $cmd) {
            try {
                // Estrategia simple: buscar literalmente "comando{" o "comando (" SIN barra antes
                // y reemplazar por "\comando{" o "\comando ("

                // Buscar comando seguido de { (sin barra antes)
                $text = $this->replaceIfNotEscaped($text, $cmd . '{', '\\' . $cmd . '{');

                // Buscar comando seguido de ( (sin barra antes)
                $text = $this->replaceIfNotEscaped($text, $cmd . '(', '\\' . $cmd . '(');

                // Buscar comando con espacio antes de { (sin barra antes)
                $text = $this->replaceIfNotEscaped($text, $cmd . ' {', '\\' . $cmd . ' {');

                // Buscar comando con espacio antes de ( (sin barra antes)
                $text = $this->replaceIfNotEscaped($text, $cmd . ' (', '\\' . $cmd . ' (');

            } catch (\Exception $e) {
                \Log::warning('Error procesando comando ' . $cmd . ': ' . $e->getMessage());
                // Continuar con el siguiente comando
            }
        }

        return $text;
    }

    /**
     * Reemplaza una cadena solo si NO está precedida por \
     */
    private function replaceIfNotEscaped($text, $search, $replace)
    {
        if (empty($text) || !is_string($text)) {
            return $text;
        }

        $result = '';
        $textLen = strlen($text);
        $searchLen = strlen($search);

        $i = 0;
        while ($i < $textLen) {
            // Comprobar si encontramos la cadena de búsqueda en esta posición
            if (substr($text, $i, $searchLen) === $search) {
                // Comprobar si está escapada (tiene \ antes)
                $isEscaped = ($i > 0 && $text[$i - 1] === '\\');

                // Si no está al inicio y el carácter anterior no es espacio/inicio, verificar que sea límite de palabra
                if ($i > 0) {
                    $prevChar = $text[$i - 1];
                    // Solo reemplazar si está al inicio o después de espacio, salto de línea, o caracteres especiales
                    $isWordBoundary = in_array($prevChar, [' ', "\n", "\r", "\t", '{', '}', '(', ')', '[', ']', '$', '^', '_']);

                    if (!$isWordBoundary || $isEscaped) {
                        $result .= $text[$i];
                        $i++;
                        continue;
                    }
                }

                if (!$isEscaped) {
                    // No está escapado, reemplazar
                    $result .= $replace;
                    $i += $searchLen;
                } else {
                    // Está escapado, mantener como está
                    $result .= $text[$i];
                    $i++;
                }
            } else {
                $result .= $text[$i];
                $i++;
            }
        }

        return $result;
    }

    /**
     * Trunca texto de forma segura
     */
    private function truncateText($text, $length)
    {
        if (empty($text) || !is_string($text)) {
            return '';
        }

        if (strlen($text) <= $length) {
            return $text;
        }

        // Usar mb_substr si está disponible, sino substr
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $length);
        }

        return substr($text, 0, $length);
    }
}
