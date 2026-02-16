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
    <h1 style="margin-bottom: 1.5rem;">Editar Método</h1>

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

            {{-- Tema y Subtema --}}
            <div class="form-row">
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

                <div class="form-group">
                    <label for="subtema_id">Subtema *</label>
                    <select name="subtema_id" id="subtema_id" required>
                        <option value="">-- Seleccionar subtema --</option>
                        @foreach($subtemas as $subtema)
                            <option value="{{ $subtema->id }}" {{ old('subtema_id', $metodo->subtema_id) == $subtema->id ? 'selected' : '' }}>
                                {{ $subtema->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

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
// Cargar subtemas dinámicamente al cambiar tema
document.getElementById('tema_id').addEventListener('change', function() {
    const temaId = this.value;
    const subtemaSelect = document.getElementById('subtema_id');

    subtemaSelect.innerHTML = '<option value="">Cargando...</option>';

    if (!temaId) {
        subtemaSelect.innerHTML = '<option value="">-- Selecciona un tema primero --</option>';
        return;
    }

    fetch(`/api/subtemas/${temaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                subtemaSelect.innerHTML = '<option value="">-- No hay subtemas para este tema --</option>';
                return;
            }
            let options = '<option value="">-- Seleccionar subtema --</option>';
            data.forEach(s => {
                options += `<option value="${s.id}">${s.nombre}</option>`;
            });
            subtemaSelect.innerHTML = options;
        })
        .catch(error => {
            console.error('Error cargando subtemas:', error);
            subtemaSelect.innerHTML = '<option value="">-- Error al cargar --</option>';
        });
});

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
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
