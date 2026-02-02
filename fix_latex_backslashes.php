<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Script de reparación de barras invertidas en LaTeX ===\n\n";

// Comandos LaTeX comunes que podrían haber perdido la barra
$latexCommands = [
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

// Crear patrón de búsqueda que NO capture si ya tiene barra invertida
$pattern = '(?<!\\\\)\\b(' . implode('|', array_map('preg_quote', $latexCommands)) . ')\\s*([{[(])';

echo "Buscando comandos LaTeX sin barra invertida en la base de datos...\n\n";

// Buscar problemas con comandos LaTeX sin barra
$problemas = DB::table('pim_problems')
    ->whereNotNull('problem_tex')
    ->orWhereNotNull('solution_tex')
    ->get();

$problemasConErrores = [];
$totalCambios = 0;

foreach ($problemas as $problema) {
    $cambios = [];

    // Revisar problem_tex
    if ($problema->problem_tex) {
        $original = $problema->problem_tex;
        $corregido = preg_replace_callback(
            '/' . $pattern . '/u',
            function($matches) {
                return '\\' . $matches[0];
            },
            $original
        );

        if ($original !== $corregido) {
            $cambios['problem_tex'] = [
                'original' => $original,
                'corregido' => $corregido,
            ];
        }
    }

    // Revisar solution_tex
    if ($problema->solution_tex) {
        $original = $problema->solution_tex;
        $corregido = preg_replace_callback(
            '/' . $pattern . '/u',
            function($matches) {
                return '\\' . $matches[0];
            },
            $original
        );

        if ($original !== $corregido) {
            $cambios['solution_tex'] = [
                'original' => $original,
                'corregido' => $corregido,
            ];
        }
    }

    if (!empty($cambios)) {
        $problemasConErrores[$problema->id] = $cambios;
        $totalCambios += count($cambios);
    }
}

echo "Problemas encontrados: " . count($problemasConErrores) . "\n";
echo "Total de campos a corregir: " . $totalCambios . "\n\n";

if (empty($problemasConErrores)) {
    echo "No se encontraron problemas que corregir.\n";
    exit(0);
}

// Mostrar algunos ejemplos
echo "=== EJEMPLOS DE CAMBIOS ===\n\n";
$ejemplosMostrados = 0;
foreach ($problemasConErrores as $id => $cambios) {
    if ($ejemplosMostrados >= 5) break;

    echo "Problema ID: $id\n";

    foreach ($cambios as $campo => $datos) {
        echo "  Campo: $campo\n";

        // Mostrar solo los primeros 200 caracteres para hacerlo legible
        $originalCorto = substr($datos['original'], 0, 200);
        $corregidoCorto = substr($datos['corregido'], 0, 200);

        echo "  Original:  " . $originalCorto . (strlen($datos['original']) > 200 ? '...' : '') . "\n";
        echo "  Corregido: " . $corregidoCorto . (strlen($datos['corregido']) > 200 ? '...' : '') . "\n";
        echo "\n";
    }

    $ejemplosMostrados++;
}

if (count($problemasConErrores) > 5) {
    echo "... y " . (count($problemasConErrores) - 5) . " problemas más.\n\n";
}

// Pedir confirmación
echo "¿Deseas aplicar estos cambios? (escribe 'SI' para confirmar): ";
$confirmacion = trim(fgets(STDIN));

if (strtoupper($confirmacion) !== 'SI') {
    echo "Operación cancelada. No se realizaron cambios.\n";
    exit(0);
}

echo "\nAplicando cambios...\n";

$procesados = 0;
$errores = 0;

foreach ($problemasConErrores as $id => $cambios) {
    try {
        $updates = [];

        if (isset($cambios['problem_tex'])) {
            $updates['problem_tex'] = $cambios['problem_tex']['corregido'];
        }

        if (isset($cambios['solution_tex'])) {
            $updates['solution_tex'] = $cambios['solution_tex']['corregido'];
        }

        DB::table('pim_problems')
            ->where('id', $id)
            ->update($updates);

        $procesados++;

        if ($procesados % 10 == 0) {
            echo "Procesados: $procesados / " . count($problemasConErrores) . "\n";
        }

    } catch (Exception $e) {
        echo "Error al procesar problema ID $id: " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Total de problemas procesados: $procesados\n";
echo "Errores: $errores\n";
echo "Campos actualizados: $totalCambios\n";
echo "\nProceso completado.\n";
