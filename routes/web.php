<?php



use Illuminate\Support\Facades\Route;
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
    Route::get('/problemas', [App\Http\Controllers\ProblemaController::class, 'index'])->name('problemas.index');
    Route::get('/problemas', [App\Http\Controllers\ProblemaController::class, 'index'])->name('problemas.index');
    Route::get('/api/topics/buscar', [App\Http\Controllers\ProblemaController::class, 'buscarTopics'])->name('topics.buscar');

   Route::get('/api/tema-desde-tag', [App\Http\Controllers\ProblemaController::class, 'temaDesdeTag'])->name('tema.desde.tag');
     Route::post('/latex/preview', [App\Http\Controllers\ProblemaController::class, 'latexPreview'])->name('latex.preview');
    Route::get('/carrito', [App\Http\Controllers\CarritoController::class, 'index'])->name('carrito.index');
    Route::post('/carrito/toggle', [App\Http\Controllers\CarritoController::class, 'toggle'])->name('carrito.toggle');
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

    // Rutas de problemas (solo para admin/editor)
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/problemas/crear', [App\Http\Controllers\ProblemaController::class, 'create'])->name('problemas.create');
        Route::post('/problemas', [App\Http\Controllers\ProblemaController::class, 'store'])->name('problemas.store');
        Route::get('/problemas/{id}/editar', [App\Http\Controllers\ProblemaController::class, 'edit'])->name('problemas.edit');
        Route::put('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'update'])->name('problemas.update');
        Route::delete('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'destroy'])->name('problemas.destroy');
    });

    // Métodos: listado y detalle visible para todos los autenticados
    Route::get('/metodos', [MetodoController::class, 'index'])->name('metodos.index');
    Route::get('/metodos/{id}', [MetodoController::class, 'show'])->name('metodos.show')->where('id', '[0-9]+');
    Route::get('/metodos/{id}/descargar-tex', [MetodoController::class, 'downloadTex'])->name('metodos.download-tex')->where('id', '[0-9]+');
    Route::get('/metodos/{id}/descargar-pdf', [MetodoController::class, 'downloadPdf'])->name('metodos.download-pdf')->where('id', '[0-9]+');

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

    // Reparar LaTeX
    Route::get('/fix-latex', [App\Http\Controllers\FixLatexController::class, 'index'])->name('fix-latex');
    Route::post('/fix-latex/scan', [App\Http\Controllers\FixLatexController::class, 'scan'])->name('fix-latex.scan');
    Route::post('/fix-latex/fix', [App\Http\Controllers\FixLatexController::class, 'fix'])->name('fix-latex.fix');
});

// Rutas de Hojas de Problemas (PimSheets)
Route::middleware('auth')->prefix('pim-sheets')->name('pim-sheets.')->group(function () {
    Route::get('/', [PimSheetController::class, 'index'])->name('index');

    // Solo editores y administradores pueden subir sheets
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/create', [PimSheetController::class, 'create'])->name('create');
        Route::post('/', [PimSheetController::class, 'store'])->name('store');
    });

    // Solo administradores pueden eliminar sheets
    Route::delete('/{id}', [PimSheetController::class, 'destroy'])->name('destroy');

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
