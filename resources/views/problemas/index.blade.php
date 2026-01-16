@extends('layouts.main')

@section('title', 'Ver Problemas')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/pagination.css?v=3') }}">
<style>
/* === ESTRUCTURA PRINCIPAL === */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.stats {
    text-align: center;
    color: #718096;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

/* === FILTROS === */
.filtros {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filtros-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    align-items: start;
}

.form-group,
.form-group-range {
    display: flex;
    flex-direction: column;
}

.form-group label,
.form-group-range label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #4a5568;
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group-range input {
    padding: 0.6rem;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    font-size: 1rem;
    background-color: white;
    min-height: 38px;
}

.form-group select:hover {
    cursor: pointer;
}

.topic-container {
    position: relative;
}

#topic-suggestions {
    position: absolute;
    background: white;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    z-index: 1000;
    display: none;
}

#topic-suggestions div {
    padding: 0.5rem;
    cursor: pointer;
}

#topic-suggestions div:hover {
    background-color: #f7fafc;
}

/* Select múltiple personalizado */
.select-multiple-wrapper {
    position: relative;
}

.select-multiple-button {
    width: 100%;
    padding: 0.6rem;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    text-align: left;
}

.select-multiple-button:hover {
    border-color: #4a5568;
}

.select-multiple-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    margin-top: 0.25rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 100;
    max-height: 250px;
    overflow-y: auto;
}

.checkbox-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-option:hover {
    background: #f7fafc;
}

.checkbox-option input[type="checkbox"] {
    cursor: pointer;
}

/* Botones de filtros */
.form-buttons {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1.5rem;
    background-color: #4a5568;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.btn:hover {
    background-color: #2d3748;
}

.btn-secondary {
    background-color: #718096;
    text-align: center;
    text-decoration: none;
}

.btn-secondary:hover {
    background-color: #4a5568;
}

/* === TARJETAS DE PROBLEMAS === */
.problema-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.problema-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.75rem;
}

.problema-info {
    flex: 1;
    min-width: 0;
}

.problema-title-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.problema-title-row h3 {
    margin: 0;
    color: #2d3748;
}

/* Nivel de dificultad */
.difficulty-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    flex-shrink: 0;
}

/* Año académico */
.year-badge {
    background: #edf2f7;
    color: #4a5568;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
}

/* Tags */
.problema-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
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
    transition: all 0.2s;
}

.tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

/* Botones de acción */
.problema-actions {
    display: flex;
    gap: 0.5rem;
}

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

.btn-delete:hover {
    border-color: #e53e3e;
    background-color: #fff5f5;
}

.btn-carrito.en-carrito {
    background-color: #48bb78;
    border-color: #48bb78;
}

.btn-carrito.en-carrito .carrito-icon::after {
    content: ' ✓';
}

/* Contenido del problema */
.problema-content {
    margin-bottom: 1.5rem;
}

.latex-content {
    padding: 1rem;
    background-color: #f7fafc;
    border-radius: 4px;
    line-height: 1.8;
    overflow-x: auto;
}

.latex-image {
    display: block;
    margin: 1rem auto;
    max-width: 100%;
    height: auto;
}

/* Pistas */
.problema-pistas {
    background: #fffbeb;
    border-left: 4px solid #f59e0b;
    padding: 1rem;
    border-radius: 4px;
}

.pistas-content {
    background: transparent !important;
    padding: 0 !important;
}

/* Comentarios */
.problema-comentarios {
    background: #f0f9ff;
    border-left: 4px solid #3b82f6;
    padding: 1rem;
    border-radius: 4px;
}

.comentarios-content {
    color: #1e40af;
    font-style: italic;
}

/* Fuente */
.problema-footer {
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
    margin-top: 1rem;
}

.fuente-text {
    color: #718096;
    font-size: 0.9rem;
}

