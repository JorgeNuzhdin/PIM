@extends('layouts.main')

@section('title', 'Editor de Tags')

@section('styles')
<style>
    .container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem;
    }

    .stats {
        text-align: center;
        color: #718096;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
    }

    .filters {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .filters-row {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #4a5568;
    }

    .filter-group input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 1rem;
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
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

    .btn-danger {
        background-color: #e53e3e;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c53030;
    }

    .btn-success {
        background-color: #48bb78;
        color: white;
    }

    .btn-success:hover {
        background-color: #38a169;
    }

    .btn-small {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }

    .tags-table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .tags-table {
        width: 100%;
        border-collapse: collapse;
    }

    .tags-table th,
    .tags-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .tags-table th {
        background-color: #4a5568;
        color: white;
        font-weight: 600;
    }

    .tags-table th.sortable {
        cursor: pointer;
        user-select: none;
    }

    .tags-table th.sortable:hover {
        background-color: #2d3748;
    }

    .tags-table th .sort-icon {
        margin-left: 0.5rem;
        opacity: 0.5;
    }

    .tags-table th.sorted .sort-icon {
        opacity: 1;
    }

    .tags-table tbody tr:hover {
        background-color: #f7fafc;
    }

    .tags-table .count-cell {
        text-align: center;
        font-weight: 600;
        color: #4299e1;
    }

    .tags-table .actions-cell {
        text-align: right;
        white-space: nowrap;
    }

    .tags-table .actions-cell button {
        margin-left: 0.25rem;
    }

    .tag-title-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tag-title-cell input {
        flex: 1;
        padding: 0.25rem 0.5rem;
        border: 1px solid #4299e1;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .edit-mode .tag-title-text {
        display: none;
    }

    .edit-mode .tag-title-input {
        display: block;
    }

    .tag-title-input {
        display: none;
    }

    /* Modal de confirmación */
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
        max-width: 90%;
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

    .alert-error {
        background-color: #fed7d7;
        color: #742a2a;
        border: 1px solid #fc8181;
    }
</style>
@endsection

@section('content')
<div class="container">
    <h1 style="margin-bottom: 1rem;">Editor de Tags</h1>

    <div class="stats">
        Total de tags: <strong>{{ $totalTags }}</strong>
        @if(request('search'))
            | Encontrados: <strong>{{ $tags->total() }}</strong>
        @endif
    </div>

    <div id="alertContainer"></div>

    <div class="filters">
        <form method="GET" action="{{ route('tags.index') }}" id="filterForm">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search">Buscar por título</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Escribe para buscar...">
                </div>

                <input type="hidden" name="sort_by" id="sort_by" value="{{ request('sort_by', 'title') }}">
                <input type="hidden" name="sort_order" id="sort_order" value="{{ request('sort_order', 'asc') }}">

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="{{ route('tags.index') }}" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Paginación superior -->
    @if($tags->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination">
                @if ($tags->currentPage() > 1)
                    <a href="{{ $tags->appends(request()->query())->url(1) }}" class="page-item" title="Primera página">&laquo;&laquo;</a>
                @else
                    <span class="page-item disabled">&laquo;&laquo;</span>
                @endif

                @if ($tags->onFirstPage())
                    <span class="page-item disabled">&laquo;</span>
                @else
                    <a href="{{ $tags->appends(request()->query())->previousPageUrl() }}" class="page-item">&laquo;</a>
                @endif

                @php
                    $current = $tags->currentPage();
                    $last = $tags->lastPage();
                    $start = max(1, $current - 4);
                    $end = min($last, $current + 4);
                @endphp

                @if ($start > 1)
                    <a href="{{ $tags->appends(request()->query())->url(1) }}" class="page-item">1</a>
                    @if ($start > 2)
                        <span class="page-item disabled">...</span>
                    @endif
                @endif

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $current)
                        <span class="page-item active">{{ $page }}</span>
                    @else
                        <a href="{{ $tags->appends(request()->query())->url($page) }}" class="page-item">{{ $page }}</a>
                    @endif
                @endfor

                @if ($end < $last)
                    @if ($end < $last - 1)
                        <span class="page-item disabled">...</span>
                    @endif
                    <a href="{{ $tags->appends(request()->query())->url($last) }}" class="page-item">{{ $last }}</a>
                @endif

                @if ($tags->hasMorePages())
                    <a href="{{ $tags->appends(request()->query())->nextPageUrl() }}" class="page-item">&raquo;</a>
                @else
                    <span class="page-item disabled">&raquo;</span>
                @endif

                @if ($tags->currentPage() < $tags->lastPage())
                    <a href="{{ $tags->appends(request()->query())->url($tags->lastPage()) }}" class="page-item" title="Última página">&raquo;&raquo;</a>
                @else
                    <span class="page-item disabled">&raquo;&raquo;</span>
                @endif
            </div>
        </div>
    @endif

    <div class="tags-table-container">
        <table class="tags-table">
            <thead>
                <tr>
                    <th class="sortable {{ request('sort_by', 'title') == 'title' ? 'sorted' : '' }}" data-sort="title">
                        Título
                        <span class="sort-icon">
                            @if(request('sort_by', 'title') == 'title')
                                {{ request('sort_order', 'asc') == 'asc' ? '▲' : '▼' }}
                            @else
                                ▲
                            @endif
                        </span>
                    </th>
                    <th class="sortable {{ request('sort_by') == 'count' ? 'sorted' : '' }}" data-sort="count" style="width: 150px; text-align: center;">
                        Apariciones
                        <span class="sort-icon">
                            @if(request('sort_by') == 'count')
                                {{ request('sort_order') == 'asc' ? '▲' : '▼' }}
                            @else
                                ▲
                            @endif
                        </span>
                    </th>
                    @if($isAdmin)
                    <th style="width: 150px; text-align: right;">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                    <tr id="tag-row-{{ $tag->id }}" data-id="{{ $tag->id }}" data-title="{{ $tag->title }}">
                        <td>
                            <div class="tag-title-cell">
                                <span class="tag-title-text">{{ $tag->title }}</span>
                                <input type="text" class="tag-title-input" value="{{ $tag->title }}">
                            </div>
                        </td>
                        <td class="count-cell">{{ $tag->count }}</td>
                        @if($isAdmin)
                        <td class="actions-cell">
                            <button class="btn btn-primary btn-small btn-edit" onclick="toggleEdit({{ $tag->id }})">Editar</button>
                            <button class="btn btn-success btn-small btn-save" style="display: none;" onclick="saveTag({{ $tag->id }})">Guardar</button>
                            <button class="btn btn-secondary btn-small btn-cancel" style="display: none;" onclick="cancelEdit({{ $tag->id }})">Cancelar</button>
                            <button class="btn btn-danger btn-small btn-delete" onclick="confirmDelete({{ $tag->id }}, '{{ addslashes($tag->title) }}')">Borrar</button>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 3 : 2 }}" style="text-align: center; padding: 2rem; color: #718096;">
                            No se encontraron tags.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h2>Confirmar eliminación</h2>
        <p>¿Estás seguro de que deseas eliminar el tag "<strong id="deleteTagTitle"></strong>"?</p>
        <p style="color: #e53e3e; font-size: 0.9rem;">Esta acción eliminará el tag y todas sus asociaciones con problemas.</p>
        <div class="modal-buttons">
            <button class="btn btn-danger" onclick="deleteTag()">Eliminar</button>
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let deleteTagId = null;

    // Ordenamiento de columnas
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            const currentSortBy = document.getElementById('sort_by').value;
            const currentSortOrder = document.getElementById('sort_order').value;

            if (sortBy === currentSortBy) {
                document.getElementById('sort_order').value = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                document.getElementById('sort_by').value = sortBy;
                document.getElementById('sort_order').value = 'asc';
            }

            document.getElementById('filterForm').submit();
        });
    });

    // Modo edición
    function toggleEdit(id) {
        const row = document.getElementById('tag-row-' + id);
        row.classList.add('edit-mode');

        row.querySelector('.btn-edit').style.display = 'none';
        row.querySelector('.btn-delete').style.display = 'none';
        row.querySelector('.btn-save').style.display = 'inline-block';
        row.querySelector('.btn-cancel').style.display = 'inline-block';

        const input = row.querySelector('.tag-title-input');
        input.focus();
        input.select();
    }

    function cancelEdit(id) {
        const row = document.getElementById('tag-row-' + id);
        row.classList.remove('edit-mode');

        row.querySelector('.btn-edit').style.display = 'inline-block';
        row.querySelector('.btn-delete').style.display = 'inline-block';
        row.querySelector('.btn-save').style.display = 'none';
        row.querySelector('.btn-cancel').style.display = 'none';

        // Restaurar valor original
        const originalTitle = row.dataset.title;
        row.querySelector('.tag-title-input').value = originalTitle;
    }

    function saveTag(id) {
        const row = document.getElementById('tag-row-' + id);
        const newTitle = row.querySelector('.tag-title-input').value.trim();

        if (!newTitle) {
            showAlert('El título no puede estar vacío', 'error');
            return;
        }

        fetch(`{{ url('tags') }}/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ title: newTitle })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');

                // Actualizar la fila
                row.dataset.title = newTitle;
                row.querySelector('.tag-title-text').textContent = newTitle;
                cancelEdit(id);
            } else {
                showAlert(data.error || 'Error al actualizar', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al actualizar el tag', 'error');
        });
    }

    // Eliminar tag
    function confirmDelete(id, title) {
        deleteTagId = id;
        document.getElementById('deleteTagTitle').textContent = title;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteTagId = null;
    }

    function deleteTag() {
        if (!deleteTagId) return;

        fetch(`{{ url('tags') }}/${deleteTagId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');

                // Eliminar la fila de la tabla
                const row = document.getElementById('tag-row-' + deleteTagId);
                if (row) {
                    row.remove();
                }
            } else {
                showAlert(data.error || 'Error al eliminar', 'error');
            }
            closeDeleteModal();
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al eliminar el tag', 'error');
            closeDeleteModal();
        });
    }

    // Mostrar alertas
    function showAlert(message, type) {
        const container = document.getElementById('alertContainer');
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.textContent = message;
        container.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }

    // Guardar con Enter
    document.querySelectorAll('.tag-title-input').forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const row = this.closest('tr');
                const id = row.dataset.id;
                saveTag(id);
            }
            if (e.key === 'Escape') {
                const row = this.closest('tr');
                const id = row.dataset.id;
                cancelEdit(id);
            }
        });
    });
</script>
@endsection
