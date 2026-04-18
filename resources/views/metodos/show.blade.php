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

    .metodo-title-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .metodo-title-row h1 {
        margin: 0;
    }

    .edit-icon {
        color: #4299e1;
        font-size: 1.2rem;
        text-decoration: none;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .edit-icon:hover {
        opacity: 1;
    }

    .metodo-proponente {
        color: #718096;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .btn-carrito-show {
        background: none;
        border: 2px solid #cbd5e0;
        border-radius: 8px;
        padding: 0.4rem 0.8rem;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-carrito-show:hover {
        border-color: #48bb78;
        background: #f0fff4;
    }

    .btn-carrito-show.en-carrito {
        background: #48bb78;
        border-color: #48bb78;
        color: white;
    }
</style>
@endsection

@section('content')
<div class="container metodo-container">
    <a href="{{ route('metodos.index') }}" class="metodo-back">&larr; Volver a métodos</a>

    <div class="metodo-header">
        <div class="metodo-title-row">
            <h1>{{ $metodo->title }}</h1>
            @auth
                <button class="btn-carrito-show" id="btn-carrito-metodo" data-metodo-id="{{ $metodo->id }}" onclick="toggleCarritoMetodo({{ $metodo->id }}, this)" title="Añadir al carrito">🛒</button>
                <button type="button" onclick="openReportMetodoModal({{ $metodo->id }})"
                    style="background:none; border:1px solid #e2e8f0; border-radius:4px; padding:0.35rem 0.65rem; cursor:pointer; color:#718096; line-height:1;"
                    title="Reportar un error en este método">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="currentColor" style="vertical-align:middle;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                </button>
                @if(in_array(Auth::user()->rol, ['admin', 'editor', 'profesor_seguro']))
                    <a href="{{ route('metodos.edit', $metodo->id) }}" class="edit-icon" title="Editar método">&#9998;</a>
                @endif
            @endauth
        </div>
        <div class="metodo-tags">
            <a href="{{ route('metodos.index', ['tema_id' => $metodo->tema_id]) }}" class="tag">{{ $metodo->tema->tema }}</a>
            @foreach($metodo->subtemas as $subtema)
                <a href="{{ route('metodos.index', ['tema_id' => $metodo->tema_id, 'subtema_id' => $subtema->id]) }}" class="tag">{{ $subtema->nombre }}</a>
            @endforeach
        </div>
        <div class="metodo-proponente">Institución: {{ $metodo->institution ?? 'PIM' }}</div>
        @if($metodo->user)
            <div class="metodo-proponente">Proponente: {{ $metodo->user->name }}</div>
        @endif
    </div>

    <div class="metodo-content">
        {!! $metodo->method_html_processed !!}
    </div>
</div>
@endsection

@auth
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("carrito.count") }}')
        .then(r => r.json())
        .then(data => {
            if (data.metodo_ids && data.metodo_ids.includes({{ $metodo->id }})) {
                const btn = document.getElementById('btn-carrito-metodo');
                if (btn) {
                    btn.classList.add('en-carrito');
                    btn.title = 'Quitar del carrito';
                }
            }
            const countEl = document.getElementById('carrito-count');
            if (countEl) countEl.textContent = data.count;
        })
        .catch(err => console.error('Error:', err));
});

function toggleCarritoMetodo(metodoId, button) {
    fetch('{{ route("carrito.toggle") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ metodo_id: metodoId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'added') {
            button.classList.add('en-carrito');
            button.title = 'Quitar del carrito';
        } else if (data.status === 'removed') {
            button.classList.remove('en-carrito');
            button.title = 'Añadir al carrito';
        }
        const countEl = document.getElementById('carrito-count');
        if (countEl) countEl.textContent = data.count;
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error al actualizar el carrito');
    });
}
</script>
@include('_partials.report-error-metodo-modal')
@endsection
@endauth
