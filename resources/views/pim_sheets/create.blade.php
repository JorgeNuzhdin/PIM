@extends('layouts.main')

@section('title', 'Subir Hoja de Problemas')

@section('styles')
<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #2d3748;
    }

    .form-group label .required {
        color: #e53e3e;
    }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 1rem;
    }

    .form-group input[type="file"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-group small {
        display: block;
        margin-top: 0.25rem;
        color: #718096;
        font-size: 0.875rem;
    }

    .file-upload-group {
        background: #f7fafc;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .file-upload-group h3 {
        margin: 0 0 1rem 0;
        color: #2d3748;
        font-size: 1.1rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
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

    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .alert-danger {
        background-color: #fed7d7;
        color: #742a2a;
        border: 1px solid #fc8181;
    }

    .alert ul {
        margin: 0.5rem 0 0 0;
        padding-left: 1.5rem;
    }

    .info-box {
        background-color: #bee3f8;
        border-left: 4px solid #4299e1;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 4px;
    }

    .info-box p {
        margin: 0;
        color: #2c5282;
    }
</style>
@endsection

@section('content')
<div class="container">
    <h1 style="margin-bottom: 1.5rem;">Subir Nueva Hoja de Problemas</h1>

    <div class="form-container">
        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Por favor, corrige los siguientes errores:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="info-box">
            <p><strong>Nota:</strong> Los campos marcados con <span style="color: #e53e3e;">*</span> son obligatorios.</p>
        </div>

        <form action="{{ route('pim-sheets.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="title">Título <span class="required">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required>
                <small>Título descriptivo de la hoja de problemas</small>
            </div>

            <div class="form-group">
                <label for="date_year">Año <span class="required">*</span></label>
                <input type="number" id="date_year" name="date_year" value="{{ old('date_year', $currentYear) }}" min="1900" max="2100" required>
                <small>Año académico de la hoja</small>
            </div>

            <div class="form-group">
                <label for="planet">Grupo</label>
                <input type="text" id="planet" name="planet" value="{{ old('planet') }}" maxlength="255">
                <small>Nombre del grupo o clase (ej: 1º ESO, 2º Bachillerato, etc.)</small>
            </div>

            <div class="form-group">
                <label for="institution">Institución</label>
                <input type="text" id="institution" name="institution" value="{{ old('institution', Auth::user()->institution ?? '') }}" maxlength="256">
                <small>Nombre de la institución educativa</small>
            </div>

            <div class="form-group">
                <label for="theme">Tema</label>
                <select id="theme" name="theme">
                    <option value="">Seleccionar tema...</option>
                    @foreach($temas as $tema)
                        <option value="{{ $tema->id }}" {{ old('theme') == $tema->id ? 'selected' : '' }}>
                            {{ $tema->tema }}
                        </option>
                    @endforeach
                </select>
                <small>Tema principal de la hoja</small>
            </div>

            <div class="form-group">
                <label for="tex_sols">Archivo TEX <span class="required">*</span></label>
                <input type="file" id="tex_sols" name="tex_sols" accept=".tex,.txt" required>
                <small>Archivo LaTeX de la hoja de problemas (máx. 10MB)</small>
            </div>

            <div class="form-group">
                <label for="preambles">Preámbulo</label>
                <textarea id="preambles" name="preambles" rows="4" readonly style="background-color: #f7fafc;"></textarea>
                <small>Contenido extraído automáticamente del archivo TEX (entre \preambletrue y \preamblefalse)</small>
            </div>

            <div class="form-group">
                <label for="problems">Problemas</label>
                <textarea id="problems" name="problems" maxlength="2048" readonly style="background-color: #f7fafc;">{{ old('problems') }}</textarea>
                <small>IDs de problemas extraídos automáticamente del archivo TEX (formato: 2804,2805,...)</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Subir Hoja de Problemas</button>
                <a href="{{ route('pim-sheets.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Función para extraer IDs de problemas del contenido TEX
    function extractProblemIds(texContent) {
        const regex = /\\idtitulo\{\\#(\d+):/g;
        const ids = [];
        let match;

        while ((match = regex.exec(texContent)) !== null) {
            ids.push(match[1]);
        }

        return ids.join(',');
    }

    // Función para extraer preámbulo del contenido TEX
    function extractPreamble(texContent) {
        const regex = /\\preambletrue([\s\S]*?)\\preamblefalse/;
        const match = texContent.match(regex);

        if (match && match[1]) {
            return match[1].trim();
        }

        return '';
    }

    // Procesar archivo TEX con soluciones cuando se selecciona
    document.getElementById('tex_sols').addEventListener('change', function(e) {
        const file = e.target.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function(event) {
            const texContent = event.target.result;

            // Extraer IDs de problemas
            const problemIds = extractProblemIds(texContent);
            if (problemIds) {
                document.getElementById('problems').value = problemIds;
                document.getElementById('problems').style.backgroundColor = '#c6f6d5'; // Verde claro
            }

            // Extraer preámbulo
            const preamble = extractPreamble(texContent);
            if (preamble) {
                document.getElementById('preambles').value = preamble;
                document.getElementById('preambles').style.backgroundColor = '#c6f6d5'; // Verde claro
            }
        };

        reader.onerror = function() {
            alert('Error al leer el archivo TEX. Por favor, intenta de nuevo.');
        };

        reader.readAsText(file);
    });

</script>
@endsection
