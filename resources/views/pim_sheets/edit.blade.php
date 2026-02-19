@extends('layouts.main')

@section('title', 'Editar Hoja de Problemas')

@section('styles')
<style>
    .form-container {
        max-width: 700px;
        margin: 2rem auto;
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-container h1 { margin-bottom: 1.5rem; color: #2d3748; }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #2d3748; }
    .form-group label .required { color: #e53e3e; }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select {
        width: 100%;
        padding: 0.65rem 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
    }
    .form-actions { display: flex; gap: 1rem; margin-top: 2rem; }
    .btn { padding: 0.65rem 1.5rem; border-radius: 4px; border: none; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; }
    .btn-primary { background: #4299e1; color: white; }
    .btn-primary:hover { background: #3182ce; }
    .btn-secondary { background: #a0aec0; color: white; }
    .btn-secondary:hover { background: #718096; }
    .alert-error { background: #fed7d7; color: #c53030; border: 1px solid #fc8181; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
</style>
@endsection

@section('content')
<div class="form-container">
    <h1>✏️ Editar hoja: {{ $sheet->title }}</h1>

    @if($errors->any())
        <div class="alert-error">
            <ul style="margin:0;padding-left:1.2rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pim-sheets.update', ['id' => $sheet->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Título <span class="required">*</span></label>
            <input type="text" name="title" value="{{ old('title', $sheet->title) }}" required>
        </div>

        <div class="form-group">
            <label>Año <span class="required">*</span></label>
            <input type="number" name="date_year" value="{{ old('date_year', $sheet->date_year) }}" min="1900" max="2100" required>
        </div>

        <div class="form-group">
            <label>Grupo</label>
            <input type="text" name="planet" value="{{ old('planet', $sheet->planet) }}" placeholder="Ej: A, B, Saturno...">
        </div>

        <div class="form-group">
            <label>Institución <span class="required">*</span></label>
            <input type="text" name="institution" value="{{ old('institution', $sheet->institution) }}" required>
        </div>

        <div class="form-group">
            <label>Tema <span class="required">*</span></label>
            <select name="theme" required>
                <option value="">-- Seleccionar --</option>
                @foreach($temas as $tema)
                    <option value="{{ $tema->id }}" {{ old('theme', $sheet->theme) == $tema->id ? 'selected' : '' }}>
                        {{ $tema->tema }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
            <a href="{{ route('pim-sheets.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
