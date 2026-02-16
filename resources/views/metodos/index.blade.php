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
    </div>

    @if($metodos->count() > 0)
        <table class="metodos-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tema</th>
                    <th>Subtema</th>
                    <th>Proponente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metodos as $metodo)
                    <tr>
                        <td><a href="{{ route('metodos.show', $metodo->id) }}" class="metodo-link">{{ $metodo->title }}</a></td>
                        <td><span class="tag">{{ $metodo->tema->tema }}</span></td>
                        <td><span class="tag">{{ $metodo->subtema->nombre }}</span></td>
                        <td>{{ $metodo->user->name ?? '—' }}</td>
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

document.getElementById('filtro-tema').addEventListener('change', function() {
    const temaId = this.value;
    const params = new URLSearchParams();
    if (temaId) params.set('tema_id', temaId);
    window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
});

document.getElementById('filtro-subtema').addEventListener('change', function() {
    const subtemaId = this.value;
    const temaId = document.getElementById('filtro-tema').value;
    const params = new URLSearchParams();
    if (temaId) params.set('tema_id', temaId);
    if (subtemaId) params.set('subtema_id', subtemaId);
    window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
});
</script>
@endsection
