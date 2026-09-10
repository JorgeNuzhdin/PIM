@extends('layouts.main')

@section('title', 'Presentación - ' . $sheet->title)

@section('styles')
<style>
    .presentacion-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 1rem;
        padding-bottom: 80px; /* Espacio para la barra de navegacion fija */
        min-height: calc(100vh - 120px);
        display: flex;
        flex-direction: column;
    }

    .sheet-heading {
        text-align: center;
        margin-bottom: 1rem;
    }

    .sheet-heading h1 {
        font-size: 1.3rem;
        color: #2d3748;
        margin: 0;
    }

    .sheet-heading .sheet-sub {
        color: #718096;
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .page-container {
        flex: 1;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 1rem;
        min-height: 400px;
        display: flex;
        flex-direction: column;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .page-type {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        white-space: nowrap;
    }

    .page-type.explicacion {
        background: #ebf8ff;
        color: #2b6cb0;
    }

    .page-type.reto {
        background: #eef2ff;
        color: #4338ca;
    }

    .page-type.solucion {
        background: #f0fff4;
        color: #276749;
    }

    .page-type.ejemplo {
        background: #fffbeb;
        color: #92400e;
    }

    .page-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #4a5568;
        text-align: right;
    }

    .pres-page {
        display: none;
        flex: 1;
        overflow-y: auto;
        line-height: 1.8;
    }

    .pres-page.active {
        display: block;
    }

    .pres-page .latex-content {
        font-size: 1.1rem;
    }

    .pres-page img {
        max-width: 100%;
        height: auto;
    }

    /* Boton de pista */
    .hint-section {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px dashed #cbd5e0;
    }

    .btn-hint {
        background: #faf5ff;
        border: 2px solid #9f7aea;
        color: #6b46c1;
        padding: 0.5rem 1.2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-hint:hover {
        background: #9f7aea;
        color: white;
    }

    .hint-content {
        display: none;
        margin-top: 1rem;
        padding: 1rem;
        background: #faf5ff;
        border-radius: 8px;
        border-left: 4px solid #9f7aea;
    }

    .hint-content.visible {
        display: block;
    }

    /* Navegacion fija abajo */
    .navigation-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #2d3748;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        z-index: 1000;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.2);
    }

    .nav-arrow {
        background: #4a5568;
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-arrow:hover:not(:disabled) {
        background: #667eea;
    }

    .nav-arrow:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .nav-numbers {
        display: flex;
        gap: 0.3rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .nav-number {
        background: #4a5568;
        border: none;
        color: white;
        min-width: 36px;
        height: 36px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8rem;
    }

    .nav-number:hover {
        background: #667eea;
    }

    .nav-number.active {
        background: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.4);
    }

    .nav-number.teoria-nav {
        background: #2b6cb0;
    }

    .nav-number.teoria-nav.active {
        background: #3182ce;
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.4);
    }

    .btn-volver {
        background: #718096;
        border: none;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        margin-left: 1rem;
    }

    .btn-volver:hover {
        background: #4a5568;
    }

    .empty-state {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        color: #718096;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .page-container {
            padding: 1rem;
        }
        .navigation-bar {
            padding: 0.5rem 0.75rem;
        }
        .nav-number {
            min-width: 32px;
            height: 32px;
            font-size: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
<div class="presentacion-container">
    <div class="sheet-heading">
        <h1>{{ $sheet->title }}</h1>
        <div class="sheet-sub">
            {{ $sheet->date_year }}-{{ $sheet->date_year + 1 }}
            @if($sheet->planet) · {{ $sheet->planet }} @endif
            @if($sheet->tema) · {{ $sheet->tema->tema }} @endif
        </div>
    </div>

    @if(count($pages) === 0)
        <div class="empty-state">
            <p>Esta hoja no tiene preámbulo ni problemas que mostrar.</p>
            <p><a href="{{ route('pim-sheets.index') }}">← Volver al listado</a></p>
        </div>
    @else
        <div class="page-container">
            <div class="page-header">
                <span class="page-type" id="page-type"></span>
                <span class="page-title" id="page-title"></span>
            </div>

            @foreach($pages as $index => $page)
                <div class="pres-page" data-index="{{ $index }}" data-group="{{ $page['group'] }}"
                     data-badge="{{ $page['badge'] }}" data-kind="{{ $page['kind'] }}"
                     data-title="{{ $page['title'] }}">
                    <div class="latex-content">{!! $page['html'] !!}</div>

                    @if(!empty($page['hints']))
                        <div class="hint-section">
                            <button class="btn-hint" onclick="toggleHint({{ $index }})" id="btn-hint-{{ $index }}">
                                Mostrar pista
                            </button>
                            <div class="hint-content" id="hint-{{ $index }}">
                                {!! $page['hints'] !!}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

@if(count($pages) > 0)
{{-- Barra de navegacion fija abajo --}}
<div class="navigation-bar">
    <button class="nav-arrow" id="btn-prev" onclick="prevPage()" title="Anterior">&larr;</button>

    <div class="nav-numbers" id="nav-numbers">
        @php $seenGroups = []; @endphp
        @foreach($pages as $index => $page)
            @if(!in_array($page['group'], $seenGroups))
                @php $seenGroups[] = $page['group']; @endphp
                <button class="nav-number{{ $page['kind'] === 'explicacion' ? ' teoria-nav' : '' }}"
                        data-group="{{ $page['group'] }}"
                        onclick="showPage({{ $index }})">{{ $page['label'] }}</button>
            @endif
        @endforeach
    </div>

    <button class="nav-arrow" id="btn-next" onclick="nextPage()" title="Siguiente">&rarr;</button>

    <a href="{{ route('pim-sheets.show', ['id' => $sheet->id]) }}" class="btn-volver">Ver hoja</a>
    <a href="{{ route('pim-sheets.index') }}" class="btn-volver">Volver</a>
</div>
@endif
@endsection

@section('scripts')
<script>
    const presPages = Array.from(document.querySelectorAll('.pres-page'));
    const totalPages = presPages.length;
    let currentPage = 0;

    document.addEventListener('DOMContentLoaded', function() {
        if (totalPages > 0) {
            showPage(0);
        }

        if (window.MathJax) {
            MathJax.typesetPromise().catch(err => console.error('MathJax error:', err));
        }
    });

    function showPage(pageIndex) {
        if (pageIndex < 0 || pageIndex >= totalPages) return;

        currentPage = pageIndex;
        const page = presPages[pageIndex];

        presPages.forEach((el, idx) => el.classList.toggle('active', idx === pageIndex));

        const pageType = document.getElementById('page-type');
        pageType.textContent = page.dataset.badge;
        pageType.className = 'page-type ' + page.dataset.kind;

        document.getElementById('page-title').textContent = page.dataset.title || '';

        updateNavigation();
        page.scrollTop = 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateNavigation() {
        const currentGroup = presPages[currentPage].dataset.group;

        document.querySelectorAll('.nav-number').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.group === currentGroup);
        });

        document.getElementById('btn-prev').disabled = currentPage === 0;
        document.getElementById('btn-next').disabled = currentPage === totalPages - 1;
    }

    function prevPage() {
        if (currentPage > 0) showPage(currentPage - 1);
    }

    function nextPage() {
        if (currentPage < totalPages - 1) showPage(currentPage + 1);
    }

    function toggleHint(index) {
        const hintContent = document.getElementById('hint-' + index);
        const btn = document.getElementById('btn-hint-' + index);

        if (hintContent.classList.contains('visible')) {
            hintContent.classList.remove('visible');
            btn.textContent = 'Mostrar pista';
        } else {
            hintContent.classList.add('visible');
            btn.textContent = 'Ocultar pista';

            if (window.MathJax) {
                MathJax.typesetPromise([hintContent]).catch(err => console.error('MathJax error:', err));
            }
        }
    }

    // Navegacion con teclado
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            prevPage();
        } else if (e.key === 'ArrowRight') {
            nextPage();
        }
    });
</script>
@endsection
