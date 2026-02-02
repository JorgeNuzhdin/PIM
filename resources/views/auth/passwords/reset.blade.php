@extends('layouts.main')

@section('title', 'Establecer nueva contraseña - PIM')

@section('styles')
<style>
    .reset-container {
        max-width: 500px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .reset-container h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #4a5568;
    }

    .reset-container p {
        text-align: center;
        color: #718096;
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #4a5568;
    }

    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
    }

    .form-group input:focus {
        outline: none;
        border-color: #4a5568;
        box-shadow: 0 0 0 2px rgba(74, 85, 104, 0.2);
    }

    .form-group input.is-invalid {
        border-color: #e53e3e;
    }

    .invalid-feedback {
        color: #e53e3e;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }

    .password-requirements {
        font-size: 0.875rem;
        color: #718096;
        margin-top: 0.25rem;
    }

    .btn-reset {
        width: 100%;
        padding: 0.75rem;
        background-color: #4a5568;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
    }

    .btn-reset:hover {
        background-color: #2d3748;
    }

    .back-to-login {
        text-align: center;
        margin-top: 1rem;
    }

    .back-to-login a {
        color: #718096;
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="reset-container">
        <h2>Establecer nueva contraseña</h2>
        <p>Introduce tu nueva contraseña.</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" class="@error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Nueva contraseña</label>
                <input id="password" type="password" class="@error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                <div class="password-requirements">Mínimo 8 caracteres</div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-reset">Restablecer contraseña</button>
        </form>

        <div class="back-to-login">
            <a href="{{ route('login') }}">← Volver al inicio de sesión</a>
        </div>
    </div>
</div>
@endsection
