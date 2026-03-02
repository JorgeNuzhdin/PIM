@extends('layouts.main')

@section('title', 'Editar perfil - PIM')

@section('styles')
<style>
    .profile-container {
        max-width: 520px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .profile-container h2 {
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

    .btn-save {
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

    .btn-save:hover {
        background-color: #2d3748;
    }

    .alert-success {
        background: #c6f6d5;
        color: #22543d;
        border: 1px solid #9ae6b4;
        border-radius: 4px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        text-align: center;
    }

    .field-note {
        font-size: 0.8rem;
        color: #718096;
        margin-top: 0.25rem;
    }
</style>
@endsection

@section('content')
@php
    $professionKeys = ['alumno_secundaria','alumno_bachillerato','alumno_universitario','profesor_matematicas','profesor_universitario'];
    $currentProfession = old('profession', in_array($user->profession, $professionKeys) ? $user->profession : ($user->profession ? 'otro' : ''));
    $currentProfessionOtro = old('profession_otro', !in_array($user->profession, $professionKeys) ? $user->profession : '');

    $reasonKeys = ['estudiar_matematicas','ensenar_matematicas'];
    $currentReason = old('reason', in_array($user->reason, $reasonKeys) ? $user->reason : ($user->reason ? 'otro' : ''));
    $currentReasonOtro = old('reason_otro', !in_array($user->reason, $reasonKeys) ? $user->reason : '');
@endphp

<div class="container">
    <div class="profile-container">
        <h2>Editar perfil</h2>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Nombre</label>
                <input id="name" type="text" class="@error('name') is-invalid @enderror"
                       name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" class="@error('email') is-invalid @enderror"
                       name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="profession">Profesión / Estudios actuales <span style="color:#e53e3e;">*</span></label>
                <select id="profession" name="profession"
                        class="@error('profession') is-invalid @enderror"
                        onchange="toggleOtro('profession', this.value)">
                    <option value="">-- Selecciona --</option>
                    <option value="alumno_secundaria"    {{ $currentProfession === 'alumno_secundaria'    ? 'selected' : '' }}>Alumno de secundaria</option>
                    <option value="alumno_bachillerato"  {{ $currentProfession === 'alumno_bachillerato'  ? 'selected' : '' }}>Alumno de bachillerato</option>
                    <option value="alumno_universitario" {{ $currentProfession === 'alumno_universitario' ? 'selected' : '' }}>Alumno universitario</option>
                    <option value="profesor_matematicas" {{ $currentProfession === 'profesor_matematicas' ? 'selected' : '' }}>Profesor de matemáticas (colegio o instituto)</option>
                    <option value="profesor_universitario" {{ $currentProfession === 'profesor_universitario' ? 'selected' : '' }}>Profesor universitario</option>
                    <option value="otro"                 {{ $currentProfession === 'otro'                 ? 'selected' : '' }}>Otro</option>
                </select>
                <input type="text" id="profession_otro" name="profession_otro" class="otro-input"
                       placeholder="Especifica tu profesión o estudios"
                       value="{{ $currentProfessionOtro }}"
                       style="display:{{ $currentProfession === 'otro' ? 'block' : 'none' }};">
                @error('profession')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="reason">Motivo de registro <span style="color:#e53e3e;">*</span></label>
                <select id="reason" name="reason"
                        class="@error('reason') is-invalid @enderror"
                        onchange="toggleOtro('reason', this.value)">
                    <option value="">-- Selecciona --</option>
                    <option value="estudiar_matematicas"  {{ $currentReason === 'estudiar_matematicas'  ? 'selected' : '' }}>Estudiar matemáticas</option>
                    <option value="ensenar_matematicas"   {{ $currentReason === 'ensenar_matematicas'   ? 'selected' : '' }}>Enseñar matemáticas</option>
                    <option value="otro"                  {{ $currentReason === 'otro'                  ? 'selected' : '' }}>Otro</option>
                </select>
                <input type="text" id="reason_otro" name="reason_otro" class="otro-input"
                       placeholder="Especifica el motivo"
                       value="{{ $currentReasonOtro }}"
                       style="display:{{ $currentReason === 'otro' ? 'block' : 'none' }};">
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-save">Guardar cambios</button>
        </form>
    </div>
</div>
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
