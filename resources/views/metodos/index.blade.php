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

    .filtros {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: center;
    }

    .filtros select, .filtros input[type="text"] {
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 6px;
        font-size: 1rem;
        background: white;
    }

    .filtros select {
        min-width: 200px;
    }

    .filtros .search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .filtros .search-wrap input {
        min-width: 220px;
        padding-right: 2rem;
    }

    .filtros .search-wrap .clear-search {
        position: absolute;
        right: 0.5rem;
        background: none;
        border: none;
        cursor: pointer;
        color: #a0aec0;
        font-size: 1rem;
        line-height: 1;
        padding: 0;
    }

    .filtros .search-wrap .clear-search:hover {
        color: #4a5568;
    }

    .metodos-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .metodos-table th {
        background: #4a5568;
        color: white;
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
    }

    .metodos-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .metodos-table tr:last-child td {
        border-bottom: none;
    }

    .metodos-table tr:hover td {
        background: #f7fafc;
    }

    .metodo-link {
        color: #4299e1;
        text-decoration: none;
        font-weight: 500;
    }

    .metodo-link:hover {
        text-decoration: underline;
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
    }

    .actions-cell {
        text-align: center;
        white-space: nowrap;
    }

    .btn-action {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem 0.4rem;
        border-radius: 4px;
        transition: background-color 0.2s;
        font-size: 1.1rem;
        text-decoration: none;
    }

    .btn-action:hover {
        background-color: #e2e8f0;
    }

    .btn-action.view:hover {
        background-color: #bee3f8;
    }

    .btn-action.download-tex {
        color: #059669;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .btn-action.download-tex:hover {
        background-color: #d1fae5;
    }

    .btn-action.edit {
        color: #4299e1;
        font-size: 1.1rem;
    }

    .btn-action.edit:hover {
        background-color: #bee3f8;
    }

    .btn-action.carrito {
        font-size: 1.1rem;
        transition: all 0.2s;
    }

    .btn-action.carrito:hover {
        background-color: #c6f6d5;
    }

    .btn-action.carrito.en-carrito {
        background-color: #48bb78;
        color: white;
        border-radius: 50%;
    }

    .empty-message {
        background: white;
        border-radius: 8px;
        padding: 3rem;
        text-align: center;
        color: #718096;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    @media (max-width: 640px) {
        .filtros {
            flex-direction: column;
        }
        .filtros select {
            min-width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="container metodos-container">
    <div class="metodos-header">
        <h1>Métodos</h1>
        <div style="display:flex;align-items:center;gap:0.75rem;">
            @if(Auth::user()->canEditProblemas())
                <a href="{{ route('metodos.create') }}" style="background:#4299e1;color:white;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">+ Añadir método</a>
            @endif
            @if(Auth::user()->isAdmin())
                <button id="btn-enable-delete" onclick="toggleDeleteMode()" title="Activar modo eliminación"
                        style="background:none;border:none;cursor:pointer;font-size:1.2rem;opacity:0.4;">🗑️</button>
            @endif
        </div>
    </div>

    <div class="filtros">
        <div class="search-wrap">
            <input type="text" id="filtro-search" placeholder="Buscar..." value="{{ request('search') }}">
            <button class="clear-search" id="btn-clear-search" title="Limpiar búsqueda" style="{{ request('search') ? '' : 'display:none;' }}">✕</button>
        </div>
        <select id="filtro-tema">
            <option value="">Todos los temas</option>
            @foreach($temas as $tema)
                <option value="{{ $tema->id }}" {{ request('tema_id') == $tema->id ? 'selected' : '' }}>{{ $tema->tema }}</option>
            @endforeach
        </select>

        <select id="filtro-subtema" {{ $subtemas->isEmpty() ? 'disabled' : '' }}>
            <option value="">Todos los subtemas</option>
            @foreach($subtemas as $subtema)
                <option value="{{ $subtema->id }}" {{ request('subtema_id') == $subtema->id ? 'selected' : '' }}>{{ $subtema->nombre }}</option>
            @endforeach
        </select>

        <select id="filtro-institution">
            <option value="">Todas las instituciones</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst }}" {{ request('institution') == $inst ? 'selected' : '' }}>{{ $inst }}</option>
            @endforeach
        </select>
        @if(Auth::user()->rol !== 'user')
        <button type="button" id="btn-add-bulk-metodos" style="background:#48bb78;color:white;border:none;border-radius:4px;padding:0.5rem 1rem;font-weight:600;cursor:pointer;font-size:0.9rem;" onclick="addFilteredMetodosToCarrito()">Al carrito</button>
        @endif
    </div>
    <div id="bulk-metodos-msg" style="display:none;padding:0.5rem 1rem;border-radius:6px;margin-top:0.5rem;font-size:0.9rem;"></div>

    @if($metodos->count() > 0)
        <table class="metodos-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tema</th>
                    <th>Subtema</th>
                    <th>Institución</th>
                    <th class="actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metodos as $metodo)
                    <tr>
                        <td>
                            <a href="{{ route('metodos.show', $metodo->id) }}" class="metodo-link">{{ $metodo->title }}</a>
                            @if(Auth::user()->canEditProblemas() && isset($metodosConErrores[$metodo->id]))
                                <span style="display:inline-block; background:#f59e0b; color:white; border-radius:10px; padding:1px 7px; font-size:0.75rem; font-weight:700; margin-left:0.4rem;" title="Tiene errores reportados">!</span>
                            @endif
                        </td>
                        <td><span class="tag">{{ $metodo->tema->tema }}</span></td>
                        <td>
                            @foreach($metodo->preloadedSubtemas as $subtema)
                                <span class="tag" style="margin: 2px;">{{ $subtema->nombre }}</span>
                            @endforeach
                        </td>
                        <td>{{ $metodo->institution ?? 'PIM' }}</td>
                        <td class="actions-cell">
                            <button class="btn-action carrito" data-metodo-id="{{ $metodo->id }}" onclick="toggleMetodoCarrito({{ $metodo->id }}, this)" title="Añadir al carrito">🛒</button>
                            <a href="{{ route('metodos.show', $metodo->id) }}" class="btn-action view" title="Ver">👁️</a>
                            @if(Auth::user()->canEditProblemas())
                                <a href="{{ route('metodos.edit', $metodo->id) }}" class="btn-action edit" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1.1em" height="1.1em" fill="currentColor" style="vertical-align:middle;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></a>
                            @endif
                            @if(Auth::user()->rol !== 'user')
                            <a href="{{ route('metodos.download-tex', $metodo->id) }}" class="btn-action download-tex" title="Descargar TEX">TEX⤓</a>
                            @endif
                            @if(Auth::user()->isAdmin())
                                <button class="btn-action btn-delete btn-delete-item" onclick="eliminarMetodo({{ $metodo->id }}, '{{ addslashes($metodo->title) }}')" title="Eliminar" style="color:#e53e3e;display:none;">🗑️</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-message">
            <p style="font-size: 1.2rem;">No se encontraron métodos{{ request('tema_id') ? ' con los filtros seleccionados' : '' }}.</p>
            @if(Auth::user()->canEditProblemas())
                <p>Puedes <a href="{{ route('metodos.create') }}" style="color:#4299e1;">añadir un método</a>.</p>
            @endif
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
const baseUrl = '{{ route("metodos.index") }}';
const apiSubtemasUrl = '{{ url("/api/subtemas") }}';

