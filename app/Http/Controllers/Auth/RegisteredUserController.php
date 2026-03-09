<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:' . User::class, 'lowercase'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
            'institution' => 'nullable|string|max:255',
            'profession' => 'required|string|max:255',
            'reason'     => 'required|string|max:255',
        ], [
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
        ]);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'institution' => $request->institution,
            'profession'  => $request->profession === 'otro' ? ($request->profession_otro ?? 'otro') : $request->profession,
            'reason'      => $request->reason     === 'otro' ? ($request->reason_otro     ?? 'otro') : $request->reason,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('problemas.index'));
    }
}
