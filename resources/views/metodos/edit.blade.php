@extends('layouts.main')

@section('title', 'Editar Método')

@section('styles')
<style>
    .form-container {
        max-width: 1100px;
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

    .form-group input[type="text"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .latex-editor-grid {
        display: flex;
        gap: 1rem;
        align-items: stretch;
    }

    .latex-editor-grid > div:first-child {
        flex: 0 0 calc(50% - 0.5rem);
        display: flex;
        flex-direction: column;
    }

    .latex-editor-grid > div:last-child {
        flex: 1 0 calc(50% - 0.5rem);
        display: flex;
        flex-direction: column;
    }

    .latex-input {
        min-height: 350px;
        font-family: 'Courier New', monospace;
        resize: vertical;
        flex: 1;
    }

    .latex-preview {
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        background: #f7fafc;
        padding: 1rem;
        min-height: 350px;
        overflow-y: auto;
        line-height: 1.8;
        flex: 1;
    }

    .latex-preview p {
        margin: 0;
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
        text-decoration: none;
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

    .subtema-checkboxes {
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        padding: 0.75rem;
        max-height: 200px;
        overflow-y: auto;
        background: #f7fafc;
    }

    .subtema-checkboxes label {
        display: block;
        font-weight: normal;
        padding: 0.25rem 0;
        cursor: pointer;
    }

    .subtema-checkboxes label:hover {
        background: #edf2f7;
    }

    .subtema-checkboxes input[type="checkbox"] {
        margin-right: 0.5rem;
    }

    .subtema-placeholder {
        color: #a0aec0;
        font-style: italic;
        padding: 0.5rem 0;
    }

    .add-subtema-btn {
        display: inline-block;
        margin-top: 0.5rem;
        background: none;
        border: 1px dashed #4299e1;
        color: #4299e1;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .add-subtema-btn:hover {
        background: #ebf8ff;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .latex-editor-grid {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
<div class="container">
    <h1 style="margin-bottom: 1.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="#4299e1" style="vertical-align:middle;margin-right:0.3em;"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> Editar Método</h1>

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

        <form action="{{ route('metodos.update', $metodo->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Título --}}
            <div class="form-group">
                <label for="title">Título *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $metodo->title) }}" required placeholder="Título del método">
            </div>

            {{-- Tema --}}
            <div class="form-group">
                <label for="tema_id">Tema *</label>
                <select name="tema_id" id="tema_id" required>
                    <option value="">-- Seleccionar tema --</option>
                    @foreach($temas as $tema)
                        <option value="{{ $tema->id }}" {{ old('tema_id', $metodo->tema_id) == $tema->id ? 'selected' : '' }}>
                            {{ $tema->tema }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Subtemas (checkboxes) --}}
            <div class="form-group">
                <label>Subtemas *</label>
                <div class="subtema-checkboxes" id="subtema_checkboxes">
                    @foreach($subtemas as $subtema)
                        <label>
                            <input type="checkbox" name="subtema_ids[]" value="{{ $subtema->id }}"
                                {{ in_array($subtema->id, old('subtema_ids', $metodo->subtema_ids_array)) ? 'checked' : '' }}>
                            {{ $subtema->nombre }}
                        </label>
                    @endforeach
                </div>
                @if(Auth::user()->isAdmin())
                    <button type="button" class="add-subtema-btn" id="addSubtemaBtn" onclick="promptNewSubtema()">+ Nuevo subtema</button>
                @endif
            </div>

            {{-- Institución --}}
            <div class="form-group">
                <label for="institution">Institución</label>
                <input type="text" name="institution" id="institution" value="{{ old('institution', $metodo->institution ?? 'PIM') }}" placeholder="Institución (por defecto PIM)">
            </div>

            {{-- Proponente (solo admin) --}}
            @if(Auth::user()->isAdmin())
            <div class="form-group">
                <label for="user_id">Proponente</label>
                <select name="user_id" id="user_id">
                    @foreach($editores as $editor)
                        <option value="{{ $editor->id }}" {{ old('user_id', $metodo->user_id) == $editor->id ? 'selected' : '' }}>
                            {{ $editor->name }} ({{ $editor->rol }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Editor LaTeX --}}
            <div class="form-group">
                <div class="latex-editor-grid">
                    <div>
                        <label for="method_tex">Método (LaTeX) *</label>
                        <textarea name="method_tex" id="method_tex" class="latex-input" required>{{ old('method_tex', $metodo->method_tex) }}</textarea>
                    </div>
                    <div>
                        <label>Vista previa</label>
                        <div id="method_preview" class="latex-preview">
                            <p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="{{ route('metodos.show', $metodo->id) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';
