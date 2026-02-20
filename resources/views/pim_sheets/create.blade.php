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

    .form-group input:invalid,
    .form-group select:invalid {
        border-color: #fc8181;
    }

    .form-group input:valid,
    .form-group select:valid {
        border-color: #68d391;
    }

    /* Sección de imágenes */
    .images-section {
        display: none;
        background: #fffbeb;
        border: 1px solid #f59e0b;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .images-section.visible {
        display: block;
    }

    .images-section h3 {
        margin: 0 0 1rem 0;
        color: #92400e;
        font-size: 1.1rem;
    }

    .images-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1rem 0;
    }

    .images-list li {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }

    .images-list .image-name {
        flex: 1;
        font-weight: 500;
        color: #374151;
    }

    .images-list .image-status {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .images-list .image-status.uploaded {
        color: #059669;
    }

    .images-list input[type="file"] {
        max-width: 250px;
    }

    /* Sección de métodos */
    .methods-section {
        display: none;
        background: #eff6ff;
        border: 1px solid #3b82f6;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .methods-section.visible {
        display: block;
    }

    .methods-section h3 {
        margin: 0 0 1rem 0;
        color: #1e40af;
        font-size: 1.1rem;
    }

    .methods-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1rem 0;
    }

    .methods-list li {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }

    .methods-list .method-title {
        flex: 1;
        font-weight: 500;
        color: #374151;
    }

    .methods-list .method-subtema {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .methods-list .method-subtema.resolved {
        color: #059669;
        font-weight: 600;
    }

    .methods-list .method-subtema.unresolved {
        color: #dc2626;
    }
</style>
@endsection

@section('content')
<div class="container">
    <h1 style="margin-bottom: 1.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="#4299e1" style="vertical-align:middle;margin-right:0.3em;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Subir Nueva Hoja de Problemas</h1>

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

        <form action="{{ route('pim-sheets.store') }}" method="POST" enctype="multipart/form-data" id="sheetForm" onsubmit="return validateForm()">
            @csrf

            <div class="form-group">
                <label for="title">Título <span class="required">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required>
                <small>Título descriptivo de la hoja de problemas</small>
            </div>

            <div class="form-group">
                <label for="date_year">Año académico <span class="required">*</span></label>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="number" id="date_year" name="date_year" value="{{ old('date_year', $currentYear) }}" min="1900" max="2100" required style="width: 120px;">
                    <span id="year_display" style="color: #4a5568; font-weight: 500;">{{ old('date_year', $currentYear) }}-{{ old('date_year', $currentYear) + 1 }}</span>
                </div>
                <small>Introduce el año de inicio del curso (ej: 2025 para el curso 2025-2026)</small>
            </div>

            <div class="form-group">
                <label for="planet">Grupo</label>
                <input type="text" id="planet" name="planet" value="{{ old('planet') }}" maxlength="255">
                <small>Nombre del grupo o clase (ej: 1º ESO, 2º Bachillerato, etc.)</small>
            </div>

            <div class="form-group">
                <label for="institution">Institución <span class="required">*</span></label>
                <input type="text" id="institution" name="institution" value="{{ old('institution', 'PIM') }}" maxlength="256" required>
                <small>Nombre de la institución educativa</small>
            </div>

            <div class="form-group">
                <label for="theme">Tema <span class="required">*</span></label>
                <select id="theme" name="theme" required>
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
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <input type="file" id="tex_sols" name="tex_sols" accept=".tex,.txt" required>
                    <a href="/tex/plantilla_hoja.tex" download style="background:#4299e1;color:white;padding:0.4rem 0.8rem;border-radius:4px;font-size:0.875rem;text-decoration:none;font-weight:600;white-space:nowrap;">
                        📥 Descargar plantilla
                    </a>
                </div>
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

            <!-- Sección de imágenes del preámbulo -->
            <div class="images-section" id="imagesSection">
                <h3>📷 Imágenes detectadas en el preámbulo</h3>
                <p style="color: #92400e; margin-bottom: 1rem;">
                    Se han detectado las siguientes imágenes en el preámbulo. Por favor, sube los archivos correspondientes:
                </p>
                <ul class="images-list" id="imagesList">
                    <!-- Se rellenará dinámicamente con JavaScript -->
                </ul>
            </div>

            <!-- Sección de métodos detectados en el preámbulo -->
            <div class="methods-section" id="methodsSection">
                <h3>Métodos detectados en el preámbulo</h3>
                <p style="color: #1e40af; margin-bottom: 1rem;">
                    Se han detectado los siguientes métodos (secciones) en el preámbulo del archivo TEX:
                </p>
                <ul class="methods-list" id="methodsList">
                    <!-- Se rellenará dinámicamente con JavaScript -->
                </ul>
                <p id="methodsTemaWarning" style="display:none; color: #dc2626; font-weight: 600;">
                    Selecciona un Tema arriba para poder asignar subtemas a los métodos.
                </p>
            </div>

            <input type="hidden" name="metodos_json" id="metodos_json" value="">

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
    // Actualizar visualización del año académico
    document.getElementById('date_year').addEventListener('input', function(e) {
        const year = parseInt(e.target.value);
        const display = document.getElementById('year_display');
        if (year && year >= 1900 && year <= 2100) {
            display.textContent = year + '-' + (year + 1);
        } else {
            display.textContent = '';
        }
    });

    // Validación del formulario antes de enviar
    function validateForm() {
        const title = document.getElementById('title').value.trim();
        const dateYear = document.getElementById('date_year').value.trim();
        const institution = document.getElementById('institution').value.trim();
        const theme = document.getElementById('theme').value;
        const texFile = document.getElementById('tex_sols').files.length;

        const errors = [];

        if (!title) {
            errors.push('El título es obligatorio.');
        }
        if (!dateYear) {
            errors.push('El año es obligatorio.');
        }
        if (!institution) {
            errors.push('La institución es obligatoria.');
        }
        if (!theme) {
            errors.push('El tema es obligatorio.');
        }
        if (!texFile) {
            errors.push('El archivo TEX es obligatorio.');
        }

        // Validar métodos extraídos
        if (window._extractedMethods && window._extractedMethods.length > 0) {
            const sinSubtema = window._extractedMethods.filter(m => !m.subtema_ids || m.subtema_ids.length === 0);
            if (sinSubtema.length > 0) {
                errors.push('Hay ' + sinSubtema.length + ' método(s) sin subtema asignado.');
            }
        }

        if (errors.length > 0) {
            alert('Por favor, corrige los siguientes errores:\n\n- ' + errors.join('\n- '));
            return false;
        }

        return true;
    }

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

    // Función para extraer nombres de imágenes ANTES y DENTRO del preámbulo
    // (las imágenes después del preámbulo son de problemas, ya están en la BD)
    function extractImageNames(texContent) {
        // Extraer macros \def\nombre{valor} para resolver referencias
        const macros = {};
        const defRegex = /\\def\\(\w+)\{([^}]+)\}/g;
        let defMatch;
        while ((defMatch = defRegex.exec(texContent)) !== null) {
            macros[defMatch[1]] = defMatch[2];
        }

        // Solo buscar imágenes ANTES de \preamblefalse (o en todo si no existe)
        const preambleEndIdx = texContent.indexOf('\\preamblefalse');
        const searchContent = preambleEndIdx !== -1
            ? texContent.substring(0, preambleEndIdx)
            : texContent.substring(0, texContent.indexOf('\\begin{document}') || texContent.length);

        // Buscar \includegraphics con o sin opciones
        const regex = /\\includegraphics\s*(?:\[[^\]]*\])?\s*\{([^}]+)\}/g;
        const images = [];
        let match;

        while ((match = regex.exec(searchContent)) !== null) {
            let imageName = match[1].trim();
            // Resolver macros: \logo -> valor de \def\logo{...}
            const macroMatch = imageName.match(/^\\(\w+)$/);
            if (macroMatch && macros[macroMatch[1]]) {
                imageName = macros[macroMatch[1]];
            }
            if (!images.includes(imageName) && !imageName.startsWith('\\')) {
                images.push(imageName);
            }
        }

        return images;
    }

    // Mostrar campos de subida de imágenes
    function showImageUploadFields(imageNames) {
        const section = document.getElementById('imagesSection');
        const list = document.getElementById('imagesList');

        if (imageNames.length === 0) {
            section.classList.remove('visible');
            list.innerHTML = '';
            return;
        }

        list.innerHTML = '';

        imageNames.forEach((imageName, index) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <span class="image-name">📄 ${imageName}</span>
                <input type="file" name="imagenes_preambulo[]" data-image-name="${imageName}" accept="image/*,.pdf" onchange="markImageUploaded(this)">
                <span class="image-status" id="status_${index}">Pendiente</span>
                <input type="hidden" name="imagenes_nombres[]" value="${imageName}">
            `;
            list.appendChild(li);
        });

        section.classList.add('visible');
    }

    // Marcar imagen como subida
    function markImageUploaded(input) {
        const li = input.closest('li');
        const status = li.querySelector('.image-status');

        if (input.files.length > 0) {
            status.textContent = '✓ Subido';
            status.classList.add('uploaded');
        } else {
            status.textContent = 'Pendiente';
            status.classList.remove('uploaded');
        }
    }

    // === Métodos: extracción y resolución de subtemas ===

    // Estado global de métodos extraídos
    window._extractedMethods = [];
    window._pendingMethods = null;
    window._availableSubtemas = [];

    // Extraer métodos (secciones) del preámbulo
    function extractMethodsFromPreamble(preambleText) {
        if (!preambleText || !preambleText.trim()) return [];

        const methods = [];
        const sectionRegex = /\\subsection\*?\{([^}]+)\}/g;
        const matches = [];
        let m;

        while ((m = sectionRegex.exec(preambleText)) !== null) {
            matches.push({
                title: m[1].trim(),
                startIndex: m.index,
                endOfMatch: m.index + m[0].length
            });
        }

        if (matches.length === 0) return [];

        for (let i = 0; i < matches.length; i++) {
            const contentStart = matches[i].endOfMatch;
            const contentEnd = (i + 1 < matches.length)
                ? matches[i + 1].startIndex
                : preambleText.length;

            const content = preambleText.substring(contentStart, contentEnd).trim();
            const subtemaMatch = content.match(/\\subtema\{([^}]+)\}/);
            const subtemaNombre = subtemaMatch ? subtemaMatch[1].trim() : null;

            methods.push({
                title: matches[i].title,
                content: content,
                subtema_nombre: subtemaNombre,
                subtema_ids: []
            });
        }

        return methods;
    }

    // Resolver subtemas contra la lista del API
    function resolveSubtemas(methods, subtemasFromApi) {
        const resolved = [];
        const unresolved = [];

        methods.forEach(method => {
            if (method.subtema_nombre) {
                const match = subtemasFromApi.find(
                    s => s.nombre.trim().toLowerCase() === method.subtema_nombre.toLowerCase()
                );
                if (match) {
                    method.subtema_ids = [match.id];
                    resolved.push(method);
                } else {
                    unresolved.push(method);
                }
            } else {
                unresolved.push(method);
            }
        });

        return { resolved, unresolved };
    }

    // Fetch subtemas y resolver
    function fetchSubtemasAndResolve(temaId, methods) {
        fetch(`/api/subtemas/${temaId}`)
            .then(response => response.json())
            .then(subtemas => {
                window._availableSubtemas = subtemas;
                const result = resolveSubtemas(methods, subtemas);

                window._extractedMethods = [...result.resolved, ...result.unresolved];
                window._pendingMethods = null;

                displayExtractedMethods(window._extractedMethods);

                if (result.unresolved.length > 0) {
                    showSubtemaModal(result.unresolved, subtemas);
                } else {
                    updateMethodsHiddenField(window._extractedMethods);
                }
            })
            .catch(error => {
                console.error('Error fetching subtemas:', error);
            });
    }

    // Mostrar lista de métodos detectados
    function displayExtractedMethods(methods) {
        const section = document.getElementById('methodsSection');
        const list = document.getElementById('methodsList');

        if (!methods || methods.length === 0) {
            section.classList.remove('visible');
            list.innerHTML = '';
            return;
        }

        list.innerHTML = '';

        methods.forEach((method, index) => {
            const li = document.createElement('li');
            const hasSubtemas = method.subtema_ids && method.subtema_ids.length > 0;
            const statusClass = hasSubtemas ? 'resolved' : 'unresolved';
            let statusText;
            if (hasSubtemas) {
                const names = method.subtema_ids.map(id => {
                    const sub = window._availableSubtemas.find(s => s.id === id);
                    return sub ? sub.nombre : '?';
                });
                statusText = 'Subtemas: ' + names.join(', ');
            } else if (method.subtema_nombre) {
                statusText = 'Subtema no encontrado: "' + method.subtema_nombre + '"';
            } else {
                statusText = 'Sin subtema asignado';
            }

            li.innerHTML = `
                <span class="method-title">${index + 1}. ${escapeHtml(method.title)}</span>
                <span class="method-subtema ${statusClass}">${statusText}</span>
            `;
            list.appendChild(li);
        });

        section.classList.add('visible');
        document.getElementById('methodsTemaWarning').style.display = 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Serializar métodos al campo hidden
    function updateMethodsHiddenField(methods) {
        const data = methods.filter(m => m.subtema_ids && m.subtema_ids.length > 0).map(m => ({
            title: m.title,
            method_tex: m.content,
            subtema_ids: m.subtema_ids
        }));
        document.getElementById('metodos_json').value = JSON.stringify(data);
    }

    // Modal para asignar subtemas faltantes
    function showSubtemaModal(unresolvedMethods, subtemas) {
        const existing = document.getElementById('subtemaModal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'subtemaModal';
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;';

        let checkboxesHtml = '';
        subtemas.forEach(s => {
            checkboxesHtml += `<label style="display:block;padding:0.25rem 0;cursor:pointer;"><input type="checkbox" value="${s.id}" style="margin-right:0.5rem;"> ${escapeHtml(s.nombre)}</label>`;
        });

        let rowsHtml = '';
        unresolvedMethods.forEach((method, idx) => {
            const hint = method.subtema_nombre
                ? ` (TEX indica: "${escapeHtml(method.subtema_nombre)}")`
                : '';

            rowsHtml += `
                <div style="margin-bottom:1rem;padding:0.75rem;background:#f9fafb;border-radius:4px;border:1px solid #e5e7eb;">
                    <div style="font-weight:600;margin-bottom:0.5rem;">
                        ${escapeHtml(method.title)}${hint}
                    </div>
                    <div class="subtema-modal-checkboxes" data-method-index="${idx}"
                         style="max-height:150px;overflow-y:auto;border:1px solid #cbd5e0;border-radius:4px;padding:0.5rem;background:white;">
                        ${checkboxesHtml}
                    </div>
                </div>
            `;
        });

        const content = document.createElement('div');
        content.style.cssText = 'background:white;padding:2rem;border-radius:8px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;';
        content.innerHTML = `
            <h2 style="margin-top:0;color:#1e40af;">Asignar Subtemas a Métodos</h2>
            <p style="color:#4b5563;margin-bottom:1.5rem;">
                Los siguientes métodos no tienen un subtema asignado en el archivo TEX.
                Selecciona uno o más subtemas para cada uno:
            </p>
            ${rowsHtml}
            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem;">
                <button type="button" id="btnCancelSubtemaModal"
                        style="background:#718096;color:white;border:none;padding:0.75rem 1.5rem;border-radius:4px;cursor:pointer;font-weight:600;">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmSubtemaModal"
                        style="background:#4299e1;color:white;border:none;padding:0.75rem 1.5rem;border-radius:4px;cursor:pointer;font-weight:600;">
                    Confirmar
                </button>
            </div>
        `;

        modal.appendChild(content);
        document.body.appendChild(modal);

        document.getElementById('btnCancelSubtemaModal').onclick = () => modal.remove();

        document.getElementById('btnConfirmSubtemaModal').onclick = () => {
            const groups = content.querySelectorAll('.subtema-modal-checkboxes');
            let allFilled = true;

            groups.forEach(group => {
                const checked = group.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length === 0) {
                    allFilled = false;
                    group.style.borderColor = '#e53e3e';
                } else {
                    group.style.borderColor = '#cbd5e0';
                }
            });

            if (!allFilled) {
                alert('Por favor, selecciona al menos un subtema para cada método.');
                return;
            }

            groups.forEach((group, i) => {
                const method = unresolvedMethods[i];
                const checked = group.querySelectorAll('input[type="checkbox"]:checked');
                method.subtema_ids = Array.from(checked).map(cb => parseInt(cb.value));
            });

            displayExtractedMethods(window._extractedMethods);
            updateMethodsHiddenField(window._extractedMethods);
            modal.remove();
        };

        modal.addEventListener('click', e => {
            if (e.target === modal) modal.remove();
        });
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

            // Detectar imágenes en TODO el contenido TEX (incluye logo, etc.)
            const imageNames = extractImageNames(texContent);
            showImageUploadFields(imageNames);

            // Extraer métodos del preámbulo
            const methods = extractMethodsFromPreamble(preamble);
            if (methods.length > 0) {
                window._extractedMethods = methods;
                displayExtractedMethods(methods);

                const temaId = document.getElementById('theme').value;
                if (temaId) {
                    fetchSubtemasAndResolve(temaId, methods);
                } else {
                    window._pendingMethods = methods;
                    document.getElementById('methodsTemaWarning').style.display = 'block';
                }
            } else {
                window._extractedMethods = [];
                window._pendingMethods = null;
                document.getElementById('methodsSection').classList.remove('visible');
                document.getElementById('metodos_json').value = '';
            }
        };

        reader.onerror = function() {
            alert('Error al leer el archivo TEX. Por favor, intenta de nuevo.');
        };

        reader.readAsText(file);
    });

    // Al cambiar tema, resolver subtemas de métodos pendientes
    document.getElementById('theme').addEventListener('change', function() {
        const temaId = this.value;
        if (temaId && window._extractedMethods && window._extractedMethods.length > 0) {
            // Resetear subtema_ids de todos los métodos para re-resolver
            window._extractedMethods.forEach(m => m.subtema_ids = []);
            fetchSubtemasAndResolve(temaId, window._extractedMethods);
        }
    });

</script>
@endsection
