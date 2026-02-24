@extends('layouts.main')

@section('title', 'Crear Problema')

@section('styles')
{{-- Los mismos estilos que create.blade.php --}}
@include('problemas._styles')

@endsection

@section('content')
<div class="form-container">
    <div class="form-header">
        <h1><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="#4299e1" style="vertical-align:middle;margin-right:0.3em;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Crear Nuevo Problema</h1>
    </div>
    
    <form action="{{ route('problemas.store') }}" method="POST" enctype="multipart/form-data" id="problema-form">
        @csrf
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