@extends('layouts.main')

@section('title', 'Configuración')

@section('content')
<div style="max-width: 600px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h1 style="margin-bottom: 1.5rem; color: #2d3748;">⚙️ Configuración del sitio</h1>

    @if(session('success'))
        <div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:0.75rem 1rem;border-radius:4px;margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fed7d7;color:#c53030;border:1px solid #fc8181;padding:0.75rem 1rem;border-radius:4px;margin-bottom:1rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf

        <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: #f7fafc; border-radius: 6px;">
            <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#2d3748;">
                Años académicos visibles para usuarios básicos
            </label>
            <p style="color:#718096; font-size:0.875rem; margin:0 0 0.75rem;">
                Los usuarios con rol <strong>user</strong> solo verán hojas y problemas de los últimos N años académicos
                (basado en el año más reciente de las hojas). Profesores, editores y admins siempre tienen acceso completo.
            </p>
            <div style="display:flex; align-items:center; gap:1rem;">
                <input type="number"
                       name="access_years"
                       value="{{ old('access_years', $settings['access_years']->value ?? 2) }}"
                       min="1" max="10"
                       style="width:80px; padding:0.5rem; border:1px solid #cbd5e0; border-radius:4px; font-size:1rem;">
                <span style="color:#4a5568;">año(s)</span>
            </div>
        </div>

        <button type="submit" style="background:#4299e1;color:white;border:none;padding:0.65rem 1.5rem;border-radius:4px;cursor:pointer;font-size:1rem;">
            💾 Guardar
        </button>
        <a href="{{ route('admin.users.index') }}" style="margin-left:1rem; color:#718096; text-decoration:none;">← Volver a usuarios</a>
    </form>
</div>
@endsection
