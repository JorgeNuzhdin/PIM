@extends('layouts.main')

@section('title', 'Administrar Usuarios')

@section('content')
<div class="admin-container">
    <h1>Administrar Usuarios</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="filters-container">
        <form action="{{ route('admin.users.index') }}" method="GET" class="filters-form">
            <div class="filter-group">
                <label for="name">Nombre</label>
                <input type="text" name="name" id="name" value="{{ request('name') }}" placeholder="Buscar por nombre...">
            </div>

            <div class="filter-group">
                <label for="email">Email</label>
                <input type="text" name="email" id="email" value="{{ request('email') }}" placeholder="Buscar por email...">
            </div>

            <div class="filter-group">
                <label for="rol">Rol</label>
                <select name="rol" id="rol">
                    <option value="">Todos</option>
                    <option value="user" {{ request('rol') == 'user' ? 'selected' : '' }}>User</option>
                    <option value="profesor" {{ request('rol') == 'profesor' ? 'selected' : '' }}>Profesor</option>
                    <option value="editor" {{ request('rol') == 'editor' ? 'selected' : '' }}>Editor</option>
                    <option value="admin" {{ request('rol') == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="created_at">Registrado desde</label>
                <input type="date" name="created_at" id="created_at" value="{{ request('created_at') }}">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla de usuarios --}}
    <div class="table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Fecha registro</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="rol-badge rol-{{ $user->rol }}">
                                {{ ucfirst($user->rol) }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('admin.users.updateRol', $user) }}" method="POST" class="rol-form">
                                @csrf
                                @method('PATCH')
                                <select name="rol" class="rol-select" onchange="this.form.submit()">
                                    <option value="user" {{ $user->rol == 'user' ? 'selected' : '' }}>User</option>
                                    <option value="profesor" {{ $user->rol == 'profesor' ? 'selected' : '' }}>Profesor</option>
                                    <option value="editor" {{ $user->rol == 'editor' ? 'selected' : '' }}>Editor</option>
                                    <option value="admin" {{ $user->rol == 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-row">No se encontraron usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="pagination-container">
        {{ $users->withQueryString()->links() }}
    </div>
</div>
@endsection

@section('scripts')
<style>
.admin-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.admin-container h1 {
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
    min-width: 180px;
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

/* Tabla */
.table-container {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.users-table th,
.users-table td {
    padding: 0.875rem 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.users-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.users-table tbody tr:hover {
    background-color: #f8f9fa;
}

.empty-row {
    text-align: center;
    color: #888;
    padding: 2rem !important;
}

/* Badges de rol */
.rol-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.rol-user {
    background-color: #e9ecef;
    color: #495057;
}

.rol-profesor {
    background-color: #fff3cd;
    color: #856404;
}

.rol-editor {
    background-color: #cce5ff;
    color: #004085;
}

.rol-admin {
    background-color: #d4edda;
    color: #155724;
}

/* Select de rol en acciones */
.rol-form {
    display: inline;
}

.rol-select {
    padding: 0.375rem 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.875rem;
    cursor: pointer;
}

.rol-select:focus {
    outline: none;
    border-color: #007bff;
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
@endsection