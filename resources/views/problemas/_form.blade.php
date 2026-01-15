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
    </div>
    <small style="color: #718096; display: block; margin-top: 0.5rem;">
            El archivo debe contener la estructura estándar con \temas{}, \dificultad{},\fuente{},\curso{}, \comentarios{},ejer,pistas, proof
        </small>
</div>

<div style="border-top: 2px solid #e2e8f0; margin: 2rem 0; padding-top: 2rem;"></div>
@endif

{{-- Fila 1: Nivel, Tema, Año --}}
<div class="form-row">
    <div class="form-group">
        <label for="difficulty">Nivel (1-6)</label>
        <select name="difficulty" id="difficulty">
            <option value="">-- Seleccionar --</option>
            @for($i = 1; $i <= 6; $i++)
                <option value="{{ $i }}" {{ old('difficulty', $problema->difficulty ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
        </select>
    </div>
    
    <div class="form-group">
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
    
    <div class="form-group">
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
<div class="form-group">
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
<div class="form-group">
    <label for="title">Título (opcional)</label>
    <input type="text" name="title" id="title" value="{{ old('title', $problema->title ?? '') }}" placeholder="Título del problema">
</div>

{{-- Enunciado --}}
<div class="form-group">
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
<div class="form-group">
    <label for="hints">Pistas</label>
    <textarea name="hints" id="hints" style="min-height: 100px;">{{ old('hints', $problema->hints ?? '') }}</textarea>
</div>

{{-- Solución --}}
<div class="form-group">
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
<div class="form-group">
    <label for="source">Fuente</label>
    <input type="text" name="source" id="source" value="{{ old('source', $problema->source ?? '') }}" placeholder="Origen del problema">
</div>

{{-- Imágenes --}}
<div class="form-group">
    <label>{{ isset($problema) ? 'Imágenes adicionales' : 'Imágenes' }}</label>
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
    <button type="submit" class="btn-primary">{{ isset($problema) ? 'Actualizar' : 'Crear' }} Problema</button>
</div>