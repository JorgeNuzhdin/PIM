<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanUploadMetodos
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->canUploadMetodos()) {
            abort(403, 'No tienes permisos para gestionar métodos.');
        }

        return $next($request);
    }
}
