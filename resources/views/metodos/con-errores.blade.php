@extends('layouts.main')

@section('title', 'Métodos con errores')

@section('styles')
<style>
.container {
    max-width: 960px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.metodo-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    border-left: 4px solid #f59e0b;
}

.metodo-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.metodo-card-header h3 {
    margin: 0;
    color: #2d3748;
}

.tipo-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
}

.tipo-badge {
    background: #fed7d7;
    color: #742a2a;
    border: 1px solid #fc8181;
    border-radius: 4px;
    padding: 0.2rem 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.reportes-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.reportes-table th {
    text-align: left;
    padding: 0.5rem 0.75rem;
    background: #f7fafc;
    border-bottom: 2px solid #e2e8f0;
    color: #4a5568;
    font-weight: 600;
}

.reportes-table td {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    color: #4a5568;
}

.reportes-table tr:last-child td {
    border-bottom: none;
}

.btn-edit-metodo {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.9rem;
    background: #4299e1;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    transition: background 0.2s;
}

.btn-edit-metodo:hover {
    background: #3182ce;
    color: white;
    text-decoration: none;
}

.btn-report-index {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.9rem;
    background: white;
    color: #4a5568;
    border: 1px solid #cbd5e0;
    border-radius: 5px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-report-index:hover {
    background: #fff5f5;
    border-color: #e53e3e;
    color: #e53e3e;
}

.pagination-wrapper {
    margin: 1.5rem 0;
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    gap: 0.3rem;
    align-items: center;
}

.page-item {
    padding: 0.4rem 0.7rem;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    background: white;
    color: #4a5568;
    text-decoration: none;
    font-size: 0.875rem;
}

.page-item.active {
    background: #4a5568;
    color: white;
    border-color: #4a5568;
}

.page-item.disabled {
    color: #a0aec0;
    pointer-events: none;
}

.page-item:hover:not(.active):not(.disabled) {
    background: #f7fafc;
}
</style>
@endsection

@section('content')
<div class="container">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:0.5rem;">
        <h1 style="margin:0; color:#2d3748;">⚠️ Métodos con errores reportados</h1>
        <a href="{{ route('metodos.index') }}" style="font-size:0.9rem; color:#4a5568; text-decoration:none;">← Volver a métodos</a>
    </div>

    @if($metodos->count() === 0)
        <div class="metodo-card" style="text-align:center; color:#718096; border-left-color:#68d391;">
            ✅ No hay métodos con errores pendientes.
        </div>
    @else

    <div class="pagination-wrapper">
        @if ($metodos->hasPages())
            <div class="pagination">
                @if ($metodos->onFirstPage())
                    <span class="page-item disabled">&laquo;</span>
                @else
                    <a href="{{ $metodos->previousPageUrl() }}" class="page-item">&laquo;</a>
                @endif
                @for ($p = 1; $p <= $metodos->lastPage(); $p++)
                    @if ($p == $metodos->currentPage())
                        <span class="page-item active">{{ $p }}</span>
                    @else
                        <a href="{{ $metodos->url($p) }}" class="page-item">{{ $p }}</a>
                    @endif
                @endfor
                @if ($metodos->hasMorePages())
                    <a href="{{ $metodos->nextPageUrl() }}" class="page-item">&raquo;</a>
                @else
                    <span class="page-item disabled">&raquo;</span>
                @endif
            </div>
        @endif
    </div>

    @foreach($metodos as $metodo)
        <div class="metodo-card">
            <div class="metodo-card-header">
                <h3>Método #{{ $metodo->id }}
                    <span style="color:#718096; font-size:0.9rem; font-weight:400;"> — {{ $metodo->title }}</span>
                </h3>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <button class="btn-report-index" onclick="openReportMetodoModal({{ $metodo->id }})" title="Añadir otro reporte">
                        ⚠️ Reportar
                    </button>
                    <a href="{{ route('metodos.edit', ['id' => $metodo->id]) }}" class="btn-edit-metodo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        Editar
                    </a>
                </div>
            </div>

            {{-- Campos afectados (tipo agregado) --}}
            @if($metodo->tipoAgregado !== '00000')
                <div class="tipo-badges">
                    <span style="font-size:0.8rem; color:#718096; align-self:center;">Campos afectados:</span>
                    @foreach($tipoLabels as $bit => $label)
                        @if(($metodo->tipoAgregado[$bit] ?? '0') === '1')
                            <span class="tipo-badge">{{ $label }}</span>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Tema --}}
            @if($metodo->tema)
                <div style="margin-bottom:0.75rem;">
                    <span style="display:inline-block; background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:0.2rem 0.6rem; border-radius:12px; font-size:0.8rem;">{{ $metodo->tema->tema }}</span>
                </div>
            @endif

            {{-- Tabla de reportes --}}
            <table class="reportes-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Campos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metodo->errorReports as $reporte)
                        <tr>
                            <td>{{ $reporte->user?->name ?? '(anónimo)' }}</td>
                            <td style="white-space:nowrap;">{{ $reporte->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $reporte->reporte ?: '—' }}</td>
                            <td>
                                @php
                                    $campos = [];
                                    foreach($tipoLabels as $bit => $label) {
                                        if (($reporte->tipo[$bit] ?? '0') === '1') {
                                            $campos[] = $label;
                                        }
                                    }
                                @endphp
                                {{ $campos ? implode(', ', $campos) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="pagination-wrapper">
        @if ($metodos->hasPages())
            <div class="pagination">
                @if ($metodos->onFirstPage())
                    <span class="page-item disabled">&laquo;</span>
                @else
                    <a href="{{ $metodos->previousPageUrl() }}" class="page-item">&laquo;</a>
                @endif
                @for ($p = 1; $p <= $metodos->lastPage(); $p++)
                    @if ($p == $metodos->currentPage())
                        <span class="page-item active">{{ $p }}</span>
                    @else
                        <a href="{{ $metodos->url($p) }}" class="page-item">{{ $p }}</a>
                    @endif
                @endfor
                @if ($metodos->hasMorePages())
                    <a href="{{ $metodos->nextPageUrl() }}" class="page-item">&raquo;</a>
                @else
                    <span class="page-item disabled">&raquo;</span>
                @endif
            </div>
        @endif
    </div>
    @endif
</div>
@endsection

@section('scripts')
@include('_partials.report-error-metodo-modal')
@endsection
