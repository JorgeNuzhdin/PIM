@extends('layouts.main')

@section('title', 'Métodos')

@section('styles')
<style>
    .metodos-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .metodos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .placeholder-message {
        background: white;
        border-radius: 8px;
        padding: 3rem;
        text-align: center;
        color: #718096;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')
<div class="container metodos-container">
    <div class="metodos-header">
        <h1>Métodos</h1>
        <a href="{{ route('metodos.create') }}" style="background:#4299e1;color:white;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">+ Añadir método</a>
    </div>

    @if(session('success'))
        <div style="background:#c6f6d5;color:#276749;padding:1rem;border-radius:4px;margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="placeholder-message">
        <p style="font-size: 1.2rem;">La vista de listado de métodos se creará próximamente.</p>
        <p>De momento puedes <a href="{{ route('metodos.create') }}" style="color:#4299e1;">añadir métodos</a>.</p>
    </div>
</div>
@endsection
