<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactoController extends Controller
{
    const MOTIVOS = [
        'anos_anteriores' => 'Ver problemas y hojas de otros años',
        'metodos'         => 'Acceso a los métodos',
        'crear'           => 'Crear métodos y hojas temáticas',
        'otro'            => 'Otro',
    ];

    public function show(Request $request)
    {
        // Antispam: suma sencilla cuya respuesta se guarda en sesión
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $request->session()->put('contacto_captcha', $a + $b);

        return view('contacto', [
            'motivos'  => self::MOTIVOS,
            'pregunta' => "¿Cuánto es $a + $b?",
            'user'     => Auth::user(),
        ]);
    }

    public function send(Request $request)
    {
        // Honeypot: un humano no ve ni rellena este campo
        if ($request->filled('website')) {
            return redirect()->route('contacto')->with('success', 'Mensaje enviado. ¡Gracias!');
        }

        $data = $request->validate([
            'nombre'  => 'required|string|max:120',
            'email'   => 'required|email|max:190',
            'motivo'  => 'required|in:' . implode(',', array_keys(self::MOTIVOS)),
            'mensaje' => 'required|string|min:10|max:5000',
            'captcha' => 'required|integer',
        ], [], [
            'captcha' => 'respuesta a la pregunta',
        ]);

        $esperado = $request->session()->pull('contacto_captcha');
        if ($esperado === null || (int) $data['captcha'] !== (int) $esperado) {
            return back()->withInput()->withErrors(['captcha' => 'La respuesta no es correcta.']);
        }

        $user = Auth::user();
        $cuerpo = "Motivo: " . self::MOTIVOS[$data['motivo']] . "\n"
                . "Nombre: {$data['nombre']}\n"
                . "Email: {$data['email']}\n"
                . ($user ? "Usuario registrado: #{$user->id} ({$user->email}), rol {$user->rol}\n" : "Usuario no registrado\n")
                . "\n" . $data['mensaje'] . "\n";

        try {
            Mail::raw($cuerpo, function ($m) use ($data) {
                $m->to(config('services.contact.email'))
                  ->replyTo($data['email'], $data['nombre'])
                  ->subject('[MathProblems] ' . self::MOTIVOS[$data['motivo']]);
            });
        } catch (\Throwable $e) {
            Log::error('Contacto: fallo al enviar correo: ' . $e->getMessage());
            return back()->withInput()->with('error', 'No se ha podido enviar el mensaje. Inténtalo más tarde.');
        }

        return redirect()->route('contacto')->with('success', 'Mensaje enviado. Te responderemos lo antes posible.');
    }
}
