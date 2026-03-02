<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'profession' => ['required', 'string', 'max:255'],
            'reason'     => ['required', 'string', 'max:255'],
        ]);

        $profession = $request->profession === 'otro'
            ? ($request->profession_otro ?: 'otro')
            : $request->profession;

        $reason = $request->reason === 'otro'
            ? ($request->reason_otro ?: 'otro')
            : $request->reason;

        $user->update([
            'name'       => $request->name,
            'email'      => $request->email,
            'profession' => $profession,
            'reason'     => $reason,
        ]);

        return redirect()->route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
    }
}
