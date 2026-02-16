@extends('layouts.main')

@section('title', $metodo->title)

@section('styles')
<style>
    .metodo-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .metodo-header {
        margin-bottom: 1.5rem;
    }

    .metodo-header h1 {
        margin-bottom: 0.75rem;
        color: #2d3748;
    }

    .metodo-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .tag {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-decoration: none;
        transition: all 0.2s;
    }

    .tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        color: white;
    }

    .metodo-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        line-height: 1.8;
    }

    .metodo-back {
        display: inline-block;
        margin-bottom: 1rem;
        color: #4299e1;
        text-decoration: none;
        font-weight: 500;
    }

    .metodo-back:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
<div class="container metodo-container">
    <a href="{{ route('metodos.index') }}" class="metodo-back">&larr; Volver a métodos</a>

    <div class="metodo-header">
        <h1>{{ $metodo->title }}</h1>
        <div class="metodo-tags">
            <a href="{{ route('metodos.index', ['tema_id' => $metodo->tema_id]) }}" class="tag">{{ $metodo->tema->tema }}</a>
            <a href="{{ route('metodos.index', ['tema_id' => $metodo->tema_id, 'subtema_id' => $metodo->subtema_id]) }}" class="tag">{{ $metodo->subtema->nombre }}</a>
        </div>
    </div>

    <div class="metodo-content">
        {!! $metodo->method_html_processed !!}
    </div>
</div>
@endsection
