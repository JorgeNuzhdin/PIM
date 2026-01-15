@extends('layouts.main')

@section('title', 'Inicio - MatemáticaMente')

@section('styles')
.welcome-text {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 1rem;
    text-align: center;
}
.subtitle {
    font-size: 1.25rem;
    color: #4a5568;
    text-align: center;
}
.container {
    margin-top: 4rem;
}
@endsection

@section('content')
<div class="container">
    <h1 class="welcome-text">Bienvenido a PIM</h1>
    <p class="subtitle">Tu plataforma de problemas matemáticos en LaTeX</p>
</div>
@endsection