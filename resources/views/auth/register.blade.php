@extends('layouts.main')

@section('title', 'Registro - PIM')

@section('styles')
<style>
    .register-container {
        max-width: 500px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .register-container h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #4a5568;
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

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
        background: white;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #4a5568;
        box-shadow: 0 0 0 2px rgba(74, 85, 104, 0.2);
    }

    .form-group input.is-invalid,
    .form-group select.is-invalid {
        border-color: #e53e3e;
    }

    .otro-input {
        margin-top: 0.5rem;
    }

    .invalid-feedback {
        color: #e53e3e;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .btn-register {
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

    .btn-register:hover {
        background-color: #2d3748;
    }

    .login-link {
        text-align: center;
        margin-top: 1rem;
        color: #718096;
    }

    .login-link a {
        color: #4a5568;
        font-weight: 600;
    }
</style>
@endsection

@section('scripts')
<script>
function toggleOtro(field, value) {
    const input = document.getElementById(field + '_otro');
    input.style.display = value === 'otro' ? 'block' : 'none';
    if (value !== 'otro') input.value = '';
}
</script>
@endsection

@section('content')
<div class="container">
    <div class="register-container">
        <h2>Registro</h2>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label for="name">Nombre</label>
                <input id="name" type="text" class="@error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" class="@error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="institution">Institución</label>
                <input id="institution" type="text" class="@error('institution') is-invalid @enderror" name="institution" value="{{ old('institution') }}" autocomplete="organization" placeholder="PIM">
                @error('institution')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="profession">Profesión / Estudios actuales <span style="color:#e53e3e;">*</span></label>
                <select id="profession" name="profession" class="@error('profession') is-invalid @enderror" onchange="toggleOtro('profession', this.value)">
                    <option value="">-- Selecciona --</option>
                    <option value="alumno_secundaria"  {{ old('profession') === 'alumno_secundaria'  ? 'selected' : '' }}>Alumno de secundaria</option>
                    <option value="alumno_bachillerato" {{ old('profession') === 'alumno_bachillerato' ? 'selected' : '' }}>Alumno de bachillerato</option>
                    <option value="alumno_universitario" {{ old('profession') === 'alumno_universitario' ? 'selected' : '' }}>Alumno universitario</option>
                    <option value="profesor_matematicas" {{ old('profession') === 'profesor_matematicas' ? 'selected' : '' }}>Profesor de matemáticas (colegio o instituto)</option>
                    <option value="profesor_universitario" {{ old('profession') === 'profesor_universitario' ? 'selected' : '' }}>Profesor universitario</option>
                    <option value="otro" {{ old('profession') === 'otro' ? 'selected' : '' }}>Otro</option>
                </select>
                <input type="text" id="profession_otro" name="profession_otro" class="otro-input"
                       placeholder="Especifica tu profesión o estudios"
                       value="{{ old('profession_otro') }}"
                       style="display:{{ old('profession') === 'otro' ? 'block' : 'none' }};">
                @error('profession')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="reason">Motivo de registro <span style="color:#e53e3e;">*</span></label>
                <select id="reason" name="reason" class="@error('reason') is-invalid @enderror" onchange="toggleOtro('reason', this.value)">
                    <option value="">-- Selecciona --</option>
                    <option value="estudiar_matematicas"  {{ old('reason') === 'estudiar_matematicas'  ? 'selected' : '' }}>Estudiar matemáticas</option>
                    <option value="ensenar_matematicas" {{ old('reason') === 'ensenar_matematicas' ? 'selected' : '' }}>Enseñar matemáticas</option>
                    <option value="otro" {{ old('reason') === 'otro' ? 'selected' : '' }}>Otro</option>
                </select>
                <input type="text" id="reason_otro" name="reason_otro" class="otro-input"
                       placeholder="Especifica el motivo"
                       value="{{ old('reason_otro') }}"
                       style="display:{{ old('reason') === 'otro' ? 'block' : 'none' }};">
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input id="password" type="password" class="@error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password-confirm">Confirmar contraseña</label>
                <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-register">Registrarse</button>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a>
        </div>
    </div>
</div>
@endsection
