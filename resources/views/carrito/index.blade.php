@extends('layouts.main')

@section('title', 'Carrito')


@section('styles')
.carrito-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem;
}
.carrito-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: #718096;
}
.carrito-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: move;
    transition: all 0.2s;
}
.carrito-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.carrito-item.dragging {
    opacity: 0.5;
}
.drag-handle {
    font-size: 1.5rem;
    color: #cbd5e0;
    cursor: grab;
}
.drag-handle:active {
    cursor: grabbing;
}
.item-content {
    flex: 1;
}
.item-title {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
}
.item-preview {
    color: #718096;
    font-size: 0.9rem;
}
.btn-remove {
    background: #fff5f5;
    border: 1px solid #fc8181;
    color: #e53e3e;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-remove:hover {
    background: #e53e3e;
    color: white;
}

/* Botones del carrito */
.carrito-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.btn-carrito {
    color: white;
    border: none;
    padding: 0.6rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-carrito:hover {
    transform: translateY(-2px);
}

.btn-guardar {
    background: #3182ce;
}
.btn-guardar:hover {
    background: #2c5282;
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.4);
}

.btn-hojas {
    background: #4299e1;
}
.btn-hojas:hover {
    background: #3182ce;
    box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
}

.btn-tex {
    background: #63b3ed;
}
.btn-tex:hover {
    background: #4299e1;
    box-shadow: 0 4px 12px rgba(99, 179, 237, 0.4);
}

.btn-pdf {
    background: #e53e3e;
}
.btn-pdf:hover {
    background: #c53030;
    box-shadow: 0 4px 12px rgba(229, 62, 62, 0.4);
}

.btn-html {
    background: #90cdf4;
    color: #2a4365;
}
.btn-html:hover {
    background: #63b3ed;
    color: white;
    box-shadow: 0 4px 12px rgba(144, 205, 244, 0.4);
}

.btn-limpiar {
    background: #e53e3e;
}
.btn-limpiar:hover {
    background: #c53030;
    box-shadow: 0 4px 12px rgba(229, 62, 62, 0.4);
}

/* Responsive */
@media (max-width: 640px) {
    .carrito-buttons {
        flex-direction: column;
    }
    .btn-carrito {
        justify-content: center;
    }
}
@endsection

