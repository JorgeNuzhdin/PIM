@extends('layouts.main')

@section('title', 'Presentacion - Carrito')

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
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .page-type {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
    }

    .page-type.enunciado {
        background: #ebf8ff;
        color: #2b6cb0;
    }

    .page-type.solucion {
        background: #f0fff4;
        color: #276749;
    }

    .problem-number {
        font-size: 1.1rem;
        font-weight: 700;
        color: #4a5568;
    }

    .page-content {
        flex: 1;
        overflow-y: auto;
        line-height: 1.8;
    }

    .page-content .latex-content {
        font-size: 1.1rem;
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
    }

    .nav-number:hover {
        background: #667eea;
    }

    .nav-number.active {
        background: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.4);
    }

    .nav-separator {
        color: #718096;
        padding: 0 0.5rem;
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

    /* Pagina oculta */
    .page-data {
        display: none;
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
            font-size: 0.85rem;
        }
    }
</style>
@endsection

@section('content')
<div class="presentacion-container">
    {{-- Contenedor de pagina visible --}}
    <div class="page-container">
        <div class="page-header">
            <span class="page-type enunciado" id="page-type">Enunciado</span>
            <span class="problem-number" id="problem-number">Problema 1</span>
        </div>
        <div class="page-content" id="page-content">
            {{-- Contenido dinamico --}}
        </div>
    </div>


</div>

{{-- Barra de navegacion fija abajo --}}
<div class="navigation-bar">
    <button class="nav-arrow" id="btn-prev" onclick="prevPage()" title="Anterior">
        &larr;
    </button>

    <div class="nav-numbers" id="nav-numbers">
        {{-- Numeros de problemas generados por JS --}}
    </div>

    <button class="nav-arrow" id="btn-next" onclick="nextPage()" title="Siguiente">
        &rarr;
    </button>

    <a href="{{ route('carrito.index') }}" class="btn-volver">Volver</a>
</div>

{{-- Datos de los problemas (ocultos) --}}
<div id="problems-data" class="page-data">
    @foreach($items as $index => $item)
        <div class="problem-item"
             data-index="{{ $index }}"
             data-id="{{ $item->problema->id }}"
             data-has-hints="{{ $item->problema->hints ? '1' : '0' }}">
            <div class="problem-enunciado">
                {!! \App\Helpers\LatexHelper::toHtml($item->problema->problem_tex) !!}
            </div>
            <div class="problem-hints">
                @if($item->problema->hints)
                    {!! \App\Helpers\LatexHelper::toHtml($item->problema->hints) !!}
                @endif
            </div>
            <div class="problem-solution">
                @if($item->problema->solution_tex)
                    {!! \App\Helpers\LatexHelper::toHtml($item->problema->solution_tex) !!}
                @else
                    <p style="color: #718096; font-style: italic;">No hay solucion disponible para este problema.</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
    const totalProblems = {{ $items->count() }};
    const totalPages = totalProblems * 2; // Enunciado + Solucion por problema
    let currentPage = 0; // 0-indexed

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        buildNavigation();
        showPage(0);

        // Renderizar MathJax si esta disponible
        if (window.MathJax) {
            MathJax.typesetPromise().catch(err => console.error('MathJax error:', err));
        }
    });

    function buildNavigation() {
        const navNumbers = document.getElementById('nav-numbers');
        navNumbers.innerHTML = '';

        for (let i = 0; i < totalProblems; i++) {
            const btn = document.createElement('button');
            btn.className = 'nav-number';
            btn.textContent = (i + 1);
            btn.onclick = () => goToProblem(i);
            btn.id = 'nav-btn-' + i;
            navNumbers.appendChild(btn);
        }
    }

    function showPage(pageIndex) {
        currentPage = pageIndex;

        const problemIndex = Math.floor(pageIndex / 2);
        const isEnunciado = pageIndex % 2 === 0;

        const problemData = document.querySelector(`.problem-item[data-index="${problemIndex}"]`);
        const problemId = problemData.dataset.id;
        const hasHints = problemData.dataset.hasHints === '1';

        const pageType = document.getElementById('page-type');
        const problemNumber = document.getElementById('problem-number');
        const pageContent = document.getElementById('page-content');

        if (isEnunciado) {
            pageType.textContent = 'Enunciado';
            pageType.className = 'page-type enunciado';
            problemNumber.textContent = `Problema ${problemIndex + 1}`;

            const enunciado = problemData.querySelector('.problem-enunciado').innerHTML;
            const hints = problemData.querySelector('.problem-hints').innerHTML;

            let html = `<div class="latex-content">${enunciado}</div>`;

            if (hasHints) {
                html += `
                    <div class="hint-section">
                        <button class="btn-hint" onclick="toggleHint()">
                            💡 Mostrar pista
                        </button>
                        <div class="hint-content" id="hint-content">
                            ${hints}
                        </div>
                    </div>
                `;
            }

            pageContent.innerHTML = html;
        } else {
            pageType.textContent = 'Solucion';
            pageType.className = 'page-type solucion';
            problemNumber.textContent = `Problema ${problemIndex + 1}`;

            const solution = problemData.querySelector('.problem-solution').innerHTML;
            pageContent.innerHTML = `<div class="latex-content">${solution}</div>`;
        }

        // Actualizar navegacion
        updateNavigation();

        // Re-renderizar MathJax
        if (window.MathJax) {
            MathJax.typesetPromise([pageContent]).catch(err => console.error('MathJax error:', err));
        }
    }

    function updateNavigation() {
        const problemIndex = Math.floor(currentPage / 2);

        // Actualizar botones de problemas
        document.querySelectorAll('.nav-number').forEach((btn, idx) => {
            btn.classList.toggle('active', idx === problemIndex);
        });

        // Actualizar flechas
        document.getElementById('btn-prev').disabled = currentPage === 0;
        document.getElementById('btn-next').disabled = currentPage === totalPages - 1;
    }

    function prevPage() {
        if (currentPage > 0) {
            showPage(currentPage - 1);
        }
    }

    function nextPage() {
        if (currentPage < totalPages - 1) {
            showPage(currentPage + 1);
        }
    }

    function goToProblem(problemIndex) {
        // Ir al enunciado del problema
        showPage(problemIndex * 2);
    }

    function toggleHint() {
        const hintContent = document.getElementById('hint-content');
        const btn = document.querySelector('.btn-hint');

        if (hintContent.classList.contains('visible')) {
            hintContent.classList.remove('visible');
            btn.textContent = '💡 Mostrar pista';
        } else {
            hintContent.classList.add('visible');
            btn.textContent = '💡 Ocultar pista';

            // Re-renderizar MathJax en la pista
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
