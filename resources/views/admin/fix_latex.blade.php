@extends('layouts.main')

@section('title', 'Reparar LaTeX - PIM')

@section('styles')
<style>
    .fix-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .fix-container h1 {
        color: #4a5568;
        margin-bottom: 1rem;
    }

    .info-box {
        background: #e6fffa;
        border: 1px solid #81e6d9;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        color: #234e52;
    }

    .warning-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        color: #78350f;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.2s;
    }

    .btn-primary {
        background-color: #4299e1;
        color: white;
    }

    .btn-primary:hover {
        background-color: #3182ce;
    }

    .btn-danger {
        background-color: #f56565;
        color: white;
    }

    .btn-danger:hover {
        background-color: #e53e3e;
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .results {
        margin-top: 2rem;
        padding: 1.5rem;
        background: #f7fafc;
        border-radius: 6px;
        display: none;
    }

    .results.show {
        display: block;
    }

    .example {
        background: white;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 4px;
        border-left: 4px solid #667eea;
    }

    .example-id {
        font-weight: bold;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }

    .code-block {
        background: #2d3748;
        color: #e2e8f0;
        padding: 1rem;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        overflow-x: auto;
        margin: 0.5rem 0;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .code-label {
        font-weight: 600;
        color: #4a5568;
        margin-top: 0.5rem;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .stat-card {
        background: white;
        padding: 1rem;
        border-radius: 6px;
        text-align: center;
        border: 2px solid #e2e8f0;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
    }

    .stat-label {
        color: #718096;
        margin-top: 0.5rem;
    }

    .loading {
        display: none;
        text-align: center;
        padding: 2rem;
    }

    .loading.show {
        display: block;
    }

    .spinner {
        border: 4px solid #f3f4f6;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .success-message {
        background: #c6f6d5;
        border: 1px solid #48bb78;
        border-radius: 6px;
        padding: 1rem;
        color: #22543d;
        margin-top: 1rem;
    }
</style>
@endsection

@section('content')
<div class="fix-container">
    <h1>🔧 Reparar barras invertidas en LaTeX</h1>

    <div class="info-box">
        <strong>ℹ️ Información:</strong><br>
        Esta herramienta detecta y corrige comandos LaTeX que han perdido la barra invertida (\).<br>
        Por ejemplo: <code>frac{a}{b}</code> → <code>\frac{a}{b}</code>
    </div>

    <div class="warning-box">
        <strong>⚠️ Advertencia:</strong><br>
        Esta operación modificará directamente la base de datos. Asegúrate de tener un respaldo antes de continuar.
    </div>

    <div style="display: flex; gap: 1rem;">
        <button class="btn btn-primary" id="btn-scan" onclick="escanear()">
            📊 Escanear problemas
        </button>
        <button class="btn btn-danger" id="btn-fix" onclick="aplicarCambios()" disabled>
            ✅ Aplicar cambios
        </button>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Procesando...</p>
    </div>

    <div class="results" id="results">
        <h2>Resultados del escaneo</h2>

        <div class="stats" id="stats"></div>

        <div id="ejemplos"></div>
    </div>

    <div id="success" style="display: none;"></div>
</div>

<script>
let datosEscaneo = null;

async function escanear() {
    const btnScan = document.getElementById('btn-scan');
    const btnFix = document.getElementById('btn-fix');
    const loading = document.getElementById('loading');
    const results = document.getElementById('results');
    const success = document.getElementById('success');

    btnScan.disabled = true;
    btnFix.disabled = true;
    loading.classList.add('show');
    results.classList.remove('show');
    success.style.display = 'none';

    try {
        const response = await fetch('{{ route("admin.fix-latex.scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Error del servidor' }));
            throw new Error(errorData.error || `Error ${response.status}`);
        }

        const data = await response.json();
        datosEscaneo = data;

        // Mostrar estadísticas
        document.getElementById('stats').innerHTML = `
            <div class="stat-card">
                <div class="stat-number">${data.total_problemas}</div>
                <div class="stat-label">Problemas con errores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">${data.total_campos}</div>
                <div class="stat-label">Campos a corregir</div>
            </div>
        `;

        // Mostrar ejemplos
        let ejemplosHtml = '<h3>Ejemplos de cambios (primeros 5):</h3>';

        if (data.ejemplos.length === 0) {
            ejemplosHtml += '<p>No se encontraron problemas que corregir.</p>';
        } else {
            data.ejemplos.forEach(ejemplo => {
                ejemplosHtml += `<div class="example">`;
                ejemplosHtml += `<div class="example-id">Problema ID: ${ejemplo.id}</div>`;

                for (const [campo, datos] of Object.entries(ejemplo.cambios)) {
                    ejemplosHtml += `<div class="code-label">Campo: ${campo}</div>`;
                    ejemplosHtml += `<div class="code-label">Original:</div>`;
                    ejemplosHtml += `<div class="code-block">${escapeHtml(datos.original.substring(0, 300))}${datos.original.length > 300 ? '...' : ''}</div>`;
                    ejemplosHtml += `<div class="code-label">Corregido:</div>`;
                    ejemplosHtml += `<div class="code-block">${escapeHtml(datos.corregido.substring(0, 300))}${datos.corregido.length > 300 ? '...' : ''}</div>`;
                }

                ejemplosHtml += `</div>`;
            });
        }

        document.getElementById('ejemplos').innerHTML = ejemplosHtml;

        results.classList.add('show');

        if (data.total_problemas > 0) {
            btnFix.disabled = false;
        }

    } catch (error) {
        alert('Error al escanear: ' + error.message);
    } finally {
        loading.classList.remove('show');
        btnScan.disabled = false;
    }
}

async function aplicarCambios() {
    if (!confirm('¿Estás seguro de que deseas aplicar estos cambios? Esta operación modificará la base de datos.')) {
        return;
    }

    const btnScan = document.getElementById('btn-scan');
    const btnFix = document.getElementById('btn-fix');
    const loading = document.getElementById('loading');
    const success = document.getElementById('success');

    btnScan.disabled = true;
    btnFix.disabled = true;
    loading.classList.add('show');

    try {
        const response = await fetch('{{ route("admin.fix-latex.fix") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Error del servidor' }));
            throw new Error(errorData.error || `Error ${response.status}`);
        }

        const data = await response.json();

        success.innerHTML = `
            <div class="success-message">
                <strong>✅ Cambios aplicados correctamente</strong><br>
                Problemas procesados: ${data.procesados}<br>
                Errores: ${data.errores}
            </div>
        `;
        success.style.display = 'block';

        // Limpiar resultados
        document.getElementById('results').classList.remove('show');
        datosEscaneo = null;

    } catch (error) {
        alert('Error al aplicar cambios: ' + error.message);
    } finally {
        loading.classList.remove('show');
        btnScan.disabled = false;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endsection
