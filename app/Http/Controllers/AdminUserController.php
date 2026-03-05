<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Problema;
use App\Models\Setting;
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
        if ($request->filled('profession')) {
            $query->where('profession', $request->profession);
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', '>=', $request->created_at);
        }

        // Ordenación
        $sortable = ['name', 'email', 'created_at', 'rol', 'profession'];
        $sort = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'created_at';
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $users = $query->orderBy($sort, $dir)->paginate(20);

        // Medalla
        $minMedalla = (int) Setting::get('min_problemas_medalla', 5);
        $usuariosConMedalla = Problema::select('proponent_id')
            ->whereNotNull('proponent_id')
            ->groupBy('proponent_id')
            ->havingRaw('COUNT(*) >= ?', [$minMedalla])
            ->havingRaw('SUM(approved = 0) = 0')
            ->pluck('proponent_id')
            ->flip()
            ->all();

        // Conteo de problemas por usuario (aprobados / total)
        $problemaCounts = Problema::select(
                'proponent_id',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'),
                \Illuminate\Support\Facades\DB::raw('SUM(approved = 1) as aprobados')
            )
            ->whereNotNull('proponent_id')
            ->groupBy('proponent_id')
            ->get()
            ->keyBy('proponent_id');

        return view('admin.users.index', compact('users', 'usuariosConMedalla', 'problemaCounts', 'sort', 'dir'));
    }

    public function updateRol(Request $request, User $user)
    {
        $request->validate([
            'rol' => 'required|in:user,profesor,editor,admin,user_seguro,profesor_seguro'
        ]);

        $user->update(['rol' => $request->rol]);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        // Evitar que un admin se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}