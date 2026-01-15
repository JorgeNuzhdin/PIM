<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Filtros
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', '>=', $request->created_at);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function updateRol(Request $request, User $user)
    {
        $request->validate([
            'rol' => 'required|in:user,profesor,editor,admin'
        ]);

        $user->update(['rol' => $request->rol]);

        return back()->with('success', 'Rol actualizado correctamente.');
    }
}