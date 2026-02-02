@extends('layouts.main')

@section('title', 'Inicio - PIM')

@section('styles')
<style>
    .home-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .home-container h2 {
        color: #4a5568;
        margin-bottom: 1rem;
    }

    .home-container p {
        color: #718096;
        line-height: 1.6;
    }

    .alert-success {
        padding: 1rem;
        background-color: #c6f6d5;
        border: 1px solid #48bb78;
        border-radius: 4px;
        color: #22543d;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="home-container">
        <h2>Bienvenido al Pequeño Instituto de Matemáticas</h2>

        @if (session('status'))
            <div class="alert-success">
                {{ session('status') }}
            </div>
        @endif

        <p>Has iniciado sesión correctamente. Usa el menú de navegación para acceder a las diferentes secciones de la aplicación.</p>
    </div>
</div>
@endsection
