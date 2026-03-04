{{-- Cargar archivo TEX (solo en crear) --}}
@if(!isset($problema))
<div class="form-group">
    <label>📄 Cargar desde archivo .tex (opcional)</label>
    <div class="tex-upload-container">
        <input type="file"
               id="tex-file"
               accept=".tex"
               onchange="procesarArchivoTex(this)">
        <button type="button"
                class="btn-secondary btn-limpiar"
                onclick="limpiarFormulario()">
            🔄 Limpiar
        </button>
        <a href="/tex/plantilla.tex" download class="btn-secondary" style="text-decoration:none; display:inline-block; padding:0.4rem 0.8rem; border-radius:4px; font-size:0.875rem;">
            📥 Descargar plantilla
        </a>
    </div>
    <small class="tex2jax_ignore" style="color: #718096; display: block; margin-top: 0.5rem;">
        El archivo debe contener la estructura estándar:
        <code>\título{}</code>, <code>\temas{}</code>, <code>\dificultad{}</code>,
        <code>\fuente{}</code>, <code>\curso{}</code>, <code>\comentarios{}</code>,
        <code>\begin{ejer}...\end{ejer}</code>, <code>\begin{pistas}...\end{pistas}</code>,
        <code>\begin{proof}...\end{proof}</code>
    </small>
</div>

<div style="border-top: 2px solid #e2e8f0; margin: 2rem 0; padding-top: 2rem;"></div>
@endif

@php $et = $errorTipo ?? '000000000'; @endphp

