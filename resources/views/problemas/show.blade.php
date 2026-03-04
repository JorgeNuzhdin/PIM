@extends('layouts.main')

@section('title', 'Problema #' . $problema->id)

@section('styles')
@include('problemas._styles')
<style>
.show-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.show-nav {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.show-nav a {
    color: #4299e1;
    text-decoration: none;
    font-size: 0.9rem;
}
.show-nav a:hover { text-decoration: underline; }
.show-nav .separator { color: #cbd5e0; }
.btn-icon {
    background: white;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    padding: 0.5rem;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.2s;
    line-height: 1;
}
.btn-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.btn-carrito.en-carrito {
    background-color: #48bb78;
    border-color: #48bb78;
}
.btn-carrito.en-carrito .carrito-icon::after {
    content: ' ✓';
}
</style>
@endsection

@section('content')
@php
    use App\Helpers\LatexHelper;
@endphp
<div class="show-container">

    {{-- Navegación --}}
    <div class="show-nav">
        <a href="{{ route('problemas.index') }}">&larr; Todos los problemas</a>
        @auth
            @if(Auth::user()->canEditProblemas())
                <span class="separator">|</span>
                <a href="{{ route('problemas.edit', ['id' => $problema->id, 'return' => urlencode(route('problemas.show', $problema->id))]) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="0.9em" height="0.9em" fill="currentColor" style="vertical-align:middle;margin-right:0.2em;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    Editar
                </a>
            @endif
        @endauth
    </div>

    {{-- Tarjeta del problema --}}
    <div class="problema-card" id="problema-{{ $problema->id }}">
        <div class="problema-header">

            {{-- Dificultad --}}
            @if($problema->difficulty)
                <a href="{{ route('problemas.index', ['difficulty_min' => $problema->difficulty, 'difficulty_max' => $problema->difficulty]) }}"
                   class="difficulty-badge" title="Dificultad {{ $problema->difficulty }}"
                   style="text-decoration:none;color:white;">
                    {{ $problema->difficulty }}
                </a>
            @endif

            <div class="problema-info">
                <div class="problema-title-row" style="display:flex;align-items:center;gap:0.75rem;flex:1;">
                    <h3 style="margin:0;">Problema #{{ $problema->id }}</h3>
                    @if($problema->title)
                        <span style="color:#4a5568;font-size:1rem;font-weight:500;">{{ $problema->title }}</span>
                    @endif
                    @if($problema->school_year)
                        @php $yearIdx = \App\Helpers\SchoolYearHelper::getYearIndex($problema->school_year); @endphp
                        <a href="{{ route('problemas.index', ['school_year_min' => $yearIdx, 'school_year_max' => $yearIdx]) }}"
                           class="year-badge" style="text-decoration:none;color:#4a5568;">
                            📚 {{ $problema->school_year }}
                        </a>
                    @endif
                    {{-- Carrito --}}
                    @auth
                        <button class="btn-icon btn-carrito {{ $enCarrito ? 'en-carrito' : '' }}"
                                data-problema-id="{{ $problema->id }}"
                                onclick="toggleCarrito({{ $problema->id }}, this)"
                                title="{{ $enCarrito ? 'Quitar del carrito' : 'Añadir al carrito' }}"
                                style="margin-left:auto;">
                            <span class="carrito-icon">🛒</span>
                        </button>
                    @endauth
                </div>

                @if($problema->tags && $problema->tags->count() > 0)
                    <div class="problema-tags">
                        @foreach($problema->tags as $tag)
                            <a href="{{ route('problemas.index', ['topic_title' => $tag->tag]) }}"
                               class="tag" style="text-decoration:none;color:white;cursor:pointer;">{{ $tag->tag }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Enunciado --}}
        <div class="problema-content">
            <strong>Enunciado:</strong>
            <div class="latex-content">{!! $problema->problem_html_processed !!}</div>
        </div>

        {{-- Pistas --}}
        @if($problema->hints)
            <div class="problema-content problema-pistas">
                <strong>💡 Pistas:</strong>
                <div class="latex-content pistas-content">{!! nl2br(e($problema->hints)) !!}</div>
            </div>
        @endif

        {{-- Solución --}}
        @if($problema->solution_tex)
            <div class="problema-content">
                <strong>Solución:</strong>
                <div class="latex-content">{!! $problema->solution_html_processed !!}</div>
            </div>
        @endif

        {{-- Comentarios: no visibles para rol user --}}
        @if($problema->comments && Auth::user()->rol !== 'user')
            <div class="problema-content problema-comentarios">
                <strong>💬 Comentarios:</strong>
                <div class="comentarios-content">{!! nl2br(e($problema->comments)) !!}</div>
            </div>
        @endif

        {{-- Footer: fuente y proponente --}}
        <div class="problema-footer" style="display:flex;gap:2rem;flex-wrap:wrap;">
            <small class="fuente-text">📖 <strong>Fuente:</strong> {{ $problema->source ?: 'desconocida' }}</small>
            @if($problema->proponent)
                <small class="fuente-text">👤 <strong>Proponente:</strong> {{ $problema->proponent->name }}</small>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
function toggleCarrito(problemaId, button) {
    fetch('{{ route("carrito.toggle") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ problema_id: problemaId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'added') {
            button.classList.add('en-carrito');
            button.title = 'Quitar del carrito';
        } else {
            button.classList.remove('en-carrito');
            button.title = 'Añadir al carrito';
        }
        const countEl = document.getElementById('carrito-count');
        if (countEl) countEl.textContent = data.count;
    })
    .catch(() => {
        console.error('Error al actualizar el carrito');
    });
}
</script>
@endsection
