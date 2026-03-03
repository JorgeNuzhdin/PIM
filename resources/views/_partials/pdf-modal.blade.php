{{-- Modal de selección de versión PDF (con/sin soluciones) --}}
<div id="pdfModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:1.75rem 2rem; min-width:260px; text-align:center; box-shadow:0 4px 24px rgba(0,0,0,0.18);">
        <p style="margin:0 0 1.25rem; font-weight:600; font-size:1rem; color:#2d3748;">Descargar PDF</p>
        <div style="display:flex; gap:0.75rem; justify-content:center;">
            <a id="pdfModalProf" href="#"
               style="display:inline-block; padding:0.6rem 1.1rem; background:#e53e3e; color:#fff; border-radius:4px; text-decoration:none; font-weight:600; font-size:0.9rem;"
               onclick="document.getElementById('pdfModal').style.display='none'">
                Con soluciones
            </a>
            <a id="pdfModalAlum" href="#"
               style="display:inline-block; padding:0.6rem 1.1rem; background:#718096; color:#fff; border-radius:4px; text-decoration:none; font-weight:600; font-size:0.9rem;"
               onclick="document.getElementById('pdfModal').style.display='none'">
                Sin soluciones
            </a>
        </div>
        <button onclick="document.getElementById('pdfModal').style.display='none'"
                style="margin-top:1rem; background:none; border:none; color:#a0aec0; cursor:pointer; font-size:0.85rem;">
            Cancelar
        </button>
    </div>
</div>
<script>
function openPdfModal(btn) {
    document.getElementById('pdfModalProf').href = btn.dataset.urlProf;
    document.getElementById('pdfModalAlum').href = btn.dataset.urlAlum;
    var modal = document.getElementById('pdfModal');
    modal.style.display = 'flex';
    modal.onclick = function(e) { if (e.target === modal) modal.style.display = 'none'; };
}
</script>
