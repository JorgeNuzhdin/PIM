<?php
// Script temporal: listar todos los \usepackage usados en las hojas
// Ejecutar en el servidor: php list_packages.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$packages = [];
\App\Models\PimSheet::whereNotNull('tex_sols')->pluck('tex_sols')->each(function ($tex) use (&$packages) {
    preg_match_all('/\\\\usepackage(?:\[[^\]]*\])?\{([^}]+)\}/', $tex, $m);
    foreach ($m[1] as $p) {
        foreach (explode(',', $p) as $pkg) {
            $pkg = trim($pkg);
            if ($pkg) $packages[$pkg] = true;
        }
    }
});

ksort($packages);
echo implode("\n", array_keys($packages)) . "\n";
