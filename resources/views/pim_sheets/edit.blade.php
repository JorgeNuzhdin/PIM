@extends('layouts.main')

@section('title', 'Editar Hoja de Problemas')

@section('content')
<div style="max-width: 700px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h1 style="margin-bottom: 1.5rem; color: #2d3748;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="#4299e1" style="vertical-align:middle;margin-right:0.3em;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Editar hoja: {{ $sheet->title }}</h1>

    @if($errors->any())
        <div style="background:#fed7d7;color:#c53030;border:1px solid #fc8181;padding:1rem;border-radius:4px;margin-bottom:1rem;">
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

        <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-weight:600;margin-bottom:0.4rem;color:#2d3748;">Título <span style="color:#e53e3e;">*</span></label>
            <input type="text" name="title" value="{{ old('title', $sheet->title) }}" required
                   style="width:100%;padding:0.65rem 0.75rem;border:1px solid #cbd5e0;border-radius:4px;font-size:1rem;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-weight:600;margin-bottom:0.4rem;color:#2d3748;">Año <span style="color:#e53e3e;">*</span></label>
            <input type="number" name="date_year" value="{{ old('date_year', $sheet->date_year) }}" min="1900" max="2100" required
                   style="width:120px;padding:0.65rem 0.75rem;border:1px solid #cbd5e0;border-radius:4px;font-size:1rem;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-weight:600;margin-bottom:0.4rem;color:#2d3748;">Grupo</label>
            <input type="text" name="planet" value="{{ old('planet', $sheet->planet) }}" placeholder="Ej: A, B, Saturno..."
                   style="width:100%;padding:0.65rem 0.75rem;border:1px solid #cbd5e0;border-radius:4px;font-size:1rem;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-weight:600;margin-bottom:0.4rem;color:#2d3748;">Institución <span style="color:#e53e3e;">*</span></label>
            <input type="text" name="institution" value="{{ old('institution', $sheet->institution) }}" required
                   style="width:100%;padding:0.65rem 0.75rem;border:1px solid #cbd5e0;border-radius:4px;font-size:1rem;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-weight:600;margin-bottom:0.4rem;color:#2d3748;">Tema <span style="color:#e53e3e;">*</span></label>
            <select name="theme" required
                    style="width:100%;padding:0.65rem 0.75rem;border:1px solid #cbd5e0;border-radius:4px;font-size:1rem;box-sizing:border-box;">
                <option value="">-- Seleccionar --</option>
                @foreach($temas as $tema)
                    <option value="{{ $tema->id }}" {{ old('theme', $sheet->theme) == $tema->id ? 'selected' : '' }}>
                        {{ $tema->tema }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;gap:1rem;margin-top:2rem;">
            <button type="submit"
                    style="background:#4299e1;color:white;border:none;padding:0.65rem 1.5rem;border-radius:4px;cursor:pointer;font-size:1rem;">
                💾 Guardar cambios
            </button>
            <a href="{{ route('pim-sheets.index') }}"
               style="background:#a0aec0;color:white;padding:0.65rem 1.5rem;border-radius:4px;text-decoration:none;font-size:1rem;display:inline-block;">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
