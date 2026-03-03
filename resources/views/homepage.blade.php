@extends('layouts.main')

@section('title', 'Inicio - PIM')

@section('styles')
<style>
.welcome-text {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 0.5rem;
    text-align: center;
}
.subtitle {
    font-size: 1.25rem;
    color: #4a5568;
    text-align: center;
    margin-bottom: 2rem;
}
.home-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.info-text {
    text-align: center;
    color: #4a5568;
    font-size: 1rem;
    margin-bottom: 2rem;
    line-height: 1.7;
}
.info-text a {
    color: #4299e1;
    text-decoration: none;
}
.info-text a:hover {
    text-decoration: underline;
}
.stats-bar {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
}
.stat-item {
    text-align: center;
    background: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #4299e1;
}
.stat-label {
    font-size: 0.9rem;
    color: #718096;
    margin-top: 0.25rem;
}
.problema-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}
.problema-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e2e8f0;
}
.problema-card-header h3 {
    margin: 0;
    color: #2d3748;
    font-size: 1.1rem;
}
.difficulty-badge {
    background: #ebf8ff;
    color: #2b6cb0;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}
.problema-card-body {
    line-height: 1.8;
    color: #2d3748;
}
.btn-reload {
    display: inline-block;
    margin-top: 1rem;
    background: #4299e1;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s;
}
.btn-reload:hover {
    background: #3182ce;
}
@media (max-width: 640px) {
    .welcome-text { font-size: 1.75rem; }
    .stats-bar { flex-direction: column; align-items: center; }
}
</style>
@endsection

@section('content')
<div class="home-container">
    <h1 class="welcome-text">Plataforma de Problemas de Matemáticas</h1>

    <div class="info-text">
        <p>
            Esta base de problemas es parte del proyecto
            <a href="https://www.icmat.es/PIM/" target="_blank" rel="noopener">Pequeño Instituto de Matemáticas</a>,
            organizado por el
            <a href="https://www.icmat.es" target="_blank" rel="noopener">Instituto de Ciencias Matemáticas</a>.
        </p>
        <p>
            Para ver los problemas y las hojas temáticas que se han utilizado en la escuela durante los últimos
            dos años académicos es necesario <a href="/register">registrarse</a>.
        </p>
        <p>
            Para poder usar las opciones avanzadas de la base de datos, como ver todos los problemas,
            descargar problemas en LaTeX y confeccionar tus propias hojas, contacta con los
            <a href="mailto:pim@icmat.es">organizadores del PIM</a>.
        </p>
    </div>

    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-number">{{ number_format($totalProblemas) }}</div>
            <div class="stat-label">problemas disponibles</div>
        </div>
    </div>

    @if($problemaAleatorio)
    <div class="problema-card">
        <div class="problema-card-header">
            <h3>Problema aleatorio</h3>
            <span class="difficulty-badge">Nivel {{ $problemaAleatorio->difficulty }}</span>
        </div>
        <div class="problema-card-body">
            {!! $problemaAleatorio->problem_html_processed !!}
        </div>
        <a href="/" class="btn-reload">Otro problema</a>
    </div>
    @endif
</div>
@endsection