function buildFilterParams() {
    const params = new URLSearchParams();
    const search = document.getElementById('filtro-search').value.trim();
    const temaId = document.getElementById('filtro-tema').value;
    const subtemaId = document.getElementById('filtro-subtema').value;
    const institution = document.getElementById('filtro-institution').value;
    if (search) params.set('search', search);
    if (temaId) params.set('tema_id', temaId);
    if (subtemaId) params.set('subtema_id', subtemaId);
    if (institution) params.set('institution', institution);
    return params;
}

const searchInput = document.getElementById('filtro-search');
const clearSearchBtn = document.getElementById('btn-clear-search');

searchInput.addEventListener('input', function() {
    clearSearchBtn.style.display = this.value ? '' : 'none';
});

searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const params = buildFilterParams();
        window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
    }
});

clearSearchBtn.addEventListener('click', function() {
    searchInput.value = '';
    this.style.display = 'none';
    const params = buildFilterParams();
    window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
});

document.getElementById('filtro-tema').addEventListener('change', function() {
    const params = buildFilterParams();
    // Al cambiar tema, resetear subtema
    params.delete('subtema_id');
    window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
});

document.getElementById('filtro-subtema').addEventListener('change', function() {
    const params = buildFilterParams();
    window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
});

document.getElementById('filtro-institution').addEventListener('change', function() {
    const params = buildFilterParams();
    window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
});

