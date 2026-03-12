<?php



use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\HojaController;
use App\Http\Controllers\PimSheetController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\MetodoController;


Route::get('/', [HomePageController::class, 'index'])->name('homepage');
Auth::routes();


Route::middleware(['auth'])->prefix('hojas')->name('hojas.')->group(function () {
    Route::get('/', [HojaController::class, 'index'])->name('index');
    Route::post('/', [HojaController::class, 'store'])->name('store');
    Route::get('/{hoja}/load', [HojaController::class, 'load'])->name('load');
    Route::delete('/{hoja}', [HojaController::class, 'destroy'])->name('destroy');
});
Route::middleware('auth')->group(function () {
    Route::get('/perfil/editar', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/problemas', [App\Http\Controllers\ProblemaController::class, 'index'])->name('problemas.index');

    // IMPORTANT: con-errores must be before /{id} to avoid being matched as an ID
    Route::get('/problemas/con-errores', [App\Http\Controllers\ErrorReportController::class, 'index'])
         ->name('problemas.con-errores')
         ->middleware('can.edit.problemas');
    Route::post('/problemas/{id}/reportar-error', [App\Http\Controllers\ErrorReportController::class, 'store'])
         ->name('problemas.reportar-error');

    Route::get('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'show'])->name('problemas.show')->where('id', '[0-9]+');
    Route::get('/api/topics/buscar', [App\Http\Controllers\ProblemaController::class, 'buscarTopics'])->name('topics.buscar');

   Route::get('/api/tema-desde-tag', [App\Http\Controllers\ProblemaController::class, 'temaDesdeTag'])->name('tema.desde.tag');
     Route::post('/latex/preview', [App\Http\Controllers\ProblemaController::class, 'latexPreview'])->name('latex.preview');
    Route::get('/carrito', [App\Http\Controllers\CarritoController::class, 'index'])->name('carrito.index');
    Route::post('/carrito/toggle', [App\Http\Controllers\CarritoController::class, 'toggle'])->name('carrito.toggle');
    Route::post('/carrito/add-bulk', [App\Http\Controllers\CarritoController::class, 'addBulk'])->name('carrito.addBulk');
    Route::post('/carrito/update-order', [App\Http\Controllers\CarritoController::class, 'updateOrder'])->name('carrito.updateOrder');
    Route::get('/carrito/count', [App\Http\Controllers\CarritoController::class, 'count'])->name('carrito.count');
    Route::get('/carrito/descargar-tex', [App\Http\Controllers\CarritoController::class, 'descargarTex'])->name('carrito.descargar.tex');
    Route::get('/carrito/descargar-pdf', [App\Http\Controllers\CarritoController::class, 'descargarPdf'])->name('carrito.descargar.pdf');
    Route::get('/carrito/presentacion', [App\Http\Controllers\CarritoController::class, 'presentacion'])->name('carrito.presentacion');
    Route::post('/carrito/limpiar', [App\Http\Controllers\CarritoController::class, 'limpiar'])->name('carrito.limpiar');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // API subtemas (para dropdown dinámico)
    Route::get('/api/subtemas/{tema_id}', [MetodoController::class, 'apiSubtemas'])->name('api.subtemas');
    Route::post('/api/subtemas', [MetodoController::class, 'apiStoreSubtema'])->name('api.subtemas.store');

    // API comprobación de duplicados
    Route::post('/api/check-duplicates/problemas', [App\Http\Controllers\ProblemaController::class, 'checkDuplicates'])->name('api.check-duplicates.problemas');
    Route::post('/api/check-duplicates/metodos', [App\Http\Controllers\MetodoController::class, 'checkDuplicates'])->name('api.check-duplicates.metodos');
    Route::post('/api/check-duplicates/hojas', [App\Http\Controllers\PimSheetController::class, 'checkDuplicates'])->name('api.check-duplicates.hojas');

    // Crear problema: cualquier usuario autenticado
    Route::get('/problemas/crear', [App\Http\Controllers\ProblemaController::class, 'create'])->name('problemas.create');
    Route::post('/problemas', [App\Http\Controllers\ProblemaController::class, 'store'])->name('problemas.store');

    // Editar/borrar: solo admin/editor
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/problemas/{id}/editar', [App\Http\Controllers\ProblemaController::class, 'edit'])->name('problemas.edit');
        Route::put('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'update'])->name('problemas.update');
        Route::delete('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'destroy'])->name('problemas.destroy');
    });

    // Métodos: listado y detalle visible para todos los autenticados
    Route::get('/metodos', [MetodoController::class, 'index'])->name('metodos.index');
    Route::get('/metodos/{id}', [MetodoController::class, 'show'])->name('metodos.show')->where('id', '[0-9]+');
    Route::get('/metodos/{id}/descargar-tex', [MetodoController::class, 'downloadTex'])->name('metodos.download-tex')->where('id', '[0-9]+');

    // Crear/editar/guardar métodos: solo para admin/editor
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/metodos/crear', [MetodoController::class, 'create'])->name('metodos.create');
        Route::post('/metodos', [MetodoController::class, 'store'])->name('metodos.store');
        Route::get('/metodos/{id}/editar', [MetodoController::class, 'edit'])->name('metodos.edit')->where('id', '[0-9]+');
        Route::put('/metodos/{id}', [MetodoController::class, 'update'])->name('metodos.update')->where('id', '[0-9]+');
        Route::delete('/metodos/{id}', [MetodoController::class, 'destroy'])->name('metodos.destroy')->where('id', '[0-9]+');
    });

});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/rol', [AdminUserController::class, 'updateRol'])->name('users.updateRol');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Reparar LaTeX
    Route::get('/fix-latex', [App\Http\Controllers\FixLatexController::class, 'index'])->name('fix-latex');
    Route::post('/fix-latex/scan', [App\Http\Controllers\FixLatexController::class, 'scan'])->name('fix-latex.scan');
    Route::post('/fix-latex/fix', [App\Http\Controllers\FixLatexController::class, 'fix'])->name('fix-latex.fix');

    // Configuración general
    Route::get('/settings', [App\Http\Controllers\AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [App\Http\Controllers\AdminSettingsController::class, 'update'])->name('settings.update');

    // Problemas pendientes de aprobación
    Route::get('/problemas-pendientes', [App\Http\Controllers\ProblemaController::class, 'pendientes'])->name('problemas.pendientes');
    Route::post('/problemas/{id}/aprobar', [App\Http\Controllers\ProblemaController::class, 'aprobar'])->name('problemas.aprobar');

    // Migración: añadir columna solved a error_reports
    Route::get('/run-migration/add-solved-to-error-reports', function () {
        try {
            if (!Schema::hasColumn('error_reports', 'solved')) {
                Schema::table('error_reports', function ($table) {
                    $table->boolean('solved')->default(false)->after('tipo');
                });
                return '✅ Columna solved añadida a error_reports.';
            }
            return 'ℹ️ La columna solved ya existe.';
        } catch (\Exception $e) {
            return '❌ Error: ' . $e->getMessage();
        }
    })->name('admin.run-migration.solved-error-reports');

    // Limpiar caché de vistas compiladas
    Route::get('/clear-views', function () {
        $path = storage_path('framework/views');
        $count = 0;
        foreach (glob($path . '/*.php') as $file) {
            @unlink($file);
            $count++;
        }
        return "✅ {$count} vistas compiladas eliminadas. Las vistas se recompilarán al acceder.";
    })->name('admin.clear-views');

    // Rellenar tema_id en pim_problems a partir de tags → topic_tema
    Route::get('/run-migration/populate-temas-debug', function () {
        // Muestra los tags distintos de problemas sin tema_id
        $tags = \Illuminate\Support\Facades\DB::table('problemas_tags')
            ->join('pim_problems', 'problemas_tags.problem_id', '=', 'pim_problems.id')
            ->whereNull('pim_problems.tema_id')
            ->select('problemas_tags.tag')
            ->distinct()
            ->orderBy('problemas_tags.tag')
            ->pluck('tag');
        $out = "Tags distintos en problemas sin tema_id (" . $tags->count() . "):\n\n";
        foreach ($tags as $t) {
            $out .= json_encode($t) . "\n"; // json_encode muestra escapes unicode si los hay
        }
        return '<pre>' . htmlspecialchars($out) . '</pre>';
    })->name('admin.run-migration.populate-temas-debug');

    Route::get('/run-migration/populate-temas', function () {
        // Reglas: mb_strtolower(tag) → mb_strtolower(nombre_tema)
        // Las claves y valores están todos en minúsculas para matching insensible a mayúsculas
        $tagRules = [
            // ── Geometría ──────────────────────────────────────────────────
            'geometría'                          => 'geometría',
            'geometría analítica'                => 'geometría',
            'geometría discreta'                 => 'geometría',
            'geometría. paridad.'                => 'geometría',
            'ángulo central'                     => 'geometría',
            'ángulos'                            => 'geometría',
            'ángulos en una circunferencia'      => 'geometría',
            'ángulos exteriores'                 => 'geometría',
            'ángulos inscritos'                  => 'geometría',
            'área del círculo'                   => 'geometría',
            'área del triángulo'                 => 'geometría',
            'áreas'                              => 'geometría',
            'árbol de steiner'                   => 'geometría',
            'bisectriz'                          => 'geometría',
            'círculo de los nueve puntos'        => 'geometría',
            'círculos'                           => 'geometría',
            'circuncentro'                       => 'geometría',
            'circunferencia inscrita'            => 'geometría',
            'circunferencias'                    => 'geometría',
            'congruencias'                       => 'geometría',
            'construcción'                       => 'geometría',
            'construcción complementaria'        => 'geometría',
            'construcción con regla y compás'    => 'geometría',
            'construcciones'                     => 'geometría',
            'coordenadas'                        => 'geometría',
            'cuadrados'                          => 'geometría',
            'cuadrilátero cíclico'               => 'geometría',
            'cuadriláteros'                      => 'geometría',
            'cubo'                               => 'geometría',
            'diagonales'                         => 'geometría',
            'distancias'                         => 'geometría',
            'división de figuras'                => 'geometría',
            'esfera'                             => 'geometría',
            'estereometría'                      => 'geometría',
            'figuras convexas'                   => 'geometría',
            'fórmula de euler generalizada'      => 'geometría',
            'fórmula de herón'                   => 'geometría',
            'homotecia'                          => 'geometría',
            'incentro'                           => 'geometría',
            'inversión'                          => 'geometría',
            'isometrías'                         => 'geometría',
            'línea media'                        => 'geometría',
            'longitud'                           => 'geometría',
            'lugar geométrico'                   => 'geometría',
            'medianas'                           => 'geometría',
            'mediatriz'                          => 'geometría',
            'ortocentro'                         => 'geometría',
            'ortopolo'                           => 'geometría',
            'parábola'                           => 'geometría',
            'paralelogramos'                     => 'geometría',
            'pentágonos'                         => 'geometría',
            'perímetro'                          => 'geometría',
            'pitágoras'                          => 'geometría',
            'planimetría'                        => 'geometría',
            'poliedros'                          => 'geometría',
            'polígonos'                          => 'geometría',
            'potencia de punto'                  => 'geometría',
            'producto escalar'                   => 'geometría',
            'producto vectorial'                 => 'geometría',
            'proyección ortogonal'               => 'geometría',
            'puntos notables'                    => 'geometría',
            'razón áurea'                        => 'geometría',
            'recta de euler'                     => 'geometría',
            'recta de simpson'                   => 'geometría',
            'rectas'                             => 'geometría',
            'semejanza'                          => 'geometría',
            'semicírculo'                        => 'geometría',
            'simetría'                           => 'geometría',
            'tales'                              => 'geometría',
            'tangencia'                          => 'geometría',
            'tangentes'                          => 'geometría',
            'teorema de ceva'                    => 'geometría',
            'teorema de la bisectriz'            => 'geometría',
            'teorema de pitágoras'               => 'geometría',
            'teorema de tales'                   => 'geometría',
            'teorema del seno'                   => 'geometría',
            'ternas pitagóricas'                 => 'geometría',
            'teselaciones'                       => 'geometría',
            'tetraedro'                          => 'geometría',
            'transformaciones'                   => 'geometría',
            'transformaciones del plano'         => 'geometría',
            'trapecios'                          => 'geometría',
            'triangulaciones'                    => 'geometría',
            'triángulo inscrito'                 => 'geometría',
            'triángulos'                         => 'geometría',
            'triángulos equiláteros'             => 'geometría',
            'triángulos isósceles'               => 'geometría',
            'triángulos semejantes'              => 'geometría',
            'triángulos similares'               => 'geometría',
            'trigonometría'                      => 'geometría',
            'vectores'                           => 'geometría',
            'visión espacial'                    => 'geometría',
            // ── Aritmética ─────────────────────────────────────────────────
            'aritmética'                         => 'aritmética',
            'aritmética modular'                 => 'aritmética',
            'algoritmo de euclides'              => 'aritmética',
            'algoritmo de la división'           => 'aritmética',
            'bases de numeración'                => 'aritmética',
            'criterios de divisibilidad'         => 'aritmética',
            'descenso infinito'                  => 'aritmética',
            'descomposición en factores'         => 'aritmética',
            'dígitos'                            => 'aritmética',
            'divisibilidad'                      => 'aritmética',
            'divisibilidad por 11'               => 'aritmética',
            'división'                           => 'aritmética',
            'divisores'                          => 'aritmética',
            'factoriales'                        => 'aritmética',
            'factorización'                      => 'aritmética',
            'función de euler'                   => 'aritmética',
            'función sigma'                      => 'aritmética',
            'gran teorema de fermat'             => 'aritmética',
            'identidad de bézout'                => 'aritmética',
            'irracionalidad'                     => 'aritmética',
            'máximo común divisor'               => 'aritmética',
            'máximo divisor común'               => 'aritmética',
            'múltiplos'                          => 'aritmética',
            'números enteros'                    => 'aritmética',
            'números irracionales'               => 'aritmética',
            'números naturales'                  => 'aritmética',
            'números primos'                     => 'aritmética',
            'parte entera'                       => 'aritmética',
            'pequeño teorema de fermat'          => 'aritmética',
            'potencias'                          => 'aritmética',
            'potencias de 2'                     => 'aritmética',
            'primos'                             => 'aritmética',
            'producto de euler'                  => 'aritmética',
            'raíces cuadradas'                   => 'aritmética',
            'raíces de la unidad'                => 'aritmética',
            'repunits'                           => 'aritmética',
            'restos'                             => 'aritmética',
            'sistema binario'                    => 'aritmética',
            'sistema decimal'                    => 'aritmética',
            'sistema posicional'                 => 'aritmética',
            'sistema ternario'                   => 'aritmética',
            'sistemas de numeración'             => 'aritmética',
            'suma de las cifras'                 => 'aritmética',
            'teorema chino del resto'            => 'aritmética',
            'teorema de bezout'                  => 'aritmética',
            'teorema de fermat'                  => 'aritmética',
            'teorema fundamental de la aritmética' => 'aritmética',
            'teoría de números'                  => 'aritmética',
            'última cifra'                       => 'aritmética',
            // ── Combinatoria ───────────────────────────────────────────────
            'combinatoria'                       => 'combinatoria',
            'combinatoria geométrica'            => 'combinatoria',
            'árboles'                            => 'combinatoria',
            'árboles generadores'                => 'combinatoria',
            'asignaciones'                       => 'combinatoria',
            'binomio de newton'                  => 'combinatoria',
            'biyecciones'                        => 'combinatoria',
            'cadenas de markov'                  => 'combinatoria',
            'caminos'                            => 'combinatoria',
            'caminos de dyck'                    => 'combinatoria',
            'cartas'                             => 'combinatoria',
            'característica de euler'            => 'combinatoria',
            'centro del grafo'                   => 'combinatoria',
            'cerillas'                           => 'combinatoria',
            'ciclo hamiltoniano'                 => 'combinatoria',
            'ciclos'                             => 'combinatoria',
            'codificación'                       => 'combinatoria',
            'códigos'                            => 'combinatoria',
            'coloración'                         => 'combinatoria',
            'coloración por aristas'             => 'combinatoria',
            'coloraciones'                       => 'combinatoria',
            'combinaciones'                      => 'combinatoria',
            'conectividad'                       => 'combinatoria',
            'conexidad'                          => 'combinatoria',
            'conjuntos'                          => 'combinatoria',
            'conteo doble'                       => 'combinatoria',
            'cortes'                             => 'combinatoria',
            'cuadrículas'                        => 'combinatoria',
            'dados'                              => 'combinatoria',
            'demostración probabilística'        => 'combinatoria',
            'distribución binomial'              => 'combinatoria',
            'distribución de poisson'            => 'combinatoria',
            'doble conteo'                       => 'combinatoria',
            'emparejamientos perfectos'          => 'combinatoria',
            'esperanza'                          => 'combinatoria',
            'estadística'                        => 'combinatoria',
            'etiquetado'                         => 'combinatoria',
            'exclusión'                          => 'combinatoria',
            'funciones generatrices'             => 'combinatoria',
            'grafo bipartito regular'            => 'combinatoria',
            'grafo de petersen'                  => 'combinatoria',
            'grafo dual'                         => 'combinatoria',
            'grafos'                             => 'combinatoria',
            'grafos bipartitos'                  => 'combinatoria',
            'grafos completos'                   => 'combinatoria',
            'grafos dirigidos'                   => 'combinatoria',
            'grafos eulerianos'                  => 'combinatoria',
            'grafos hamiltonianos'               => 'combinatoria',
            'grafos planares'                    => 'combinatoria',
            'grafos regulares'                   => 'combinatoria',
            'hamiltonicidad'                     => 'combinatoria',
            'handshaking lemma'                  => 'combinatoria',
            'isomorfismo'                        => 'combinatoria',
            'juegos'                             => 'combinatoria',
            'juegos de estrategia'               => 'combinatoria',
            'manhattan'                          => 'combinatoria',
            'método probabilístico'              => 'combinatoria',
            'nim'                                => 'combinatoria',
            'nim-sumas'                          => 'combinatoria',
            'número cromático'                   => 'combinatoria',
            'números combinatorios'              => 'combinatoria',
            'números de catalán'                 => 'combinatoria',
            'números de fibonacci'               => 'combinatoria',
            'números de lucas'                   => 'combinatoria',
            'números de ramsey'                  => 'combinatoria',
            'números de stirling'                => 'combinatoria',
            'números triangulares'               => 'combinatoria',
            'palabras'                           => 'combinatoria',
            'palomar'                            => 'combinatoria',
            'particiones'                        => 'combinatoria',
            'paseo aleatorio'                    => 'combinatoria',
            'permutaciones'                      => 'combinatoria',
            'plano de fano'                      => 'combinatoria',
            'poliminos'                          => 'combinatoria',
            'principio de exclusión-inclusión'   => 'combinatoria',
            'principio de inclusión-exclusión'   => 'combinatoria',
            'principio del palomar'              => 'combinatoria',
            'probabilidad'                       => 'combinatoria',
            'probabilidad condicional'           => 'combinatoria',
            'probabilidad geométrica'            => 'combinatoria',
            'random walk'                        => 'combinatoria',
            'recorrido euleriano'                => 'combinatoria',
            'recorrido hamiltoniano'             => 'combinatoria',
            'recurrencias'                       => 'combinatoria',
            'rompecabezas'                       => 'combinatoria',
            'subconjuntos'                       => 'combinatoria',
            'sucesión de fibonacci'              => 'combinatoria',
            'tableros'                           => 'combinatoria',
            'tablas'                             => 'combinatoria',
            'teorema de hall'                    => 'combinatoria',
            'teorema de mantel'                  => 'combinatoria',
            'teorema de turán'                   => 'combinatoria',
            'teoría de juegos'                   => 'combinatoria',
            'torneos'                            => 'combinatoria',
            'triángulo de pascal'                => 'combinatoria',
            'varianza'                           => 'combinatoria',
            'votaciones'                         => 'combinatoria',
            // ── Métodos ────────────────────────────────────────────────────
            'invariantes'                        => 'métodos',
            'inducción'                          => 'métodos',
            'semiinvariantes'                    => 'métodos',
            'reducción al absurdo'               => 'métodos',
            'reductio ad absurdum'               => 'métodos',
            'construcción de ejemplos'           => 'métodos',
            'contraejemplos'                     => 'métodos',
            'método de pequeñas variaciones'     => 'métodos',
            'principio del extremo'              => 'métodos',
            'principio de la palanca'            => 'métodos',
            'cambio de lenguaje'                 => 'métodos',
            'cambio de variable'                 => 'métodos',
            'nuevas variables'                   => 'métodos',
            'análisis de casos'                  => 'métodos',
            'estudio de casos'                   => 'métodos',
            'estrategia'                         => 'métodos',
            'estrategias'                        => 'métodos',
            'razonamientos largos'               => 'métodos',
            'pensamiento lateral'                => 'métodos',
            'iteraciones'                        => 'métodos',
            'demostraciones'                     => 'métodos',
            'algoritmo voraz'                    => 'métodos',
        ];

        // Temas: nombre_lowercase → id  (insensible a cómo estén guardados en BD)
        $temasRaw     = \Illuminate\Support\Facades\DB::table('temas')->select('id', 'tema')->get();
        $temaIdByLc   = $temasRaw->mapWithKeys(fn($t) => [mb_strtolower($t->tema) => $t->id]);
        $temaNameById = $temasRaw->pluck('tema', 'id');

        $problems = \Illuminate\Support\Facades\DB::table('pim_problems')
            ->whereNull('tema_id')
            ->select('id')
            ->get();

        $tagsByProblem = \Illuminate\Support\Facades\DB::table('problemas_tags')
            ->select('problem_id', 'tag')
            ->get()
            ->groupBy('problem_id');

        $set = 0; $skipped = 0; $conflicts = [];
        $log = '';

        foreach ($problems as $problem) {
            $tags     = $tagsByProblem->get($problem->id, collect());
            $tagNames = $tags->pluck('tag');

            // mb_strtolower para matching insensible a mayúsculas/acentos de capitalización
            $resolvedLc = $tagNames
                ->map(fn($tag) => $tagRules[mb_strtolower($tag)] ?? null)
                ->filter()->unique()->values();

            if ($resolvedLc->isEmpty()) { $skipped++; continue; }

            $temaIds = $resolvedLc->map(fn($lc) => $temaIdByLc[$lc] ?? null)->filter()->unique()->values();

            if ($temaIds->isEmpty()) { $skipped++; continue; }

            // Si hay varios temas, prioridad fija sin preguntar: Geometría > Aritmética > Combinatoria > Métodos
            $priority = ['geometría', 'aritmética', 'combinatoria', 'métodos'];
            $winner   = null;
            foreach ($priority as $temaLc) {
                foreach ($temaIds as $tid) {
                    if (mb_strtolower($temaNameById[$tid] ?? '') === $temaLc) {
                        $winner = $tid;
                        break 2;
                    }
                }
            }
            if (!$winner) { $skipped++; continue; }

            \Illuminate\Support\Facades\DB::table('pim_problems')
                ->where('id', $problem->id)
                ->update(['tema_id' => $winner]);
            $marker = $temaIds->count() > 1 ? '⚡' : '✅';
            $log .= "{$marker} #{$problem->id} → " . ($temaNameById[$winner] ?? $winner) . "\n";
            $set++;
        }

        $log .= "\n{$set} asignados, {$skipped} saltados (sin tag reconocido).\n";
        return '<pre>' . htmlspecialchars($log) . '</pre>';

    })->name('admin.run-migration.populate-temas');

    // Migración manual: añadir tema_id a pim_problems (ejecutar una sola vez desde el navegador)
    Route::get('/run-migration/tema-id', function () {
        if (\Illuminate\Support\Facades\Schema::hasColumn('pim_problems', 'tema_id')) {
            return '<pre>✅ La columna tema_id ya existe en pim_problems. No es necesario hacer nada.</pre>';
        }
        $log = '';
        // 1. Averiguar el tipo real de temas.id para usarlo en la FK
        try {
            $col = \Illuminate\Support\Facades\DB::select("
                SELECT DATA_TYPE, COLUMN_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'temas'
                  AND COLUMN_NAME  = 'id'
                LIMIT 1
            ");
            $dataType   = strtolower($col[0]->DATA_TYPE   ?? 'bigint');
            $columnType = strtolower($col[0]->COLUMN_TYPE ?? 'bigint unsigned');
            $unsigned   = str_contains($columnType, 'unsigned');
            $log .= "temas.id → DATA_TYPE={$dataType}, COLUMN_TYPE={$columnType}\n";
        } catch (\Exception $e) {
            $dataType = 'bigint'; $unsigned = true;
            $log .= "No se pudo leer temas.id, usando bigint unsigned por defecto\n";
        }

        // 2. Añadir columna (sin FK primero para que no falle el ALTER)
        try {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE pim_problems ADD COLUMN tema_id " .
                strtoupper($dataType) . ($unsigned ? " UNSIGNED" : "") .
                " NULL AFTER source"
            );
            $log .= "✅ Columna tema_id añadida.\n";
        } catch (\Exception $e) {
            return '<pre>❌ Error al añadir columna: ' . htmlspecialchars($e->getMessage()) . "\n$log</pre>";
        }

        // 3. Intentar añadir FK (puede fallar si hay datos huérfanos u otro problema)
        try {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE pim_problems
                 ADD CONSTRAINT pim_problems_tema_id_foreign
                 FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE SET NULL"
            );
            $log .= "✅ FK añadida correctamente.\n";
        } catch (\Exception $e) {
            $log .= "⚠️ FK NO añadida (la columna funciona igual sin ella): " . $e->getMessage() . "\n";
        }

        return '<pre>' . htmlspecialchars($log) . '</pre>';
    })->name('admin.run-migration.tema-id');
});