/* === RESPONSIVE === */
@media (max-width: 1024px) {
    .filtros-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    .filtros {
        padding: 1rem;
    }
    
    .filtros-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group input,
    .form-group select,
    .form-group-range input,
    .select-multiple-button {
        font-size: 16px;
    }
    
    .form-group-range > div {
        flex-wrap: wrap;
    }
    
    .select-multiple-dropdown {
        max-height: 200px;
    }

    .stats {
        font-size: 0.85rem;
        line-height: 1.6;
    }
    
    .problema-card {
        padding: 1rem;
    }
    
    .problema-header {
        display: grid;
        grid-template-columns: auto 1fr auto;
        grid-template-rows: auto auto auto;
        gap: 0.5rem 0.75rem;
        align-items: start;
    }
    
    /* Línea 1: Título ocupa las 3 columnas */
    .problema-info {
        grid-column: 1 / 4;
        grid-row: 1;
        width: 100%;
    }
    
    .problema-title-row {
        flex-wrap: nowrap;
        margin-bottom: 0;
    }
    
    .problema-title-row h3 {
        font-size: 1.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Mover el año a la línea 2 en móvil */
    .difficulty-badge {
        grid-column: 1;
        grid-row: 2;
        width: 35px;
        height: 35px;
        font-size: 0.95rem;
    }
    
    .year-badge {
        grid-column: 2;
        grid-row: 2;
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        align-self: center;
    }
    
    .problema-actions {
        grid-column: 3;
        grid-row: 2;
        justify-self: end;
    }
    
    /* Línea 3: Tags ocupan las 3 columnas */
    .problema-tags {
        grid-column: 1 / 4;
        grid-row: 3;
        gap: 0.3rem;
    }
    
    .tag {
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
    }
    
    .btn-icon {
        font-size: 1rem;
        padding: 0.4rem;
    }
    
    .latex-content {
        font-size: 0.9rem;
        padding: 0.75rem;
    }
    
    .latex-content img {
        max-width: 100% !important;
        height: auto !important;
    }
    
    .problema-pistas,
    .problema-comentarios {
        padding: 0.75rem;
    }
}

@media (max-width: 480px) {
    .filtros {
        padding: 0.75rem;
    }
    
    .form-group label,
    .form-group-range label {
        font-size: 0.85rem;
    }
    
    .select-multiple-button {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    .checkbox-option {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    .form-buttons {
        flex-direction: column;
    }

    .problema-content strong {
        display: block;
        margin-bottom: 0.5rem;
    }
}

@endsection

@section('content')
<div class="container">
   <h1>Buscador de problemas</h1>

    <div class="stats">
    Total de problemas en la base de datos: <strong>{{ $totalProblemas }}</strong>
    @if(request()->hasAny(['buscar', 'tema_id', 'topic_title']))
        | Problemas encontrados: <strong style="color: #4299e1;">{{ $problemasEncontrados }}</strong>
    @endif
    | Mostrando en esta página: <strong>{{ $problemas->count() }}</strong>
</div>


<div class="filtros">
    <form method="GET" action="{{ route('problemas.index') }}">
        <div class="filtros-grid">
            {{-- Fila 1: Filtros principales --}}
            <div class="form-group topic-container">
                <label for="topic">Tag (Topic)</label>
                <input type="text" 
                       id="topic-input" 
                       name="topic_display" 
                       placeholder="Escribe al menos 3 caracteres..."
                       autocomplete="off"
                       value="{{ request('topic_display') }}">
                <input type="hidden" id="topic-title" name="topic_title" value="{{ request('topic_title') }}">
                <div id="topic-suggestions"></div>
            </div>

            <div class="form-group">
                <label for="tema_id">Tema</label>
                <select name="tema_id" id="tema_id">
                    <option value="">-- Todos los temas --</option>
                    @foreach($temas as $tema)
                        <option value="{{ $tema->id }}" {{ request('tema_id') == $tema->id ? 'selected' : '' }}>
                            {{ $tema->tema }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="buscar">Buscar texto</label>
                <input type="text" 
                       name="buscar" 
                       id="buscar" 
                       placeholder="Buscar en problema o solución..."
                       value="{{ request('buscar') }}">
            </div>
            
            {{-- Fila 2: Rangos --}}
            <div class="form-group-range">
                <label>Dificultad (1-6)</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="number" 
                           name="difficulty_min" 
                           placeholder="Min"
                           min="1" max="6"
                           value="{{ request('difficulty_min') }}"
                           style="width: 70px;">
                    <span>—</span>
                    <input type="number" 
                           name="difficulty_max" 
                           placeholder="Max"
                           min="1" max="10"
                           value="{{ request('difficulty_max') }}"
                           style="width: 70px;">
                </div>
            </div>

            <div class="form-group">
                <label for="school_year">Año académico (hasta)</label>
                <select name="school_year" id="school_year">
                    <option value="">-- Todos los años --</option>
                    @foreach($schoolYears as $index => $yearName)
                        <option value="{{ $index }}" {{ request('school_year') == $index ? 'selected' : '' }}>
                            {{ $yearName }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            {{-- Opciones a mostrar --}}
<div class="form-group">
    <label for="mostrar">Mostrar</label>
    <div class="select-multiple-wrapper">
        <button type="button" class="select-multiple-button" onclick="toggleMostrar(event)">
            <span id="mostrar-text">Seleccionar opciones...</span>
            <span>▼</span>
        </button>
        <div class="select-multiple-dropdown" id="mostrar-dropdown" style="display: none;">
            @php
                $mostrarArray = is_array(request('mostrar')) ? request('mostrar') : ['fuente', 'pistas', 'solucion', 'comentarios', 'year'];
            @endphp
            
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="fuente" {{ in_array('fuente', $mostrarArray) ? 'checked' : '' }} onchange="updateMostrarText()">
                Fuente
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="pistas" {{ in_array('pistas', $mostrarArray) ? 'checked' : '' }} onchange="updateMostrarText()">
                Pistas
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="solucion" {{ in_array('solucion', $mostrarArray) ? 'checked' : '' }} onchange="updateMostrarText()">
                Solución
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="comentarios" {{ in_array('comentarios', $mostrarArray) ? 'checked' : '' }} onchange="updateMostrarText()">
                Comentarios
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="year" {{ in_array('year', $mostrarArray) ? 'checked' : '' }} onchange="updateMostrarText()">
                Año académico
            </label>
        </div>
    </div>
</div>
          

            {{-- Botones --}}
            <div class="form-group form-buttons">
                <button type="submit" class="btn">Filtrar</button>
                <a href="{{ route('problemas.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </form>
</div>


    

@php
    $params = request()->except('page');
@endphp
<div class="pagination-wrapper">
    @if ($problemas->hasPages())
        <div class="pagination">
            {{-- Primera página --}}
            @if ($problemas->currentPage() > 1)
                <a href="{{ $problemas->url(1) }}" class="page-item" title="Primera página">&laquo;&laquo;</a>
            @else
                <span class="page-item disabled">&laquo;&laquo;</span>
            @endif

            {{-- Página anterior --}}
            @if ($problemas->onFirstPage())
                <span class="page-item disabled">&laquo;</span>
            @else
                <a href="{{ $problemas->previousPageUrl() }}" class="page-item">&laquo;</a>
            @endif

            {{-- Números de página --}}
            @php
                $current = $problemas->currentPage();
                $last = $problemas->lastPage();
                $start = max(1, $current - 4);
                $end = min($last, $current + 4);
            @endphp

            {{-- Primera página siempre visible --}}
            @if ($start > 1)
                <a href="{{ $problemas->url(1) }}" class="page-item">1</a>
                @if ($start > 2)
                    <span class="page-item disabled">...</span>
                @endif
            @endif

            {{-- Rango de páginas alrededor de la actual --}}
            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $current)
                    <span class="page-item active">{{ $page }}</span>
                @else
                    <a href="{{ $problemas->url($page) }}" class="page-item">{{ $page }}</a>
                @endif
            @endfor

            {{-- Última página siempre visible --}}
            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="page-item disabled">...</span>
                @endif
                <a href="{{ $problemas->url($last) }}" class="page-item">{{ $last }}</a>
            @endif

            {{-- Página siguiente --}}
            @if ($problemas->hasMorePages())
                <a href="{{ $problemas->nextPageUrl() }}" class="page-item">&raquo;</a>
            @else
                <span class="page-item disabled">&raquo;</span>
            @endif

            {{-- Última página --}}
            @if ($problemas->currentPage() < $problemas->lastPage())
                <a href="{{ $problemas->url($problemas->lastPage()) }}" class="page-item" title="Última página">&raquo;&raquo;</a>
            @else
                <span class="page-item disabled">&raquo;&raquo;</span>
            @endif
        </div>
    @endif
</div>
    

@php
    use App\Helpers\LatexHelper;
    LatexHelper::resetCounters();
    
    // Opciones de visualización
    $mostrarArray = is_array(request('mostrar')) ? request('mostrar') : ['fuente', 'pistas', 'solucion', 'comentarios', 'year'];
@endphp

@if($problemas->count() > 0)
    @foreach($problemas as $problema)
        <div class="problema-card">
            <div class="problema-header">
                {{-- Nivel de dificultad - FUERA de problema-info --}}
                @if($problema->difficulty)
                    <div class="difficulty-badge" title="Dificultad: {{ $problema->difficulty }}/10">
                        {{ $problema->difficulty }}
                    </div>
                @endif
                
                {{-- Título, año y tags --}}
                <div class="problema-info">
                    <div class="problema-title-row">
                        <h3>Problema #{{ $problema->id }}</h3>
                        
                        {{-- Año académico --}}
                        @if($problema->school_year && in_array('year', $mostrarArray))
                            <span class="year-badge">
                                📚 {{ $problema->school_year }}
                            </span>
                        @endif
                    </div>
                    
                    {{-- Tags --}}
                    @if($problema->tags && $problema->tags->count() > 0)
                        <div class="problema-tags">
                            @foreach($problema->tags as $tag)
                                <span class="tag">{{ $tag->tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                {{-- Botones de acción --}}
                <div class="problema-actions">
                    @auth
                       @if(Auth::user()->canEditProblemas())
                            <a href="{{ route('problemas.edit', $problema->id) }}" class="btn-icon" title="Editar problema">
                                ✏️
                            </a>
                            
                            <button class="btn-icon btn-delete" 
                                    onclick="eliminarProblema({{ $problema->id }})" 
                                    title="Eliminar problema">
                                🗑️
                            </button>
                        @endif
                    @endauth
                    
                    @auth
                        <button class="btn-icon btn-carrito" 
                                data-problema-id="{{ $problema->id }}"
                                onclick="toggleCarrito({{ $problema->id }}, this)"
                                title="Añadir/Quitar del carrito">
                            <span class="carrito-icon">🛒</span>
                        </button>
                    @endauth
                </div>
            </div>
                
            {{-- Enunciado --}}
            <div class="problema-content">
                <strong>Enunciado:</strong>
                <div class="latex-content">{!! $problema->problem_html_processed !!}</div>
            </div>
            
            {{-- Pistas --}}
            @if($problema->hints && in_array('pistas', $mostrarArray))
                <div class="problema-content problema-pistas">
                    <strong>💡 Pistas:</strong>
                    <div class="latex-content pistas-content">
                        {!! nl2br(e($problema->hints)) !!}
                    </div>
                </div>
            @endif
            
            {{-- Solución --}}
            @if(in_array('solucion', $mostrarArray))
                <div class="problema-content">
                    <strong>Solución:</strong>
                    <div class="latex-content">{!! $problema->solution_html_processed !!}</div>
                </div>
            @endif
            
            {{-- Comentarios --}}
            @if($problema->comments && in_array('comentarios', $mostrarArray))
                <div class="problema-content problema-comentarios">
                    <strong>💬 Comentarios:</strong>
                    <div class="comentarios-content">
                        {!! nl2br(e($problema->comments)) !!}
                    </div>
                </div>
            @endif
            
            {{-- Fuente --}}
            @if($problema->source && in_array('fuente', $mostrarArray))
                <div class="problema-footer">
                    <small class="fuente-text">
                        📖 <strong>Fuente:</strong> {{ $problema->source }}
                    </small>
                </div>
            @endif
        </div>
    @endforeach



@else
    <div class="problema-card" style="text-align: center; color: #718096;">
        No se encontraron problemas con los filtros seleccionados.
    </div>
@endif



</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const topicInput = document.getElementById('topic-input');
    const topicSuggestions = document.getElementById('topic-suggestions');
    const topicTitleInput = document.getElementById('topic-title');
    
    console.log('Topic input:', topicInput);
    console.log('Topic title input:', topicTitleInput);
    
    if (!topicInput || !topicTitleInput) {
        console.error('Elementos no encontrados!');
        return;
    }
    
    let debounceTimer;
    
    topicInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const term = this.value;
        
        if (term.length < 3) {
            topicSuggestions.style.display = 'none';
            topicTitleInput.value = '';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`/api/topics/buscar?q=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Topics encontrados:', data);
                    if (data.length > 0) {
                        topicSuggestions.innerHTML = data.map(topic =>
                            `<div data-title="${topic}">${topic}</div>`
                        ).join('');
                        topicSuggestions.style.display = 'block';
                    } else {
                        topicSuggestions.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 300);
    });
    
    topicSuggestions.addEventListener('click', function(e) {
        if (e.target.tagName === 'DIV') {
            const selectedTitle = e.target.dataset.title;
            console.log('Tag seleccionado:', selectedTitle);
            topicInput.value = selectedTitle;
            topicTitleInput.value = selectedTitle;
            topicSuggestions.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!topicInput.contains(e.target) && !topicSuggestions.contains(e.target)) {
            topicSuggestions.style.display = 'none';
        }
    });
});



// Marcar problemas que ya están en el carrito al cargar
document.addEventListener('DOMContentLoaded', function() {
    @auth
    // Obtener IDs de problemas en el carrito
    fetch('{{ route("carrito.count") }}')
        .then(response => response.json())
        .then(data => {
            // Aquí podrías cargar qué problemas específicos están en el carrito
            // Por ahora dejamos la función toggle para manejarlo
        });
    @endauth
});

function toggleCarrito(problemaId, button) {
    @guest
        alert('Debes iniciar sesión para usar el carrito');
        window.location.href = '{{ route("login") }}';
        return;
    @endguest
    
    fetch('{{ route("carrito.toggle") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ problema_id: problemaId })
    })
    .then(response => response.json())
    .then(data => {
        // Actualizar el botón
        if (data.status === 'added') {
            button.classList.add('en-carrito');
            button.title = 'Quitar del carrito';
        } else {
            button.classList.remove('en-carrito');
            button.title = 'Añadir al carrito';
        }
        
        // Actualizar contador
        document.getElementById('carrito-count').textContent = data.count;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el carrito');
    });
}

function eliminarProblema(problemaId) {
    if (confirm('¿Estás seguro de que quieres eliminar este problema?')) {
        // TODO: Implementar eliminación
        alert('Funcionalidad de eliminación pendiente');
    }
}
function toggleMostrar(event) {
    event.preventDefault();
    const dropdown = document.getElementById('mostrar-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function updateMostrarText() {
    const checkboxes = document.querySelectorAll('#mostrar-dropdown input[type="checkbox"]:checked');
    const text = Array.from(checkboxes).map(cb => cb.parentElement.textContent.trim()).join(', ');
    document.getElementById('mostrar-text').textContent = text || 'Seleccionar opciones...';
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
    const wrapper = event.target.closest('.select-multiple-wrapper');
    if (!wrapper) {
        document.getElementById('mostrar-dropdown').style.display = 'none';
    }
});

// Actualizar texto al cargar
document.addEventListener('DOMContentLoaded', updateMostrarText);

</script>
@endsection