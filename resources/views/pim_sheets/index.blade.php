@extends('layouts.main')

@section('title', 'Hojas de Problemas')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/pagination.css?v=3') }}">
<style>
    .filters-container {
        background: #f7fafc;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #2d3748;
    }

    .filter-group input,
    .filter-group select {
        padding: 0.5rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .filter-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.2s;
    }

    .btn-primary {
        background-color: #4299e1;
        color: white;
    }

    .btn-primary:hover {
        background-color: #3182ce;
    }

    .btn-secondary {
        background-color: #718096;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #4a5568;
    }

    .btn-success {
        background-color: #48bb78;
        color: white;
    }

    .btn-success:hover {
        background-color: #38a169;
    }

    .sheets-table {
        width: 100%;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .sheets-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .sheets-table th {
        background-color: #4a5568;
        color: white;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
    }

    .sheets-table th:hover {
        background-color: #2d3748;
    }

    .sheets-table th.sortable::after {
        content: ' ⇅';
        opacity: 0.5;
    }

    .sheets-table th.sorted-asc::after {
        content: ' ↑';
        opacity: 1;
    }

    .sheets-table th.sorted-desc::after {
        content: ' ↓';
        opacity: 1;
    }

    .sheets-table td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .sheets-table tbody tr {
        cursor: pointer;
        transition: background-color 0.15s;
    }

    .sheets-table tbody tr:hover {
        background-color: #f7fafc;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 2rem;
        border-radius: 8px;
        width: 400px;
        text-align: center;
    }

    .modal-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
    }

    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background-color: #c6f6d5;
        color: #22543d;
        border: 1px solid #9ae6b4;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Hojas de Problemas</h1>
        @auth
            @if(Auth::user()->canEditProblemas())
                <a href="{{ route('pim-sheets.create') }}" class="btn btn-success">+ Subir Nueva Hoja</a>
            @endif
        @endauth
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filtros -->
    <div class="filters-container">
        <form method="GET" action="{{ route('pim-sheets.index') }}" id="filterForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Buscar por título</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Título de la hoja...">
                </div>

                <div class="filter-group">
                    <label for="year">Año</label>
                    <select id="year" name="year">
                        <option value="">Todos los años</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="planet">Grupo</label>
                    <select id="planet" name="planet">
                        <option value="">Todos los grupos</option>
                        @foreach($planets as $planet)
                            <option value="{{ $planet }}" {{ request('planet') == $planet ? 'selected' : '' }}>
                                {{ $planet }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="institution">Institución</label>
                    <select id="institution" name="institution">
                        <option value="">Todas las instituciones</option>
                        @foreach($institutions as $institution)
                            <option value="{{ $institution }}" {{ request('institution') == $institution ? 'selected' : '' }}>
                                {{ $institution }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="theme">Tema</label>
                    <select id="theme" name="theme">
                        <option value="">Todos los temas</option>
                        @foreach($temas as $tema)
                            <option value="{{ $tema->id }}" {{ request('theme') == $tema->id ? 'selected' : '' }}>
                                {{ $tema->tema }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <input type="hidden" name="sort_by" id="sort_by" value="{{ request('sort_by', 'date_year') }}">
            <input type="hidden" name="sort_order" id="sort_order" value="{{ request('sort_order', 'desc') }}">

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                <a href="{{ route('pim-sheets.index') }}" class="btn btn-secondary">Limpiar Filtros</a>
            </div>
        </form>
    </div>

    <!-- Paginación superior -->
    @if($sheets->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination">
                {{-- Primera página --}}
                @if ($sheets->currentPage() > 1)
                    <a href="{{ $sheets->appends(request()->query())->url(1) }}" class="page-item" title="Primera página">&laquo;&laquo;</a>
                @else
                    <span class="page-item disabled">&laquo;&laquo;</span>
                @endif

                {{-- Página anterior --}}
                @if ($sheets->onFirstPage())
                    <span class="page-item disabled">&laquo;</span>
                @else
                    <a href="{{ $sheets->appends(request()->query())->previousPageUrl() }}" class="page-item">&laquo;</a>
                @endif

                {{-- Números de página --}}
                @php
                    $current = $sheets->currentPage();
                    $last = $sheets->lastPage();
                    $start = max(1, $current - 4);
                    $end = min($last, $current + 4);
                @endphp

                {{-- Primera página siempre visible --}}
                @if ($start > 1)
                    <a href="{{ $sheets->appends(request()->query())->url(1) }}" class="page-item">1</a>
                    @if ($start > 2)
                        <span class="page-item disabled">...</span>
                    @endif
                @endif

                {{-- Rango de páginas alrededor de la actual --}}
                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $current)
                        <span class="page-item active">{{ $page }}</span>
                    @else
                        <a href="{{ $sheets->appends(request()->query())->url($page) }}" class="page-item">{{ $page }}</a>
                    @endif
                @endfor

                {{-- Última página siempre visible --}}
                @if ($end < $last)
                    @if ($end < $last - 1)
                        <span class="page-item disabled">...</span>
                    @endif
                    <a href="{{ $sheets->appends(request()->query())->url($last) }}" class="page-item">{{ $last }}</a>
                @endif

                {{-- Página siguiente --}}
                @if ($sheets->hasMorePages())
                    <a href="{{ $sheets->appends(request()->query())->nextPageUrl() }}" class="page-item">&raquo;</a>
                @else
                    <span class="page-item disabled">&raquo;</span>
                @endif

                {{-- Última página --}}
                @if ($sheets->currentPage() < $sheets->lastPage())
                    <a href="{{ $sheets->appends(request()->query())->url($sheets->lastPage()) }}" class="page-item" title="Última página">&raquo;&raquo;</a>
                @else
                    <span class="page-item disabled">&raquo;&raquo;</span>
                @endif
            </div>
        </div>
    @endif

    <!-- Tabla de sheets -->
    <div class="sheets-table">
        <table>
            <thead>
                <tr>
                    <th class="sortable {{ request('sort_by') == 'title' ? 'sorted-' . request('sort_order', 'desc') : '' }}"
                        data-sort="title">
                        Título
                    </th>
                    <th class="sortable {{ request('sort_by') == 'date_year' ? 'sorted-' . request('sort_order', 'desc') : '' }}"
                        data-sort="date_year">
                        Año
                    </th>
                    <th class="sortable {{ request('sort_by') == 'planet' ? 'sorted-' . request('sort_order', 'desc') : '' }}"
                        data-sort="planet">
                        Grupo
                    </th>
                    <th class="sortable {{ request('sort_by') == 'institution' ? 'sorted-' . request('sort_order', 'desc') : '' }}"
                        data-sort="institution">
                        Institución
                    </th>
                    <th>Tema</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sheets as $sheet)
                    <tr onclick="showDownloadModal({{ $sheet->id }}, '{{ addslashes($sheet->title) }}', {{ $sheet->tex_sols ? 'true' : 'false' }}, {{ $sheet->tex_no_sols ? 'true' : 'false' }})">
                        <td><strong>{{ $sheet->title }}</strong></td>
                        <td>{{ $sheet->date_year }}</td>
                        <td>{{ $sheet->planet ?? '-' }}</td>
                        <td>{{ $sheet->institution ?? '-' }}</td>
                        <td>{{ $sheet->tema->tema ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: #718096;">
                            No se encontraron hojas de problemas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de descarga -->
<div id="downloadModal" class="modal">
    <div class="modal-content">
        <h2>Descargar Hoja</h2>
        <p id="modalSheetTitle" style="margin: 1rem 0; color: #718096;"></p>
        <p>¿Qué versión deseas descargar?</p>
        <div class="modal-buttons">
            <button id="btnWithSolutions" class="btn btn-success" onclick="downloadSheet(true)">Con Soluciones</button>
            <button id="btnWithoutSolutions" class="btn btn-primary" onclick="downloadSheet(false)">Sin Soluciones</button>
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentSheetId = null;
    let hasSolutions = false;
    let hasNoSolutions = false;

    // Ordenamiento de columnas
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            const currentSortBy = document.getElementById('sort_by').value;
            const currentSortOrder = document.getElementById('sort_order').value;

            // Si es la misma columna, invertir el orden
            if (sortBy === currentSortBy) {
                document.getElementById('sort_order').value = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                // Si es nueva columna, ordenar descendente por defecto
                document.getElementById('sort_by').value = sortBy;
                document.getElementById('sort_order').value = 'desc';
            }

            document.getElementById('filterForm').submit();
        });
    });

    // Modal de descarga
    function showDownloadModal(sheetId, title, withSols, withoutSols) {
        currentSheetId = sheetId;
        hasSolutions = withSols;
        hasNoSolutions = withoutSols;

        document.getElementById('modalSheetTitle').textContent = title;

        // Deshabilitar botones si no hay archivos disponibles
        document.getElementById('btnWithSolutions').disabled = !hasSolutions;
        document.getElementById('btnWithoutSolutions').disabled = !hasNoSolutions;

        if (!hasSolutions) {
            document.getElementById('btnWithSolutions').style.opacity = '0.5';
            document.getElementById('btnWithSolutions').style.cursor = 'not-allowed';
        } else {
            document.getElementById('btnWithSolutions').style.opacity = '1';
            document.getElementById('btnWithSolutions').style.cursor = 'pointer';
        }

        if (!hasNoSolutions) {
            document.getElementById('btnWithoutSolutions').style.opacity = '0.5';
            document.getElementById('btnWithoutSolutions').style.cursor = 'not-allowed';
        } else {
            document.getElementById('btnWithoutSolutions').style.opacity = '1';
            document.getElementById('btnWithoutSolutions').style.cursor = 'pointer';
        }

        document.getElementById('downloadModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('downloadModal').style.display = 'none';
        currentSheetId = null;
    }

    function downloadSheet(withSolutions) {
        if (currentSheetId) {
            const url = `/pim-sheets/${currentSheetId}/download?with_solutions=${withSolutions ? '1' : '0'}`;
            window.location.href = url;
            closeModal();
        }
    }

    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('downloadModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>
@endsection
