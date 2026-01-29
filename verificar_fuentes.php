<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener todas las fuentes con "Círculo"
echo "=== FUENTES CON 'Círculo' ===\n";
$fuentes = DB::table('pim_problems')
    ->where('source', 'like', '%Círculo%')
    ->orWhere('source', 'like', '%Circulo%')
    ->distinct()
    ->pluck('source');

foreach ($fuentes as $fuente) {
    echo "- {$fuente}\n";
}

echo "\n=== VERIFICAR PATRONES ===\n";
$patrones = ['C.rculo.*Matem', 'Math.*Circle', 'C.rculo.*Matem.*Ruso'];

foreach ($fuentes as $fuente) {
    echo "\nFuente: '{$fuente}'\n";
    foreach ($patrones as $patron) {
        $match = preg_match('/' . $patron . '/i', $fuente);
        echo "  Patrón '{$patron}': " . ($match ? 'COINCIDE' : 'NO coincide') . "\n";
    }
}
