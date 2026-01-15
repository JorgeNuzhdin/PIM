{{-- 
    Añade @include('hojas._carrito_guardar') en tu carrito/index.blade.php
--}}

{{-- Modal para guardar hoja --}}
<div id="modal-guardar-hoja" class="modal-hoja" style="display: none;">
    <div class="modal-hoja-content">
        <div class="modal-hoja-header">
            <h2>Guardar Hoja de Problemas</h2>
            <button type="button" class="modal-hoja-close" onclick="cerrarModalHoja()">&times;</button>
        </div>
        <div class="modal-hoja-body">
            <form id="form-guardar-hoja">
                <div class="form-group-hoja">
                    <label for="modal-nombre-hoja">Nombre de la hoja *</label>
                    <input type="text" id="modal-nombre-hoja" name="nombre_hoja" required 
                           placeholder="Ej: Examen Tema 3">
                </div>
                <div class="form-group-hoja">
                    <label for="modal-nombre-grupo">Grupo</label>
                    <input type="text" id="modal-nombre-grupo" name="nombre_grupo" 
                           placeholder="Ej: 2º ESO A">
                </div>
                <div class="form-group-hoja">
                    <label for="modal-tema">Tema</label>
                    <input type="text" id="modal-tema" name="tema" 
                           placeholder="Ej: Ecuaciones">
                </div>
                <div class="form-group-hoja">
                    <label for="modal-year">Año</label>
                    <input type="number" id="modal-year" name="year" 
                           value="2026" min="2021" max="2050">
                </div>
            </form>
        </div>
        <div class="modal-hoja-footer">
            <button type="button" class="btn-modal btn-modal-cancelar" onclick="cerrarModalHoja()">Cancelar</button>
            <button type="button" class="btn-modal btn-modal-guardar" onclick="guardarHoja()">Guardar</button>
        </div>
    </div>
</div>

<style>
/* Modal */
.modal-hoja {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.modal-hoja-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-hoja-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-hoja-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #2d3748;
}

.modal-hoja-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #a0aec0;
}

.modal-hoja-close:hover {
    color: #2d3748;
}

.modal-hoja-body {
    padding: 1.5rem;
}

.modal-hoja-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.form-group-hoja {
    margin-bottom: 1rem;
}

.form-group-hoja label {
    display: block;
    margin-bottom: 0.25rem;
    font-weight: 500;
    color: #4a5568;
}

.form-group-hoja input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 1rem;
}

.form-group-hoja input:focus {
    outline: none;
    border-color: #4299e1;
    box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.2);
}

/* Botones del modal */
.btn-modal {
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-modal:hover {
    transform: translateY(-1px);
}

.btn-modal-cancelar {
    background: #90cdf4;
    color: #2a4365;
}

.btn-modal-cancelar:hover {
    background: #63b3ed;
    color: white;
}

.btn-modal-guardar {
    background: #3182ce;
}

.btn-modal-guardar:hover {
    background: #2c5282;
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.4);
}
</style>

<script>
function abrirModalGuardar() {
    const items = document.querySelectorAll('.carrito-item');
    if (items.length === 0) {
        alert('El carrito está vacío');
        return;
    }
    document.getElementById('modal-guardar-hoja').style.display = 'flex';
}

function cerrarModalHoja() {
    document.getElementById('modal-guardar-hoja').style.display = 'none';
}

function guardarHoja() {
    // Obtener los problema_id de los items del carrito
    const items = document.querySelectorAll('.carrito-item');
    const problemas = Array.from(items).map(item => {
        // Extraer problema_id del botón remove
        const btn = item.querySelector('.btn-remove');
        const onclickAttr = btn.getAttribute('onclick');
        const match = onclickAttr.match(/removeFromCarrito\((\d+)/);
        return match ? parseInt(match[1]) : null;
    }).filter(id => id !== null);
    
    if (problemas.length === 0) {
        alert('El carrito está vacío');
        return;
    }

    const nombreHoja = document.getElementById('modal-nombre-hoja').value;
    if (!nombreHoja.trim()) {
        alert('El nombre de la hoja es obligatorio');
        return;
    }

    const formData = {
        nombre_hoja: nombreHoja,
        nombre_grupo: document.getElementById('modal-nombre-grupo').value,
        tema: document.getElementById('modal-tema').value,
        year: document.getElementById('modal-year').value || null,
        problemas: problemas
    };

    fetch('{{ route("hojas.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Hoja guardada correctamente');
            cerrarModalHoja();
            document.getElementById('form-guardar-hoja').reset();
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar la hoja');
    });
}

// Cierra el modal al hacer clic fuera
document.getElementById('modal-guardar-hoja')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalHoja();
    }
});
</script>