<?php



use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\HojaController;
use App\Http\Controllers\PimSheetController;


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
    Route::post('/carrito/limpiar', [App\Http\Controllers\CarritoController::class, 'limpiar'])->name('carrito.limpiar');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Rutas de problemas (solo para admin/editor)
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/problemas/crear', [App\Http\Controllers\ProblemaController::class, 'create'])->name('problemas.create');
        Route::post('/problemas', [App\Http\Controllers\ProblemaController::class, 'store'])->name('problemas.store');
        Route::get('/problemas/{id}/editar', [App\Http\Controllers\ProblemaController::class, 'edit'])->name('problemas.edit');
        Route::put('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'update'])->name('problemas.update');
        Route::delete('/problemas/{id}', [App\Http\Controllers\ProblemaController::class, 'destroy'])->name('problemas.destroy');
       
    });

});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/rol', [AdminUserController::class, 'updateRol'])->name('users.updateRol');
});

// Rutas de Hojas de Problemas (PimSheets)
Route::middleware('auth')->prefix('pim-sheets')->name('pim-sheets.')->group(function () {
    Route::get('/', [PimSheetController::class, 'index'])->name('index');
    Route::get('/{sheet}/download', [PimSheetController::class, 'download'])->name('download');

    // Solo editores y administradores pueden subir sheets
    Route::middleware('can.edit.problemas')->group(function () {
        Route::get('/create', [PimSheetController::class, 'create'])->name('create');
        Route::post('/', [PimSheetController::class, 'store'])->name('store');
    });
});