{{-- Fila 1: Nivel, Tema, Año --}}
<div class="form-row">
    <div class="form-group {{ $et[0] === '1' ? 'field-error' : '' }}">
        <label for="difficulty">Nivel (1-6)</label>
        <select name="difficulty" id="difficulty">
            <option value="">-- Seleccionar --</option>
            @for($i = 1; $i <= 6; $i++)
                <option value="{{ $i }}" {{ old('difficulty', $problema->difficulty ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
        </select>
    </div>

    <div class="form-group {{ $et[1] === '1' ? 'field-error' : '' }}">
        <label for="tema_id">
            Tema
            <small id="tema-auto-indicator" style="color: #48bb78; font-weight: normal; display: none;">
                ✨ Auto-detectado
            </small>
        </label>
        <select name="tema_id" id="tema_id">
            <option value="">-- Seleccionar --</option>
            @foreach($temas as $tema)
                <option value="{{ $tema->id }}" {{ old('tema_id') == $tema->id ? 'selected' : '' }}>
                    {{ $tema->tema }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group {{ $et[2] === '1' ? 'field-error' : '' }}">
        <label for="school_year">Año académico</label>
        <select name="school_year" id="school_year">
            <option value="">-- Seleccionar --</option>
            @foreach($schoolYears as $index => $yearName)
                @php
                    $selectedIndex = null;
                    if (isset($problema)) {
                        foreach($schoolYears as $idx => $name) {
                            if ($name === $problema->school_year) {
                                $selectedIndex = $idx;
                                break;
                            }
                        }
                    }
                @endphp
                <option value="{{ $index }}" {{ old('school_year', $selectedIndex) == $index ? 'selected' : '' }}>
                    {{ $yearName }}
                </option>
            @endforeach
        </select>
    </div>
</div>

{{-- Tags --}}
<div class="form-group {{ $et[1] === '1' ? 'field-error' : '' }}">
    <label>Tags</label>
    <div class="tags-container" id="tags-container">
        @php
            $tags = isset($problema) ? $problema->tags->pluck('tag')->toArray() : [];
            $oldTags = old('tags', $tags);
        @endphp

        @if(empty($oldTags))
            <div class="tag-input-row" style="position: relative;">
                <input type="text" name="tags[]" class="tag-input" placeholder="Escribe un tag..." autocomplete="off">
                <div class="tag-suggestions"></div>
                <button type="button" class="btn-add-tag" onclick="addTagInput()">+</button>
            </div>
        @else
            @foreach($oldTags as $index => $tag)
                <div class="tag-input-row" style="position: relative;">
                    <input type="text" name="tags[]" class="tag-input" value="{{ $tag }}" placeholder="Escribe un tag..." autocomplete="off">
                    <div class="tag-suggestions"></div>
                    @if($index === 0)
                        <button type="button" class="btn-add-tag" onclick="addTagInput()">+</button>
                    @else
                        <button type="button" class="btn-remove-tag" onclick="this.parentElement.remove()">−</button>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Título --}}
<div class="form-group {{ $et[3] === '1' ? 'field-error' : '' }}">
    <label for="title">Título (opcional)</label>
    <input type="text" name="title" id="title" value="{{ old('title', $problema->title ?? '') }}" placeholder="Título del problema">
</div>

{{-- Enunciado --}}
<div class="form-group {{ $et[4] === '1' ? 'field-error' : '' }}">
    <div class="latex-editor-grid">
        <div>
            <label for="problem_tex">Enunciado (LaTeX) *</label>
            <textarea name="problem_tex" id="problem_tex" class="latex-input" required>{{ old('problem_tex', $problema->problem_tex ?? '') }}</textarea>
        </div>
        <div>
            <label>Vista previa</label>
            <div id="problem_preview" class="latex-preview">
                <p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>
            </div>
        </div>
    </div>
</div>

{{-- Pistas --}}
<div class="form-group {{ $et[5] === '1' ? 'field-error' : '' }}">
    <label for="hints">Pistas</label>
    <textarea name="hints" id="hints" style="min-height: 100px;">{{ old('hints', $problema->hints ?? '') }}</textarea>
</div>

{{-- Solución --}}
<div class="form-group {{ $et[6] === '1' ? 'field-error' : '' }}">
    <div class="latex-editor-grid">
        <div>
            <label for="solution_tex">Solución (LaTeX)</label>
            <textarea name="solution_tex" id="solution_tex" class="latex-input">{{ old('solution_tex', $problema->solution_tex ?? '') }}</textarea>
        </div>
        <div>
            <label>Vista previa</label>
            <div id="solution_preview" class="latex-preview">
                <p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>
            </div>
        </div>
    </div>
</div>

{{-- Comentarios --}}
<div class="form-group">
    <label for="comments">Comentarios</label>
    <textarea name="comments" id="comments" style="min-height: 100px;">{{ old('comments', $problema->comments ?? '') }}</textarea>
</div>

{{-- Fuente --}}
<div class="form-group {{ $et[7] === '1' ? 'field-error' : '' }}">
    <label for="source">Fuente</label>
    <input type="text" name="source" id="source" value="{{ old('source', $problema->source ?? '') }}" placeholder="Origen del problema">
</div>

{{-- Proponente (solo admin en edición) --}}
@if(isset($problema) && Auth::user()->isAdmin())
<div class="form-group">
    <label for="proponent_id">Proponente</label>
    <select name="proponent_id" id="proponent_id">
        <option value="">-- Sin proponente --</option>
        @foreach($proponents ?? [] as $proponent)
            <option value="{{ $proponent->id }}" {{ old('proponent_id', $problema->proponent_id) == $proponent->id ? 'selected' : '' }}>
                {{ $proponent->name }}
            </option>
        @endforeach
    </select>
</div>
@endif

{{-- Imágenes existentes (solo en edición) --}}
@if(isset($problema) && isset($figuras) && $figuras->count() > 0)
<div class="form-group">
    <label>📷 Imágenes existentes</label>
    <div class="existing-images-list">
        @foreach($figuras as $figura)
            <div class="existing-image-item">
                <span class="image-name">📄 {{ $figura->title }}</span>
                <span class="image-size">{{ number_format(strlen($figura->figure) / 1024, 1) }} KB</span>
            </div>
        @endforeach
    </div>
    <small style="color: #718096; margin-top: 0.5rem; display: block;">
        Si subes una imagen con el mismo nombre, se sustituirá automáticamente.
    </small>
</div>
@endif

{{-- Imágenes --}}
<div class="form-group {{ $et[8] === '1' ? 'field-error' : '' }}">
    <label>{{ isset($problema) ? 'Añadir nuevas imágenes' : 'Imágenes' }}</label>
    <div class="image-upload-area" onclick="document.getElementById('imagenes').click()">
        <p>📁 Haz clic para {{ isset($problema) ? 'agregar más' : 'seleccionar' }} imágenes</p>
        <small>Formatos: JPG, PNG, GIF, PDF (máx. 5MB cada una)</small>
        <input type="file" name="imagenes[]" id="imagenes" multiple accept="image/*,.pdf" onchange="showFileNames(this)">
    </div>
    <div id="file-list" style="margin-top: 0.5rem; color: #4a5568;"></div>
</div>

{{-- Botones --}}
<div class="form-actions">
    <a href="{{ route('problemas.index') }}" class="btn-cancel">Cancelar</a>
    <button type="submit" class="btn-primary" id="btn-submit-problema">{{ isset($problema) ? 'Actualizar' : 'Crear' }} Problema</button>
</div>

@if(!isset($problema))
<div id="duplicate-warning" style="display:none; margin-top:1rem; padding:1rem 1.25rem; border-radius:6px; border:1px solid #d69e2e; background:#fffbeb; color:#744210;">
    <span id="duplicate-warning-text"></span>
    <div style="margin-top:0.75rem; display:flex; gap:0.75rem;">
        <button type="button" id="btn-dup-cancel" style="background:#718096;color:white;border:none;padding:0.45rem 1rem;border-radius:4px;cursor:pointer;">Cancelar</button>
        <button type="button" id="btn-dup-force" style="background:#e53e3e;color:white;border:none;padding:0.45rem 1rem;border-radius:4px;cursor:pointer;">Crear de todos modos</button>
    </div>
</div>

<script>
(function () {
    let forceSubmit = false;

    const form = document.getElementById('btn-submit-problema').closest('form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        if (forceSubmit) return; // segunda vez: dejar pasar

        const title = (document.getElementById('title')?.value || '').trim();
        const problemTex = (document.getElementById('problem_tex')?.value || '').trim();

        if (!problemTex) return; // sin contenido, no chequeamos

        e.preventDefault();

        const contentPrefix = problemTex.trim().replace(/\s+/g, ' ').substring(0, 100);
        const warningDiv  = document.getElementById('duplicate-warning');
        const warningText = document.getElementById('duplicate-warning-text');

        try {
            const resp = await fetch('{{ route("api.check-duplicates.problemas") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ items: [{ title, content_prefix: contentPrefix }] })
            });
            const results = await resp.json();
            const r = results[0] || {};

            const msgs = [];
            if (r.content_match) msgs.push(`Ya existe el Problema #${r.content_match.id} con contenido similar o igual.`);
            else if (r.title_match) msgs.push(`Ya existe el Problema #${r.title_match.id} con el mismo título.`);

            if (msgs.length === 0) {
                // Sin duplicados: enviar
                forceSubmit = true;
                form.submit();
                return;
            }

            warningText.textContent = msgs.join(' ');
            warningDiv.style.display = 'block';
            warningDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        } catch (err) {
            console.warn('Error al comprobar duplicados:', err);
            forceSubmit = true;
            form.submit();
        }
    });

    document.getElementById('btn-dup-cancel').onclick = () => {
        document.getElementById('duplicate-warning').style.display = 'none';
    };

    document.getElementById('btn-dup-force').onclick = () => {
        forceSubmit = true;
        form.submit();
    };
})();
</script>
@endif
