@extends('layouts.main')

@section('title', 'Contacto - PIM')

@section('styles')
.contacto-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.contacto-container h1 {
    text-align: center;
    color: #2d3748;
    font-size: 1.75rem;
    margin: 0 0 0.5rem;
}
.contacto-intro {
    color: #4a5568;
    text-align: center;
    margin-bottom: 1.75rem;
    line-height: 1.6;
}
.contacto-intro a {
    color: #4299e1;
    text-decoration: none;
}
.contacto-container .form-group {
    margin-bottom: 1.1rem;
}
.contacto-container label {
    display: block;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.35rem;
}
.contacto-container input[type=text],
.contacto-container input[type=email],
.contacto-container input[type=number],
.contacto-container select,
.contacto-container textarea {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
    box-sizing: border-box;
}
.contacto-container textarea {
    min-height: 150px;
    resize: vertical;
}
.contacto-container input:focus,
.contacto-container select:focus,
.contacto-container textarea:focus {
    outline: none;
    border-color: #4299e1;
    box-shadow: 0 0 0 3px rgba(66,153,225,0.15);
}
.contacto-container input.captcha {
    width: 6rem;
}
.campo-error {
    color: #c53030;
    font-size: 0.875rem;
    margin-top: 0.3rem;
}
/* Honeypot: fuera de la vista, sin display:none para que los bots lo rellenen */
.hp-field {
    position: absolute;
    left: -10000px;
    width: 1px;
    height: 1px;
    overflow: hidden;
}
.btn-enviar {
    width: 100%;
    background: #4299e1;
    color: white;
    border: none;
    padding: 0.75rem;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-enviar:hover {
    background: #3182ce;
}
@endsection

@section('content')
<div class="contacto-container">
    <h1>Contacto</h1>
    <p class="contacto-intro">
        Escríbenos para solicitar permisos ampliados (problemas de otros años, métodos, creación de hojas)
        o para cualquier otra consulta sobre el <a href="{{ route('proyecto') }}">proyecto</a>.
    </p>

    <form method="POST" action="{{ route('contacto.send') }}">
        @csrf

        <div class="hp-field" aria-hidden="true">
            <label for="website">No rellenar</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $user->name ?? '') }}" required maxlength="120">
            @error('nombre') <div class="campo-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required maxlength="190">
            @error('email') <div class="campo-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="motivo">Motivo</label>
            <select id="motivo" name="motivo" required>
                @foreach($motivos as $clave => $texto)
                    <option value="{{ $clave }}" {{ old('motivo', request('motivo')) === $clave ? 'selected' : '' }}>{{ $texto }}</option>
                @endforeach
            </select>
            @error('motivo') <div class="campo-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="mensaje">Mensaje</label>
            <textarea id="mensaje" name="mensaje" required minlength="10" maxlength="5000"
                      placeholder="Cuéntanos quién eres, dónde das clase o con qué actividad colaboras, y qué necesitas.">{{ old('mensaje') }}</textarea>
            @error('mensaje') <div class="campo-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="captcha">{{ $pregunta }}</label>
            <input type="number" id="captcha" name="captcha" class="captcha" required>
            @error('captcha') <div class="campo-error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn-enviar">Enviar</button>
    </form>
</div>
@endsection
