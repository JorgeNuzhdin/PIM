<style>
.form-container {
    max-width: 900px;
    margin: 2rem auto;
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.form-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}
.form-header h1 {
    color: #2d3748;
    margin: 0;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #4a5568;
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
.form-group textarea {
    min-height: 150px;
    font-family: 'Courier New', monospace;
    resize: vertical;
}
.form-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.tags-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.tag-input-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.tag-input-row input {
    flex: 1;
}
.btn-add-tag {
    background: #48bb78;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1.2rem;
}
.btn-remove-tag {
    background: #e53e3e;
    color: white;
    border: none;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    cursor: pointer;
}
.btn-add-tag:hover {
    background: #38a169;
}
.btn-remove-tag:hover {
    background: #c53030;
}
.tag-suggestions {
    position: absolute;
    background: white;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.tag-suggestion-item {
    padding: 0.5rem;
    cursor: pointer;
}
.tag-suggestion-item:hover {
    background-color: #f7fafc;
}
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #e2e8f0;
}
.btn-primary {
    background: #4299e1;
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
}
.btn-primary:hover {
    background: #3182ce;
}
.btn-cancel {
    background: #718096;
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
}
.btn-cancel:hover {
    background: #4a5568;
}
.image-upload-area {
    border: 2px dashed #cbd5e0;
    border-radius: 4px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.image-upload-area:hover {
    border-color: #4299e1;
    background: #f7fafc;
}
.image-upload-area input[type="file"] {
    display: none;
}
.tex-upload-container {
    display: flex;
    gap: 1rem;
    align-items: center;
}
.tex-upload-container input[type="file"] {
    flex: 1;
}
.btn-limpiar {
    padding: 0.6rem 1rem;
    white-space: nowrap;
}
@media (max-width: 768px) {
    .form-container {
        padding: 1rem;
        margin: 1rem;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
    .form-actions {
        flex-direction: column;
    }
    .tex-upload-container {
        flex-direction: column;
        align-items: stretch;
    }
    .tex-upload-container input[type="file"] {
        width: 100%;
    }
    .btn-limpiar {
        width: 100%;
    }
}
</style>