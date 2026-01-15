<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanEditProblemas
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->canEditProblemas()) {
            abort(403, 'No tienes permisos para esta acción');
        }

        return $next($request);
    }
}