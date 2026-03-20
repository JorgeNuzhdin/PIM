@extends('layouts.main')

@section('title', 'Administrar Usuarios')

@section('content')
<div class="admin-container">
    <h1>Administrar Usuarios</h1>

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
                    <option value="user_seguro" {{ request('rol') == 'user_seguro' ? 'selected' : '' }}>User seguro</option>
                    <option value="profesor" {{ request('rol') == 'profesor' ? 'selected' : '' }}>Profesor</option>
                    <option value="profesor_seguro" {{ request('rol') == 'profesor_seguro' ? 'selected' : '' }}>Profesor seguro</option>
                    <option value="editor" {{ request('rol') == 'editor' ? 'selected' : '' }}>Editor</option>
                    <option value="admin" {{ request('rol') == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="profession">Profesión</label>
                <select name="profession" id="profession">
                    <option value="">Todas</option>
                    <option value="alumno_secundaria"   {{ request('profession') == 'alumno_secundaria'   ? 'selected' : '' }}>Alumno secundaria</option>
                    <option value="alumno_bachillerato" {{ request('profession') == 'alumno_bachillerato' ? 'selected' : '' }}>Alumno bachillerato</option>
                    <option value="alumno_universitario" {{ request('profession') == 'alumno_universitario' ? 'selected' : '' }}>Alumno universitario</option>
                    <option value="profesor_matematicas" {{ request('profession') == 'profesor_matematicas' ? 'selected' : '' }}>Profesor matemáticas</option>
                    <option value="profesor_universitario" {{ request('profession') == 'profesor_universitario' ? 'selected' : '' }}>Profesor universitario</option>
                    <option value="otro" {{ request('profession') == 'otro' ? 'selected' : '' }}>Otro</option>
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

    @php
        $sortUrl   = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
        $sortArrow = fn($col) => $sort === $col ? ($dir === 'asc' ? ' ↑' : ' ↓') : ' ↕';
        $professionLabels = [
            'alumno_secundaria'     => 'Alumno secundaria',
            'alumno_bachillerato'   => 'Alumno bachillerato',
            'alumno_universitario'  => 'Alumno universitario',
            'profesor_matematicas'  => 'Profe. matemáticas',
            'profesor_universitario'=> 'Profe. universitario',
        ];
        $reasonLabels = [
            'estudiar_matematicas' => 'Estudiar matemáticas',
            'ensenar_matematicas'  => 'Enseñar matemáticas',
        ];
    @endphp

    {{-- Control de columnas visibles --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; flex-wrap:wrap; gap:0.5rem;">
        <span style="font-size:0.875rem; color:#666;">{{ $users->total() }} usuario(s)</span>
        <div style="display:flex; align-items:center; gap:1rem; font-size:0.875rem;">
            <strong>Mostrar:</strong>
            <label style="cursor:pointer;"><input type="checkbox" id="col-email" onchange="toggleCol('email',this.checked)"> Email</label>
            <label style="cursor:pointer;"><input type="checkbox" id="col-motivo" onchange="toggleCol('motivo',this.checked)"> Motivo</label>
            <label style="cursor:pointer;"><input type="checkbox" id="col-problemas" checked onchange="toggleCol('problemas',this.checked)"> Problemas</label>
            <label style="cursor:pointer;"><input type="checkbox" id="col-reportes" onchange="toggleCol('reportes',this.checked)"> Reportes</label>
        </div>
    </div>

    {{-- Tabla de usuarios --}}
    <div class="table-container">
        <table class="users-table" id="users-table">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('name') }}" class="sort-link">Nombre{!! $sortArrow('name') !!}</a></th>
                    <th class="col-email" style="display:none;"><a href="{{ $sortUrl('email') }}" class="sort-link">Email{!! $sortArrow('email') !!}</a></th>
                    <th><a href="{{ $sortUrl('created_at') }}" class="sort-link">Fecha registro{!! $sortArrow('created_at') !!}</a></th>
                    <th><a href="{{ $sortUrl('profession') }}" class="sort-link">Profesión{!! $sortArrow('profession') !!}</a></th>
                    <th class="col-motivo" style="display:none;">Motivo</th>
                    <th><a href="{{ $sortUrl('rol') }}" class="sort-link">Rol{!! $sortArrow('rol') !!}</a></th>
                    <th class="col-problemas"><a href="{{ $sortUrl('problemas') }}" class="sort-link">Problemas{!! $sortArrow('problemas') !!}</a></th>
                    <th class="col-reportes" style="display:none;"><a href="{{ $sortUrl('reportes') }}" class="sort-link">Reportes{!! $sortArrow('reportes') !!}</a></th>
                    <th>Acciones
                        <button id="btn-enable-delete" onclick="toggleDeleteMode()" title="Activar modo eliminación"
                                style="background:none;border:none;cursor:pointer;font-size:1.1rem;margin-left:0.5rem;opacity:0.4;">🗑️</button>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $prof     = $user->profession ?? '';
                        $profLabel = $professionLabels[$prof] ?? ($prof ?: '-');
                        $reas     = $user->reason ?? '';
                        $reasLabel = $reasonLabels[$reas] ?? ($reas ?: '-');
                        $pc       = $problemaCounts[$user->id] ?? null;
                        $ec       = $errorCounts[$user->id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $user->name }} @if(isset($usuariosConMedalla[$user->id])) 🏅 @endif</td>
                        <td class="col-email" style="display:none;">{{ $user->email }}</td>
                        <td>{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td title="{{ $profLabel }}">{{ Str::limit($profLabel, 22, '…') }}</td>
                        <td class="col-motivo" style="display:none;" title="{{ $reasLabel }}">{{ Str::limit($reasLabel, 22, '…') }}</td>
                        <td>
                            <span class="rol-badge rol-{{ $user->rol }}">{{ ucfirst($user->rol) }}</span>
                        </td>
                        <td class="col-problemas" style="font-size:0.875rem; color:#4a5568; white-space:nowrap;">
                            @if($pc)
                                <span title="{{ $pc->aprobados }} aprobados / {{ $pc->total }} propuestos">{{ $pc->aprobados }}/{{ $pc->total }}</span>
                            @else
                                <span style="color:#cbd5e0;">—</span>
                            @endif
                        </td>
                        <td class="col-reportes" style="display:none; font-size:0.875rem; color:#4a5568; white-space:nowrap;">
                            @if($ec)
                                <span>{{ $ec->total }}</span>
                            @else
                                <span style="color:#cbd5e0;">—</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.users.updateRol', $user) }}" method="POST" class="rol-form">
                                @csrf
                                @method('PATCH')
                                <select name="rol" class="rol-select" onchange="this.form.submit()">
                                    <option value="user"           {{ $user->rol == 'user'           ? 'selected' : '' }}>User</option>
                                    <option value="user_seguro"    {{ $user->rol == 'user_seguro'    ? 'selected' : '' }}>User seguro</option>
                                    <option value="profesor"       {{ $user->rol == 'profesor'       ? 'selected' : '' }}>Profesor</option>
                                    <option value="profesor_seguro"{{ $user->rol == 'profesor_seguro'? 'selected' : '' }}>Profesor seguro</option>
                                    <option value="editor"         {{ $user->rol == 'editor'         ? 'selected' : '' }}>Editor</option>
                                    <option value="admin"          {{ $user->rol == 'admin'          ? 'selected' : '' }}>Admin</option>
                                </select>
                            </form>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="delete-user-form" style="display:none;margin-top:0.25rem;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-user"
                                        onclick="return confirm('¿Eliminar al usuario {{ addslashes($user->name) }}? Esta acción no se puede deshacer.')"
                                        style="background:#dc3545;color:white;border:none;padding:0.25rem 0.5rem;border-radius:4px;cursor:pointer;font-size:0.8rem;">
                                    🗑️ Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-row">No se encontraron usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @php $users->withQueryString(); @endphp
    <div class="pagination-wrapper">
        @if ($users->hasPages())
            @php
                $cur   = $users->currentPage();
                $last  = $users->lastPage();
                $start = max(1, $cur - 4);
                $end   = min($last, $cur + 4);
            @endphp
            <div class="pagination">
                <a href="{{ $cur > 1 ? $users->url(1) : '#' }}"
                   class="page-item {{ $cur == 1 ? 'disabled' : '' }}" title="Primera">&laquo;&laquo;</a>
                <a href="{{ !$users->onFirstPage() ? $users->previousPageUrl() : '#' }}"
                   class="page-item {{ $users->onFirstPage() ? 'disabled' : '' }}">&laquo;</a>

                @if ($start > 1)
                    <a href="{{ $users->url(1) }}" class="page-item">1</a>
                    @if ($start > 2)<span class="page-item disabled">...</span>@endif
                @endif

                @for ($p = $start; $p <= $end; $p++)
                    @if ($p == $cur)
                        <span class="page-item active">{{ $p }}</span>
                    @else
                        <a href="{{ $users->url($p) }}" class="page-item">{{ $p }}</a>
                    @endif
                @endfor

                @if ($end < $last)
                    @if ($end < $last - 1)<span class="page-item disabled">...</span>@endif
                    <a href="{{ $users->url($last) }}" class="page-item">{{ $last }}</a>
                @endif

                <a href="{{ $users->hasMorePages() ? $users->nextPageUrl() : '#' }}"
                   class="page-item {{ !$users->hasMorePages() ? 'disabled' : '' }}">&raquo;</a>
                <a href="{{ $cur < $last ? $users->url($last) : '#' }}"
                   class="page-item {{ $cur >= $last ? 'disabled' : '' }}" title="Última">&raquo;&raquo;</a>
            </div>
        @endif
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

.rol-user_seguro {
    background-color: #e2e8f0;
    color: #2d3748;
}

.rol-profesor_seguro {
    background-color: #fef9c3;
    color: #713f12;
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

#btn-enable-delete.active {
    opacity: 1;
    background: #dc354520 !important;
    border-radius: 4px;
    padding: 2px 4px;
}
</style>
<style>
.sort-link { color: inherit; text-decoration: none; white-space: nowrap; }
.sort-link:hover { text-decoration: underline; }
</style>
<script>
// ── Columnas visibles (persistidas en localStorage) ──────────────────
const COLS = ['email', 'motivo', 'problemas', 'reportes'];
const DEFAULTS = { email: false, motivo: false, problemas: true, reportes: false };

function toggleCol(name, visible) {
    document.querySelectorAll('.col-' + name).forEach(el => {
        el.style.display = visible ? '' : 'none';
    });
    const prefs = JSON.parse(localStorage.getItem('users_cols') || '{}');
    prefs[name] = visible;
    localStorage.setItem('users_cols', JSON.stringify(prefs));
}

function initCols() {
    const prefs = JSON.parse(localStorage.getItem('users_cols') || '{}');
    COLS.forEach(name => {
        const visible = name in prefs ? prefs[name] : DEFAULTS[name];
        const cb = document.getElementById('col-' + name);
        if (cb) cb.checked = visible;
        toggleCol(name, visible);
    });
}

document.addEventListener('DOMContentLoaded', initCols);

// ── Modo eliminación ──────────────────────────────────────────────────
let deleteMode = false;
function toggleDeleteMode() {
    deleteMode = !deleteMode;
    const btn = document.getElementById('btn-enable-delete');
    btn.classList.toggle('active', deleteMode);
    btn.title = deleteMode ? 'Desactivar modo eliminación' : 'Activar modo eliminación';
    document.querySelectorAll('.delete-user-form').forEach(f => {
        f.style.display = deleteMode ? 'block' : 'none';
    });
}
</script>
@endsection