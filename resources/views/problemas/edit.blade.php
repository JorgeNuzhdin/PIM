@extends('layouts.main')

@section('title', 'Editar Problema')

@section('styles')
{{-- Los mismos estilos que create.blade.php --}}
@include('problemas._styles')

@endsection

@section('content')
<div class="form-container">
    <div class="form-header">
        <h1>✏️ Editar Problema #{{ $problema->id }}</h1>
    </div>
    
    @if(session('error'))
        <div style="background: #fff5f5; border: 1px solid #fc8181; color: #c53030; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            {{ session('error') }}
        </div>
    @endif
    
    @if(session('success'))
        <div style="background: #f0fff4; border: 1px solid #48bb78; color: #2f855a; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            ✅ {{ session('success') }}
        </div>
    @endif
    
    <form action="{{ route('problemas.update', $problema->id) }}" method="POST" enctype="multipart/form-data" id="problema-form">
        @csrf
        @method('PUT')
        @include('problemas._form')
    </form>
</div>
@endsection

@section('scripts')
<style>
    .form-container {
    width: 100% !important;
    max-width: 960px !important;
    margin: 2rem auto !important;
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    box-sizing: border-box !important;
}
</style>
@include('problemas._scripts')
@endsection