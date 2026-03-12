{{-- Modal para reportar errores en un método --}}
<div id="reportErrorMetodoModal" style="display:none; position:fixed; inset:0; z-index:2100; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:white; border-radius:8px; padding:1.5rem; width:90%; max-width:480px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <h3 style="margin:0 0 1rem; color:#4a5568; font-size:1.1rem;">⚠️ Reportar un error en el método</h3>

        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; color:#4a5568; margin-bottom:0.4rem; font-size:0.9rem;">Descripción del error <span style="color:#e53e3e;">*</span></label>
            <textarea id="reportErrorMetodoTexto" rows="3"
                style="width:100%; padding:0.6rem; border:1px solid #cbd5e0; border-radius:4px; font-size:0.9rem; resize:vertical; box-sizing:border-box;"
                placeholder="Describe brevemente el error encontrado..."></textarea>
        </div>

        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; color:#4a5568; margin-bottom:0.5rem; font-size:0.9rem;">
                Campos afectados <span style="color:#e53e3e;">*</span>
                <span style="font-weight:400; color:#718096;">(marca al menos uno)</span>
            </label>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.3rem;">
                <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.875rem; cursor:pointer;">
                    <input type="checkbox" class="report-metodo-tipo-cb" value="0"> Título
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.875rem; cursor:pointer;">
                    <input type="checkbox" class="report-metodo-tipo-cb" value="1"> Tema
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.875rem; cursor:pointer;">
                    <input type="checkbox" class="report-metodo-tipo-cb" value="2"> Subtema
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.875rem; cursor:pointer;">
                    <input type="checkbox" class="report-metodo-tipo-cb" value="3"> Contenido
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.875rem; cursor:pointer;">
                    <input type="checkbox" class="report-metodo-tipo-cb" value="4"> Imágenes
                </label>
            </div>
            <p id="reportErrorMetodoCbError" style="display:none; color:#e53e3e; font-size:0.8rem; margin:0.3rem 0 0;">Debes marcar al menos un campo afectado.</p>
        </div>

        <div id="reportErrorMetodoSuccess" style="display:none; background:#c6f6d5; color:#22543d; border:1px solid #9ae6b4; border-radius:4px; padding:0.6rem 0.8rem; font-size:0.9rem; margin-bottom:0.8rem;">
            ✓ Información enviada. ¡Gracias!
        </div>

        <div style="display:flex; gap:0.8rem; justify-content:flex-end;">
            <button type="button" onclick="closeReportMetodoModal()"
                style="padding:0.5rem 1rem; border:1px solid #cbd5e0; border-radius:4px; background:white; cursor:pointer; font-size:0.9rem; color:#4a5568;">
                Cancelar
            </button>
            <button type="button" id="reportErrorMetodoSubmitBtn" onclick="submitReportErrorMetodo()"
                style="padding:0.5rem 1rem; border:none; border-radius:4px; background:#e53e3e; color:white; cursor:pointer; font-size:0.9rem; font-weight:600;">
                Enviar reporte
            </button>
        </div>
    </div>
</div>

<script>
var _reportErrorMetodoId = null;

function openReportMetodoModal(metodoId) {
    _reportErrorMetodoId = metodoId;
    document.getElementById('reportErrorMetodoTexto').value = '';
    document.querySelectorAll('.report-metodo-tipo-cb').forEach(cb => cb.checked = false);
    document.getElementById('reportErrorMetodoCbError').style.display = 'none';
    document.getElementById('reportErrorMetodoSuccess').style.display = 'none';
    document.getElementById('reportErrorMetodoSubmitBtn').disabled = false;
    document.getElementById('reportErrorMetodoSubmitBtn').style.opacity = '1';
    document.getElementById('reportErrorMetodoModal').style.display = 'flex';
}

function closeReportMetodoModal() {
    document.getElementById('reportErrorMetodoModal').style.display = 'none';
}

function submitReportErrorMetodo() {
    var texto = document.getElementById('reportErrorMetodoTexto').value.trim();
    var checked = Array.from(document.querySelectorAll('.report-metodo-tipo-cb:checked'));

    if (checked.length === 0) {
        document.getElementById('reportErrorMetodoCbError').style.display = 'block';
        return;
    }
    document.getElementById('reportErrorMetodoCbError').style.display = 'none';

    // Build 5-bit tipo string
    var tipo = '00000'.split('');
    checked.forEach(cb => { tipo[parseInt(cb.value)] = '1'; });
    var tipoStr = tipo.join('');

    var btn = document.getElementById('reportErrorMetodoSubmitBtn');
    btn.disabled = true;
    btn.style.opacity = '0.6';

    fetch('/metodos/' + _reportErrorMetodoId + '/reportar-error', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ reporte: texto, tipo: tipoStr }),
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reportErrorMetodoSuccess').style.display = 'block';
            setTimeout(() => closeReportMetodoModal(), 1500);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
}

document.getElementById('reportErrorMetodoModal').addEventListener('click', function(e) {
    if (e.target === this) closeReportMetodoModal();
});
</script>