// Rutas de Hojas de Problemas (PimSheets)
Route::middleware('auth')->prefix('pim-sheets')->name('pim-sheets.')->group(function () {
    Route::get('/', [PimSheetController::class, 'index'])->name('index');

    // Solo editores y administradores pueden subir sheets
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/create', [PimSheetController::class, 'create'])->name('create');
        Route::post('/', [PimSheetController::class, 'store'])->name('store');
    });

    // Solo administradores pueden editar/eliminar sheets
    Route::middleware('admin')->group(function () {
        Route::get('/{id}/edit', [PimSheetController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PimSheetController::class, 'update'])->name('update');
        Route::delete('/{id}', [PimSheetController::class, 'destroy'])->name('destroy');
    });

    // Ver hoja (debe ir después de /create para evitar conflictos)
    Route::get('/{id}', [PimSheetController::class, 'show'])->name('show');

    // Descarga de hojas
    Route::get('/{id}/download', [PimSheetController::class, 'download'])->name('download');
    Route::get('/{id}/download-pdf', [PimSheetController::class, 'downloadPdf'])->name('download-pdf');
});

// Rutas de Editor de Tags (solo admin/editor pueden ver, solo admin puede editar/borrar)
Route::middleware(['auth', 'can.edit.problemas'])->prefix('tags')->name('tags.')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('index');
    Route::put('/{title}', [TagController::class, 'update'])->name('update')->where('title', '.*');
    Route::delete('/{title}', [TagController::class, 'destroy'])->name('destroy')->where('title', '.*');
});
