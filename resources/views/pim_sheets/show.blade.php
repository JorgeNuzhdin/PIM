@extends('layouts.main')

@section('title', $sheet->title)

@section('styles')
<style>
    .sheet-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .sheet-header {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .sheet-title {
        font-size: 1.5rem;
        color: #2d3748;
        margin: 0 0 0.5rem 0;
    }

    .sheet-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        color: #718096;
        font-size: 0.9rem;
    }

    .sheet-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .sheet-actions {
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
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
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

    /* Selector Mostrar */
    .display-options {
        background: white;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
    }

    .display-options label {
        font-weight: 600;
        color: #2d3748;
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .checkbox-option {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        cursor: pointer;
        font-weight: normal;
    }

    .checkbox-option input {
        cursor: pointer;
    }

    /* Preamble section */
    .preamble-section {
        background: #f0fff4;
        border-left: 4px solid #48bb78;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .preamble-section h2 {
        color: #276749;
        font-size: 1.1rem;
        margin: 0 0 1rem 0;
    }

    .preamble-content {
        color: #2d3748;
        line-height: 1.6;
    }

    /* Problem cards */
    .problema-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .problema-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .problema-title-section {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .reto-number {
        color: #6366f1;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .problema-title {
        margin: 0;
        color: #2d3748;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .difficulty-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: bold;
        padding: 0.4rem 0.7rem;
        border-radius: 8px;
        font-size: 0.85rem;
        min-width: 35px;
        text-align: center;
        flex-shrink: 0;
    }

    .year-badge {
        background: #e2e8f0;
        color: #4a5568;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .problema-content {
        margin-bottom: 1rem;
    }

    .problema-content strong {
        color: #2d3748;
        display: block;
        margin-bottom: 0.5rem;
    }

    .latex-content {
        line-height: 1.6;
        color: #4a5568;
    }

    .problema-pistas {
        background: #fffbeb;
        border-left: 3px solid #f59e0b;
        padding: 1rem;
        border-radius: 4px;
    }

    .problema-comentarios {
        background: #f0f9ff;
        border-left: 3px solid #3b82f6;
        padding: 1rem;
        border-radius: 4px;
    }

    .problema-footer {
        padding-top: 0.75rem;
        border-top: 1px solid #e2e8f0;
        color: #718096;
        font-size: 0.9rem;
    }

    .no-problems {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        color: #718096;
    }
</style>
@endsection

@section('content')
<div class="container sheet-container">
    <!-- Header -->
    <div class="sheet-header">
        <h1 class="sheet-title">{{ $sheet->title }}</h1>
        <div class="sheet-meta">
            <span>📅 {{ $sheet->date_year }}-{{ $sheet->date_year + 1 }}</span>
            @if($sheet->planet)
                <span>👥 {{ $sheet->planet }}</span>
            @endif
            <span>🏛️ {{ $sheet->institution }}</span>
            @if($sheet->tema)
                <span>📚 {{ $sheet->tema->tema }}</span>
            @endif
        </div>
        <div class="sheet-actions">
            <a href="{{ route('pim-sheets.download', ['id' => $sheet->id]) }}" class="btn btn-primary">
                📦 Descargar ZIP
            </a>
            <a href="{{ route('pim-sheets.index') }}" class="btn btn-secondary">
                ← Volver al listado
            </a>
        </div>
    </div>

    <!-- Display Options -->
    <form method="GET" action="{{ route('pim-sheets.show', ['id' => $sheet->id]) }}" class="display-options">
        <label>Mostrar:</label>
        <div class="checkbox-group">
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="pistas" {{ in_array('pistas', $mostrarArray) ? 'checked' : '' }} onchange="this.form.submit()">
                Pistas
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="solucion" {{ in_array('solucion', $mostrarArray) ? 'checked' : '' }} onchange="this.form.submit()">
                Soluciones
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="fuente" {{ in_array('fuente', $mostrarArray) ? 'checked' : '' }} onchange="this.form.submit()">
                Fuente
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="comentarios" {{ in_array('comentarios', $mostrarArray) ? 'checked' : '' }} onchange="this.form.submit()">
                Comentarios
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="year" {{ in_array('year', $mostrarArray) ? 'checked' : '' }} onchange="this.form.submit()">
                Año académico
            </label>
            <label class="checkbox-option">
                <input type="checkbox" name="mostrar[]" value="nivel" {{ in_array('nivel', $mostrarArray) ? 'checked' : '' }} onchange="this.form.submit()">
                Nivel
            </label>
        </div>
    </form>

    <!-- Preamble -->
    @if(!empty($preambleHtml))
        <div class="preamble-section">
            <h2>📋 Preámbulo</h2>
            <div class="preamble-content latex-content">
                {!! $preambleHtml !!}
            </div>
        </div>
    @endif

    <!-- Problems -->
    @php
        use App\Helpers\LatexHelper;
        LatexHelper::resetCounters();
    @endphp

    @if($problemas->count() > 0)
        @php $retoNumber = 0; @endphp
        @foreach($problemas as $problema)
            @php $retoNumber++; @endphp
            <div class="problema-card">
                <div class="problema-header">
                    <div class="problema-title-section">
                        <span class="reto-number">Reto {{ $retoNumber }}:</span>
                        @if($problema->title)
                            <h3 class="problema-title">{{ $problema->title }}</h3>
                        @endif
                        @if($problema->school_year && in_array('year', $mostrarArray))
                            <span class="year-badge">📚 {{ $problema->school_year }}</span>
                        @endif
                    </div>

                    @if($problema->difficulty && in_array('nivel', $mostrarArray))
                        <div class="difficulty-badge" title="Dificultad: {{ $problema->difficulty }}/10">
                            {{ $problema->difficulty }}
                        </div>
                    @endif
                </div>

                <!-- Enunciado -->
                <div class="problema-content">
                    <strong>Enunciado:</strong>
                    <div class="latex-content">{!! $problema->problem_html_processed !!}</div>
                </div>

                <!-- Pistas -->
                @if($problema->hints && in_array('pistas', $mostrarArray))
                    <div class="problema-content problema-pistas">
                        <strong>💡 Pistas:</strong>
                        <div class="latex-content">
                            {!! nl2br(e($problema->hints)) !!}
                        </div>
                    </div>
                @endif

                <!-- Solución -->
                @if(in_array('solucion', $mostrarArray))
                    <div class="problema-content">
                        <strong>Solución:</strong>
                        <div class="latex-content">{!! $problema->solution_html_processed !!}</div>
                    </div>
                @endif

                <!-- Comentarios -->
                @if($problema->comments && in_array('comentarios', $mostrarArray))
                    <div class="problema-content problema-comentarios">
                        <strong>💬 Comentarios:</strong>
                        <div class="comentarios-content">
                            {!! nl2br(e($problema->comments)) !!}
                        </div>
                    </div>
                @endif

                <!-- Fuente -->
                @if($problema->source && in_array('fuente', $mostrarArray))
                    <div class="problema-footer">
                        <small>
                            📖 <strong>Fuente:</strong> {{ $problema->source }}
                        </small>
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="no-problems">
            <p>Esta hoja no tiene problemas asociados o los IDs de problemas no se encontraron en la base de datos.</p>
        </div>
    @endif
</div>
@endsection
