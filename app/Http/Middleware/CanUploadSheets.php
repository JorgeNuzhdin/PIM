<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanUploadSheets
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->canUploadSheets()) {
            abort(403, 'No tienes permisos para subir hojas de problemas.');
        }

        return $next($request);
    }
}