@section('content')
<div class="carrito-container">
    <h1 style="margin-bottom: 2rem; color: #2d3748;">🛒 Mi Carrito</h1>

    @if($items->count() > 0)
        @include('hojas._carrito_guardar')
        <div class="carrito-buttons">
            @auth
                @if(in_array(Auth::user()->rol, ['admin', 'editor', 'profesor']))
                    <button type="button" class="btn-carrito btn-guardar" onclick="abrirModalGuardar()">
                        💾 Guardar hoja
                    </button>
                    <a href="{{ route('hojas.index') }}" class="btn-carrito btn-hojas">
                        📂 Mis hojas
                    </a>
                @endif
            @endauth
            @if(Auth::user()->rol !== 'user')
            <a href="{{ route('carrito.descargar.tex') }}" class="btn-carrito btn-tex">
                 TEX ⤓
            </a>
            @endif
            <a href="{{ route('carrito.descargar.pdf', ['solutions' => '1']) }}" class="btn-carrito btn-pdf" title="PDF con soluciones (para profesores)">
                 PDF prof ⤓
            </a>
            <a href="{{ route('carrito.descargar.pdf', ['solutions' => '0']) }}" class="btn-carrito btn-pdf" style="background-color: #718096;" title="PDF sin soluciones (para alumnos)">
                 PDF alum ⤓
            </a>
            <a href="{{ route('carrito.presentacion') }}" class="btn-carrito btn-html">
                 HTML
            </a>
            <button class="btn-carrito btn-limpiar" onclick="limpiarCarrito()">
                🗑️ Limpiar
            </button>
        </div>
    @endif

    @if($items->count() > 0)
        <div id="carrito-list">
            @foreach($items as $item)
                <div class="carrito-item" data-id="{{ $item->id }}" data-tipo="{{ $item->isMetodo() ? 'metodo' : 'problema' }}" data-problema-id="{{ $item->problema_id }}" data-metodo-id="{{ $item->metodo_id }}" draggable="true">
                    <div class="drag-handle">☰</div>
                    <div class="item-content">
                        @if($item->isMetodo())
                            <div class="item-title" style="color: #2b6cb0;">📘 Método: {{ $item->metodo->title }}</div>
                            <div class="item-preview">
                                @php
                                    $text = strip_tags(\App\Helpers\LatexHelper::toHtml($item->metodo->method_tex));
                                    $text = preg_replace('/\\\\begin\{tikzpicture\}.*?\\\\end\{tikzpicture\}/s', '[figura]', $text);
                                    if (strlen($text) > 150) $text = substr($text, 0, 150) . '...';
                                @endphp
                                {!! $text !!}
                            </div>
                        @else
                            <div class="item-title">Problema #{{ $item->problema->id }}</div>
                            <div class="item-preview">
                                @php
                                    $text = strip_tags($item->problema->problem_html_processed);
                                    $text = preg_replace('/\\\\begin\{tikzpicture\}.*?\\\\end\{tikzpicture\}/s', '[figura]', $text);
                                    $limit = 150;
                                    if (strlen($text) > $limit) {
                                        $cut = substr($text, 0, $limit);
                                        $dollarCount = substr_count($cut, '$') - substr_count($cut, '\\$');
                                        if ($dollarCount % 2 !== 0) {
                                            $nextDollar = strpos($text, '$', $limit);
                                            if ($nextDollar !== false && $nextDollar < $limit + 100) {
                                                $cut = substr($text, 0, $nextDollar + 1);
                                            } else {
                                                $lastDollar = strrpos($cut, '$');
                                                $cut = substr($text, 0, $lastDollar);
                                            }
                                        }
                                        if (substr_count($cut, '\\(') > substr_count($cut, '\\)')) {
                                            $nextClose = strpos($text, '\\)', strlen($cut));
                                            if ($nextClose !== false && $nextClose < $limit + 100) {
                                                $cut = substr($text, 0, $nextClose + 2);
                                            }
                                        }
                                        if (substr_count($cut, '\\[') > substr_count($cut, '\\]')) {
                                            $nextClose = strpos($text, '\\]', strlen($cut));
                                            if ($nextClose !== false && $nextClose < $limit + 200) {
                                                $cut = substr($text, 0, $nextClose + 2);
                                            } else {
                                                $lastOpen = strrpos($cut, '\\[');
                                                if ($lastOpen !== false) {
                                                    $cut = substr($text, 0, $lastOpen);
                                                }
                                            }
                                        }
                                        $text = $cut . '...';
                                    }
                                @endphp
                                {!! $text !!}
                            </div>
                        @endif
                    </div>
                    <button class="btn-remove" onclick="removeFromCarrito(this)" title="Quitar del carrito">
                        🗑️
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div class="carrito-empty">
            <p style="font-size: 3rem; margin-bottom: 1rem;">🛒</p>
            <p style="font-size: 1.2rem;">Tu carrito está vacío</p>
            <p style="margin-top: 0.5rem;">
                <a href="{{ route('problemas.index') }}" style="color: #4299e1; text-decoration: underline;">Ver problemas</a>
                &nbsp;|&nbsp;
                <a href="{{ route('metodos.index') }}" style="color: #4299e1; text-decoration: underline;">Ver métodos</a>
            </p>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
let draggedElement = null;

document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.carrito-item');
    
    items.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragend', handleDragEnd);
    });
});

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    
    const afterElement = getDragAfterElement(e.currentTarget.parentNode, e.clientY);
    if (afterElement == null) {
        e.currentTarget.parentNode.appendChild(draggedElement);
    } else {
        e.currentTarget.parentNode.insertBefore(draggedElement, afterElement);
    }
    
    return false;
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    updateOrder();
    return false;
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.carrito-item:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateOrder() {
    const items = document.querySelectorAll('.carrito-item');
    const order = Array.from(items).map(item => item.dataset.id);
    
    fetch('{{ route("carrito.updateOrder") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ order: order })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Orden actualizado');
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function removeFromCarrito(button) {
    const item = button.closest('.carrito-item');
    const tipo = item.dataset.tipo;
    const label = tipo === 'metodo' ? 'este método' : 'este problema';

    if (confirm('¿Quitar ' + label + ' del carrito?')) {
        const body = tipo === 'metodo'
            ? { metodo_id: parseInt(item.dataset.metodoId) }
            : { problema_id: parseInt(item.dataset.problemaId) };

        fetch('{{ route("carrito.toggle") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(body)
        })
        .then(response => response.json())
        .then(data => {
            item.remove();
            const countEl = document.getElementById('carrito-count');
            if (countEl) countEl.textContent = data.count;
            if (data.count === 0) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar del carrito');
        });
    }
}

function limpiarCarrito() {
    if (confirm('¿Estás seguro de que quieres vaciar todo el carrito?')) {
        // Crear un formulario y enviarlo
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("carrito.limpiar") }}';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection