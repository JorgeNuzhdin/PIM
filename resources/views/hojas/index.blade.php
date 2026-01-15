@extends('layouts.main')

@section('title', 'Mis Hojas de Problemas')

@section('content')
<div class="hojas-container">
    <h1>Hojas de Problemas</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="filters-container">
        <form action="{{ route('hojas.index') }}" method="GET" class="filters-form">
            <div class="filter-group">
                <label for="nombre_hoja">Nombre</label>
                <input type="text" name="nombre_hoja" id="nombre_hoja" value="{{ request('nombre_hoja') }}" placeholder="Buscar por nombre...">
            </div>

            <div class="filter-group">
                <label for="nombre_grupo">Grupo</label>
                <input type="text" name="nombre_grupo" id="nombre_grupo" value="{{ request('nombre_grupo') }}" placeholder="Buscar por grupo...">
            </div>

            <div class="filter-group">
                <label for="tema">Tema</label>
                <input type="text" name="tema" id="tema" value="{{ request('tema') }}" placeholder="Buscar por tema...">
            </div>

            <div class="filter-group">
                <label for="year">Año</label>
                <input type="number" name="year" id="year" value="{{ request('year') }}" placeholder="2024" min="2000" max="2100">
            </div>

            @if(Auth::user()->rol === 'admin' && $profesores)
                <div class="filter-group">
                    <label for="user_id">Profesor</label>
                    <select name="user_id" id="user_id">
                        <option value="">Todos</option>
                        @foreach($profesores as $profesor)
                            <option value="{{ $profesor->id }}" {{ request('user_id') == $profesor->id ? 'selected' : '' }}>
                                {{ $profesor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('hojas.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla de hojas --}}
    <div class="table-container">
        <table class="hojas-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Grupo</th>
                    <th>Tema</th>
                    <th>Año</th>
                    @if(Auth::user()->rol === 'admin')
                        <th>Profesor</th>
                    @endif
                    <th>Problemas</th>
                    <th>Creada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hojas as $hoja)
                    <tr>
                        <td>{{ $hoja->nombre_hoja }}</td>
                        <td>{{ $hoja->nombre_grupo ?? '-' }}</td>
                        <td>{{ $hoja->tema ?? '-' }}</td>
                        <td>{{ $hoja->year ?? '-' }}</td>
                        @if(Auth::user()->rol === 'admin')
                            <td>{{ $hoja->user->name }}</td>
                        @endif
                        <td>{{ $hoja->problems->count() }}</td>
                        <td>{{ $hoja->created_at->format('d/m/Y') }}</td>
                        <td class="actions-cell">
                            <button class="btn btn-sm btn-primary" onclick="cargarHoja({{ $hoja->id }})">
                                Cargar
                            </button>
                            <form action="{{ route('hojas.destroy', $hoja) }}" method="POST" style="display: inline;" 
                                  onsubmit="return confirm('¿Eliminar esta hoja?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ Auth::user()->rol === 'admin' ? 8 : 7 }}" class="empty-row">
                            No se encontraron hojas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="pagination-container">
        {{ $hojas->withQueryString()->links() }}
    </div>
</div>
@endsection

@section('scripts')
<style>
.hojas-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.hojas-container h1 {
    margin-bottom: 1.5rem;
    color: #333;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Filtros */
.filters-container {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #555;
}

.filter-group input,
.filter-group select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.875rem;
    min-width: 150px;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.15);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    text-decoration: none;
    display: inline-block;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Tabla */
.table-container {
    overflow-x: auto;
}

.hojas-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.hojas-table th,
.hojas-table td {
    padding: 0.875rem 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.hojas-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.hojas-table tbody tr:hover {
    background-color: #f8f9fa;
}

.empty-row {
    text-align: center;
    color: #888;
    padding: 2rem !important;
}

.actions-cell {
    white-space: nowrap;
}

.actions-cell .btn {
    margin-right: 0.25rem;
}

/* Paginación */
.pagination-container {
    margin-top: 1.5rem;
    display: flex;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }
    
    .filter-group input,
    .filter-group select {
        min-width: 100%;
    }
}
</style>

<script>
function cargarHoja(hojaId) {
    // Verificar si hay items en el carrito actual
    const carritoCount = parseInt(document.getElementById('carrito-count')?.textContent || '0');
    
    let accion = 'reemplazar';
    
    if (carritoCount > 0) {
        const opcion = prompt(
            'El carrito tiene ' + carritoCount + ' problema(s).\n\n' +
            'Escribe:\n' +
            '  1 = Añadir los problemas de la hoja al carrito actual\n' +
            '  2 = Vaciar el carrito y cargar solo los de la hoja\n' +
            '  (o cancela para no hacer nada)'
        );
        
        if (opcion === null) {
            return; // Cancelado
        }
        
        if (opcion === '1') {
            accion = 'añadir';
        } else if (opcion === '2') {
            accion = 'reemplazar';
        } else {
            alert('Opción no válida');
            return;
        }
    }
    
    fetch(`/hojas/${hojaId}/load`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (accion === 'reemplazar') {
                    // Primero vaciar el carrito
                    fetch('{{ route("carrito.limpiar") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(() => {
                        añadirProblemasAlCarrito(data.problema_ids, data.nombre_hoja);
                    });
                } else {
                    añadirProblemasAlCarrito(data.problema_ids, data.nombre_hoja);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la hoja');
        });
}

function añadirProblemasAlCarrito(problemaIds, nombreHoja) {
    // Añadir cada problema al carrito (toggle solo añade si no existe)
    const promises = problemaIds.map(problemaId => 
        fetch('{{ route("carrito.toggle") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ problema_id: problemaId, accion: 'añadir' })
        })
    );
    
    Promise.all(promises)
        .then(() => {
            alert('Hoja "' + nombreHoja + '" cargada correctamente');
            window.location.href = '{{ route("carrito.index") }}';
        });
}
</script>
@endsection