const currentSubtemaIds = {!! json_encode($metodo->subtema_ids_array) !!};

// Cargar subtemas como checkboxes al cambiar tema
document.getElementById('tema_id').addEventListener('change', function() {
    const temaId = this.value;
    const container = document.getElementById('subtema_checkboxes');

    if (!temaId) {
        container.innerHTML = '<span class="subtema-placeholder">Selecciona un tema primero</span>';
        return;
    }

    container.innerHTML = '<span class="subtema-placeholder">Cargando...</span>';

    fetch(`/api/subtemas/${temaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<span class="subtema-placeholder">No hay subtemas para este tema</span>';
            } else {
                container.innerHTML = '';
                data.forEach(s => {
                    const checked = currentSubtemaIds.includes(s.id) ? 'checked' : '';
                    container.innerHTML += `<label><input type="checkbox" name="subtema_ids[]" value="${s.id}" ${checked}> ${s.nombre}</label>`;
                });
            }
        })
        .catch(error => {
            console.error('Error cargando subtemas:', error);
            container.innerHTML = '<span class="subtema-placeholder">Error al cargar</span>';
        });
});

// Crear nuevo subtema (admin)
function promptNewSubtema() {
    const nombre = prompt('Nombre del nuevo subtema:');
    if (!nombre || !nombre.trim()) return;

    const temaId = document.getElementById('tema_id').value;
    if (!temaId) {
        alert('Selecciona un tema primero.');
        return;
    }

    fetch('/api/subtemas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ nombre: nombre.trim(), tema_id: temaId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        const container = document.getElementById('subtema_checkboxes');
        const placeholder = container.querySelector('.subtema-placeholder');
        if (placeholder) placeholder.remove();
        container.innerHTML += `<label><input type="checkbox" name="subtema_ids[]" value="${data.id}" checked> ${data.nombre}</label>`;
    })
    .catch(error => {
        console.error('Error creando subtema:', error);
        alert('Error al crear subtema.');
    });
}

// Vista previa LaTeX en tiempo real
let methodPreviewTimeout;

document.getElementById('method_tex').addEventListener('input', function() {
    clearTimeout(methodPreviewTimeout);
    const texContent = this.value;
    const previewDiv = document.getElementById('method_preview');

    if (!texContent.trim()) {
        previewDiv.innerHTML = '<p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>';
        return;
    }

    methodPreviewTimeout = setTimeout(() => {
        previewDiv.innerHTML = '<p style="color: #4a5568;">⏳ Procesando...</p>';

        fetch('{{ route("latex.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ latex: texContent })
        })
        .then(response => response.json())
        .then(data => {
            if (data.html) {
                previewDiv.innerHTML = data.html;
                if (window.MathJax) {
                    MathJax.typesetPromise([previewDiv]).catch(err => console.error('MathJax error:', err));
                }
            } else if (data.error) {
                previewDiv.innerHTML = '<p style="color: #e53e3e;">Error: ' + data.error + '</p>';
            }
        })
        .catch(error => {
            console.error('Error al procesar LaTeX:', error);
            previewDiv.innerHTML = '<p style="color: #e53e3e;">Error al procesar la vista previa</p>';
        });
    }, 500);
});

// Renderizar vista previa al cargar si hay contenido
if (document.getElementById('method_tex').value.trim()) {
    document.getElementById('method_tex').dispatchEvent(new Event('input'));
}
</script>
@endsection
