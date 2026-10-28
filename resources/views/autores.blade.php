@extends('layouts.main')

@section('title', 'Autores - PIM')

@section('styles')
.autores-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.autores-container h1 {
    font-size: 2rem;
    color: #2d3748;
    text-align: center;
    margin-bottom: 2rem;
}
.autor-card {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.autor-card img {
    width: 150px;
    height: 200px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}
.autor-card h2 {
    margin: 0 0 0.75rem;
    color: #2d3748;
    font-size: 1.3rem;
}
.autor-card ul {
    margin: 0;
    padding-left: 1.2rem;
    color: #4a5568;
    line-height: 1.8;
}
.autor-card a {
    color: #4299e1;
    text-decoration: none;
}
.autor-card a:hover {
    text-decoration: underline;
}
@media (max-width: 640px) {
    .autor-card { flex-direction: column; text-align: center; }
    .autor-card ul { list-style: none; padding-left: 0; }
}
@endsection

@section('content')
<div class="autores-container">
    <h1>Autores</h1>

    <div class="autor-card">
        <img src="{{ asset('images/autores/andrei-jaikin.jpg') }}" alt="Andrei Jaikin">
        <div>
            <h2>Andrei Jaikin</h2>
            <ul>
                <li>Director del proyecto</li>
                <li>Director del PIM</li>
                <li>Autor de materiales didácticos</li>
            </ul>
        </div>
    </div>

    <div class="autor-card">
        <img src="{{ asset('images/autores/georgy-nuzhdin.jpg') }}" alt="Georgy Nuzhdin">
        <div>
            <h2>Georgy Nuzhdin</h2>
            <ul>
                <li>Autor y traductor de problemas y hojas temáticas</li>
                <li>Soporte técnico</li>
                <li>Autor de <a href="https://mathgames.es" target="_blank" rel="noopener">mathgames.es</a></li>
            </ul>
        </div>
    </div>
</div>
@endsection