// Carrito: marcar metodos que ya estan en el carrito
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("carrito.count") }}')
        .then(r => r.json())
        .then(data => {
            if (data.metodo_ids && data.metodo_ids.length > 0) {
                data.metodo_ids.forEach(id => {
                    const btn = document.querySelector('.btn-action.carrito[data-metodo-id="' + id + '"]');
                    if (btn) {
                        btn.classList.add('en-carrito');
                        btn.title = 'Quitar del carrito';
                    }
                });
            }
            // Actualizar contador del carrito en el navbar si existe
            const countEl = document.getElementById('carrito-count');
            if (countEl) countEl.textContent = data.count;
        })
        .catch(err => console.error('Error cargando estado carrito:', err));
});

function toggleMetodoCarrito(metodoId, button) {
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
        if (data.status === 'limit_exceeded') {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error al actualizar el carrito');
    });
}

function addFilteredMetodosToCarrito() {
    const params = buildFilterParams();
    const body = Object.fromEntries(params);
    body.type = 'metodos';
    const btn = document.getElementById('btn-add-bulk-metodos');
    if (btn) btn.disabled = true;
    fetch('{{ route("carrito.addBulk") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(data => {
        if (btn) btn.disabled = false;
        const msg = document.getElementById('bulk-metodos-msg');
        if (data.success) {
            msg.style.cssText = 'display:block;padding:0.5rem 1rem;border-radius:6px;margin-top:0.5rem;font-size:0.9rem;background:#c6f6d5;color:#276749;border:1px solid #68d391;';
            msg.textContent = '✅ ' + data.message;
            const countEl = document.getElementById('carrito-count');
            if (countEl) countEl.textContent = data.count;
        } else {
            msg.style.cssText = 'display:block;padding:0.5rem 1rem;border-radius:6px;margin-top:0.5rem;font-size:0.9rem;background:#fed7d7;color:#742a2a;border:1px solid #fc8181;';
            msg.textContent = '❌ ' + data.error;
        }
        setTimeout(() => { msg.style.display = 'none'; }, 5000);
    })
    .catch(() => { if (btn) btn.disabled = false; alert('Error al añadir al carrito'); });
}

let deleteMode = false;
function toggleDeleteMode() {
    deleteMode = !deleteMode;
    const btn = document.getElementById('btn-enable-delete');
    btn.style.opacity = deleteMode ? '1' : '0.4';
    btn.style.background = deleteMode ? '#dc354520' : 'none';
    btn.style.borderRadius = '4px';
    btn.title = deleteMode ? 'Desactivar modo eliminación' : 'Activar modo eliminación';
    document.querySelectorAll('.btn-delete-item').forEach(b => {
        b.style.display = deleteMode ? 'inline-block' : 'none';
    });
}

function eliminarMetodo(id, titulo) {
    if (confirm('¿Estás seguro de que quieres eliminar el método "' + titulo + '"?\n\nEsta acción no se puede deshacer.')) {
        fetch('{{ url("metodos") }}/' + id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el método');
        });
    }
}
</script>
@endsection
