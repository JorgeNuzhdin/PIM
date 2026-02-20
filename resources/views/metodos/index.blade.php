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

    .filtros select {
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 6px;
        font-size: 1rem;
        background: white;
        min-width: 200px;
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

    .btn-action.download-pdf {
        color: #e53e3e;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .btn-action.download-pdf:hover {
        background-color: #fed7d7;
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
        @if(Auth::user()->canEditProblemas())
            <a href="{{ route('metodos.create') }}" style="background:#4299e1;color:white;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">+ Añadir método</a>
        @endif
    </div>

    @if(session('success'))
        <div style="background:#c6f6d5;color:#276749;padding:1rem;border-radius:4px;margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="filtros">
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
    </div>

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
                        <td><a href="{{ route('metodos.show', $metodo->id) }}" class="metodo-link">{{ $metodo->title }}</a></td>
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
                            <a href="{{ route('metodos.download-pdf', $metodo->id) }}" class="btn-action download-pdf" title="Descargar PDF">PDF⤓</a>
                            @if(Auth::user()->isAdmin())
                                <button class="btn-action btn-delete" onclick="eliminarMetodo({{ $metodo->id }}, '{{ addslashes($metodo->title) }}')" title="Eliminar" style="color:#e53e3e;">🗑️</button>
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
    const temaId = document.getElementById('filtro-tema').value;
    const subtemaId = document.getElementById('filtro-subtema').value;
    const institution = document.getElementById('filtro-institution').value;
    if (temaId) params.set('tema_id', temaId);
    if (subtemaId) params.set('subtema_id', subtemaId);
    if (institution) params.set('institution', institution);
    return params;
}

document.getElementById('filtro-tema').addEventListener('change', function() {
    const params = new URLSearchParams();
    const temaId = this.value;
    if (temaId) params.set('tema_id', temaId);
    const institution = document.getElementById('filtro-institution').value;
    if (institution) params.set('institution', institution);
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
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error al actualizar el carrito');
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
