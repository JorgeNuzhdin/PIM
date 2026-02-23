<script>
console.log('_scripts.blade.php cargado - versión con soporte de título y llaves anidadas');
// Vista previa LaTeX en tiempo real
let problemPreviewTimeout;
let solutionPreviewTimeout;

function updatePreview(inputId, previewId) {
    // Usar el timeout correcto según el input
    if (inputId === 'problem_tex') {
        clearTimeout(problemPreviewTimeout);
    } else {
        clearTimeout(solutionPreviewTimeout);
    }
    
    const timeout = setTimeout(() => {
        const texContent = document.getElementById(inputId).value;
        const previewDiv = document.getElementById(previewId);
        
        if (!texContent.trim()) {
            previewDiv.innerHTML = '<p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>';
            return;
        }
        
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
                // Renderizar MathJax en el nuevo contenido
                if (window.MathJax) {
                    MathJax.typesetPromise([previewDiv]).catch(err => console.error('MathJax error:', err));
                }
            } else if (data.error) {
                previewDiv.innerHTML = '<p style="color: #e53e3e;">❌ Error: ' + data.error + '</p>';
            }
        })
        .catch(error => {
            console.error('Error al procesar LaTeX:', error);
            previewDiv.innerHTML = '<p style="color: #e53e3e;">❌ Error al procesar la vista previa</p>';
        });
    }, 500);
    
    if (inputId === 'problem_tex') {
        problemPreviewTimeout = timeout;
    } else {
        solutionPreviewTimeout = timeout;
    }
}


// Los eventos se adjuntan en el segundo DOMContentLoaded más abajo


// Función para mostrar nombres de archivos seleccionados
function showFileNames(input) {
    const fileList = document.getElementById('file-list');
    if (!fileList) return;

    if (input.files.length === 0) {
        fileList.innerHTML = '';
        return;
    }

    const names = Array.from(input.files).map(f => `📎 ${f.name}`).join('<br>');
    fileList.innerHTML = names;
}

// Función para limpiar el formulario
function limpiarFormulario() {
    // Limpiar input de archivo
    document.getElementById('tex-file').value = '';

    // Limpiar campos del formulario
    document.getElementById('difficulty').value = '';
    document.getElementById('school_year').value = '';
    document.getElementById('source').value = '';
    document.getElementById('title').value = '';
    document.getElementById('problem_tex').value = '';
    document.getElementById('hints').value = '';
    document.getElementById('solution_tex').value = '';
    document.getElementById('comments').value = '';

    // Limpiar tema si existe
    const temaSelect = document.getElementById('tema_id');
    if (temaSelect) {
        temaSelect.value = '';
    }

    // Resetear tags a uno vacío
    const container = document.getElementById('tags-container');
    container.innerHTML = `
        <div class="tag-input-row" style="position: relative;">
            <input type="text" name="tags[]" class="tag-input" placeholder="Escribe un tag..." autocomplete="off">
            <div class="tag-suggestions"></div>
            <button type="button" class="btn-add-tag" onclick="addTagInput()">+</button>
        </div>
    `;
    attachTagAutocomplete(container.querySelector('.tag-input'));

    // Limpiar vistas previas
    document.getElementById('problem_preview').innerHTML = '<p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>';
    document.getElementById('solution_preview').innerHTML = '<p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>';

    // Ocultar indicador de auto-detección si existe
    const autoIndicator = document.getElementById('tema-auto-indicator');
    if (autoIndicator) {
        autoIndicator.style.display = 'none';
    }
}

function procesarArchivoTex(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (!file.name.endsWith('.tex')) {
        alert('Por favor, selecciona un archivo .tex válido');
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const contenido = e.target.result;
        
        // Verificar estructura básica
        if (!contenido.includes('\\begin{document}') || !contenido.includes('\\end{document}')) {
            alert('El archivo no contiene la estructura \\begin{document} ... \\end{document}');
            return;
        }
        
        // Extraer ejercicios
        const ejercicios = extraerEjercicios(contenido);
        
        if (ejercicios.length === 0) {
            alert('No se encontraron ejercicios en el formato esperado');
            return;
        }
        
        if (ejercicios.length === 1) {
            // Un solo ejercicio: rellenar el formulario
            rellenarFormulario(ejercicios[0]);
            alert('✅ Archivo cargado. Revisa los campos y haz clic en "Crear Problema"');
        } else {
            // Múltiples ejercicios: validar + comprobar duplicados + mostrar preview
            analizarYMostrarPreview(ejercicios, contenido);
        }
    };
    
    reader.readAsText(file);
}

// Extraer ejercicios del contenido .tex
function extraerEjercicios(contenido) {
    const ejercicios = [];

    // Buscar todo entre \begin{document} y \end{document}
    const docMatch = contenido.match(/\\begin\{document\}([\s\S]*?)\\end\{document\}/);
    if (!docMatch) return ejercicios;

    const documento = docMatch[1];

    // Buscar todos los bloques de ejercicios
    const regexEjer = /\\begin\{ejer\}([\s\S]*?)\\end\{ejer\}/g;
    let match;
    let lastEjerEnd = 0;

    while ((match = regexEjer.exec(documento)) !== null) {
        // Buscar metadatos SOLO entre el ejercicio anterior y el actual
        const bloqueMetadatos = documento.substring(lastEjerEnd, match.index);

        const temas = extraerComando(bloqueMetadatos, 'temas') || '';
        const dificultad = extraerComando(bloqueMetadatos, 'dificultad') || '';
        const fuente = extraerComando(bloqueMetadatos, 'fuente') || '';
        const curso = extraerComando(bloqueMetadatos, 'curso') || '';
        const titulo = extraerComando(bloqueMetadatos, 'title') || '';
        const comentarios = extraerComando(bloqueMetadatos, 'comentarios') || '';

        // Extraer enunciado
        const enunciado = match[1].trim();

        // Determinar el final del bloque del ejercicio actual
        // (desde el final de \end{ejer} hasta el siguiente \begin{ejer} o final del documento)
        const despuesEjer = documento.substring(match.index + match[0].length);
        const siguienteEjerMatch = despuesEjer.match(/\\begin\{ejer\}/);
        const finBloque = siguienteEjerMatch ? despuesEjer.substring(0, siguienteEjerMatch.index) : despuesEjer;

        // Buscar pistas en el bloque limitado
        const pistasMatch = finBloque.match(/\\begin\{pistas\}([\s\S]*?)\\end\{pistas\}/);
        const pistas = pistasMatch ? pistasMatch[1].trim() : '';

        // Buscar solución en el bloque limitado
        const solucionMatch = finBloque.match(/\\begin\{proof\}(?:\[.*?\])?([\s\S]*?)\\end\{proof\}/);
        const solucion = solucionMatch ? solucionMatch[1].trim() : '';

        ejercicios.push({
            temas: temas,
            dificultad: dificultad,
            fuente: fuente,
            curso: curso,
            titulo: titulo,
            comentarios: comentarios,
            enunciado: enunciado,
            pistas: pistas,
            solucion: solucion
        });

        // Actualizar lastEjerEnd al final de \end{proof} (o \end{pistas} si no hay proof)
        // para que el siguiente ejercicio empiece después del ejercicio completo
        if (solucionMatch) {
            const proofEndIndex = finBloque.indexOf('\\end{proof}', solucionMatch.index);
            if (proofEndIndex !== -1) {
                lastEjerEnd = match.index + match[0].length + proofEndIndex + 11; // 11 = length of '\end{proof}'
            }
        } else if (pistasMatch) {
            const pistasEndIndex = finBloque.indexOf('\\end{pistas}', pistasMatch.index);
            if (pistasEndIndex !== -1) {
                lastEjerEnd = match.index + match[0].length + pistasEndIndex + 12; // 12 = length of '\end{pistas}'
            }
        } else {
            lastEjerEnd = match.index + match[0].length;
        }
    }

    return ejercicios;
}

// Extraer valor de un comando LaTeX (maneja llaves anidadas)
function extraerComando(texto, comando) {
    const regex = new RegExp(`\\\\${comando}\\{`, 'i');
    const match = texto.match(regex);

    if (!match) return '';

    const startIndex = match.index + match[0].length;
    let braceCount = 1;
    let endIndex = startIndex;

    // Contar llaves para encontrar la llave de cierre correspondiente
    while (endIndex < texto.length && braceCount > 0) {
        if (texto[endIndex] === '{') {
            braceCount++;
        } else if (texto[endIndex] === '}') {
            braceCount--;
        }
        endIndex++;
    }

    if (braceCount === 0) {
        return texto.substring(startIndex, endIndex - 1).trim();
    }

    return '';
}

// Rellenar formulario con datos de un ejercicio
function rellenarFormulario(ejercicio) {
    // Dificultad
    if (ejercicio.dificultad) {
        const dif = parseInt(ejercicio.dificultad);
        if (dif >= 1 && dif <= 10) {
            document.getElementById('difficulty').value = dif;
        }
    }
    
    // Curso (convertir texto a índice)
    if (ejercicio.curso) {
        const schoolYearIndex = convertirCursoAIndice(ejercicio.curso);
        if (schoolYearIndex) {
            document.getElementById('school_year').value = schoolYearIndex;
        }
    }
    
    // Fuente
    if (ejercicio.fuente) {
        document.getElementById('source').value = ejercicio.fuente;
    }

    // Título
    if (ejercicio.titulo) {
        document.getElementById('title').value = ejercicio.titulo;
    }

    // Enunciado
    if (ejercicio.enunciado) {
        document.getElementById('problem_tex').value = ejercicio.enunciado;
    }
    
    // Pistas
    if (ejercicio.pistas) {
        document.getElementById('hints').value = ejercicio.pistas;
    }
    
    // Solución
    if (ejercicio.solucion) {
        document.getElementById('solution_tex').value = ejercicio.solucion;
    }
    
    // Comentarios
    if (ejercicio.comentarios) {
        document.getElementById('comments').value = ejercicio.comentarios;
    }
    
    // Tags (temas)
    if (ejercicio.temas) {
        const tagsArray = ejercicio.temas.split(',').map(t => t.trim()).filter(t => t);
        cargarTagsEnFormulario(tagsArray);
    }
}

// Convertir nombre de curso a índice (case-insensitive)
function convertirCursoAIndice(curso) {
    // Normalizar: quitar espacios extra, convertir a mayúsculas
    const cursoNorm = curso.trim().toUpperCase().replace(/\s+/g, ' ');

    // Mapa de cursos normalizados (todo en mayúsculas)
    const cursos = {
        '1 PRIMARIA': 1, '1º PRIMARIA': 1, '1PRIMARIA': 1, '1ºPRIMARIA': 1,
        '2 PRIMARIA': 2, '2º PRIMARIA': 2, '2PRIMARIA': 2, '2ºPRIMARIA': 2,
        '3 PRIMARIA': 3, '3º PRIMARIA': 3, '3PRIMARIA': 3, '3ºPRIMARIA': 3,
        '4 PRIMARIA': 4, '4º PRIMARIA': 4, '4PRIMARIA': 4, '4ºPRIMARIA': 4,
        '5 PRIMARIA': 5, '5º PRIMARIA': 5, '5PRIMARIA': 5, '5ºPRIMARIA': 5,
        '6 PRIMARIA': 6, '6º PRIMARIA': 6, '6PRIMARIA': 6, '6ºPRIMARIA': 6,
        '1 ESO': 7, '1º ESO': 7, '1ESO': 7, '1ºESO': 7,
        '2 ESO': 8, '2º ESO': 8, '2ESO': 8, '2ºESO': 8,
        '3 ESO': 9, '3º ESO': 9, '3ESO': 9, '3ºESO': 9,
        '4 ESO': 10, '4º ESO': 10, '4ESO': 10, '4ºESO': 10,
        '1 BACHILLERATO': 11, '1º BACHILLERATO': 11, '1 BACH': 11, '1º BACH': 11, '1BACHILLERATO': 11, '1ºBACHILLERATO': 11, '1BACH': 11, '1ºBACH': 11,
        '2 BACHILLERATO': 12, '2º BACHILLERATO': 12, '2 BACH': 12, '2º BACH': 12, '2BACHILLERATO': 12, '2ºBACHILLERATO': 12, '2BACH': 12, '2ºBACH': 12
    };

    return cursos[cursoNorm] || null;
}

// Cargar tags en el formulario
function cargarTagsEnFormulario(tags) {
    const container = document.getElementById('tags-container');
    container.innerHTML = '';
    
    if (tags.length === 0) {
        const firstRow = document.createElement('div');
        firstRow.className = 'tag-input-row';
        firstRow.style.position = 'relative';
        firstRow.innerHTML = `
            <input type="text" name="tags[]" class="tag-input" placeholder="Escribe un tag..." autocomplete="off">
            <div class="tag-suggestions"></div>
            <button type="button" class="btn-add-tag" onclick="addTagInput()">+</button>
        `;
        container.appendChild(firstRow);
        attachTagAutocomplete(firstRow.querySelector('.tag-input'));
        return;
    }
    
    tags.forEach((tag, index) => {
        const newRow = document.createElement('div');
        newRow.className = 'tag-input-row';
        newRow.style.position = 'relative';
        
        if (index === 0) {
            newRow.innerHTML = `
                <input type="text" name="tags[]" class="tag-input" value="${tag}" placeholder="Escribe un tag..." autocomplete="off">
                <div class="tag-suggestions"></div>
                <button type="button" class="btn-add-tag" onclick="addTagInput()">+</button>
            `;
        } else {
            newRow.innerHTML = `
                <input type="text" name="tags[]" class="tag-input" value="${tag}" placeholder="Escribe un tag..." autocomplete="off">
                <div class="tag-suggestions"></div>
                <button type="button" class="btn-remove-tag" onclick="this.parentElement.remove()">−</button>
            `;
        }
        
        container.appendChild(newRow);
        attachTagAutocomplete(newRow.querySelector('.tag-input'));
    });
}

// Variable global para almacenar imágenes subidas para importación masiva
let imagenesParaImportar = {};

// Extraer nombres de archivos de imagen de \includegraphics
function extraerNombresImagenes(contenido) {
    const imagenes = new Set();

    // Buscar \includegraphics[...]{nombre} o \includegraphics{nombre}
    const regex = /\\includegraphics(?:\[[^\]]*\])?\{([^}]+)\}/g;
    let match;

    while ((match = regex.exec(contenido)) !== null) {
        let nombreArchivo = match[1].trim();
        // Quitar ruta si existe (solo queremos el nombre del archivo)
        nombreArchivo = nombreArchivo.split('/').pop().split('\\').pop();
        // Añadir extensión .png si no tiene extensión
        if (!nombreArchivo.includes('.')) {
            nombreArchivo += '.png';
        }
        imagenes.add(nombreArchivo);
    }

    return Array.from(imagenes);
}

// Mostrar modal para subir imágenes requeridas
function mostrarModalImagenes(imagenesRequeridas, callback) {
    // Crear modal
    const modal = document.createElement('div');
    modal.id = 'modal-imagenes';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';

    const contenido = document.createElement('div');
    contenido.style.cssText = 'background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;';

    contenido.innerHTML = `
        <h2 style="margin-top: 0; color: #2d3748;">📷 Imágenes Requeridas</h2>
        <p style="color: #718096;">Se encontraron referencias a las siguientes imágenes en el archivo .tex. Por favor, súbelas antes de continuar:</p>

        <div id="lista-imagenes-requeridas" style="margin: 1rem 0;">
            ${imagenesRequeridas.map(img => `
                <div class="imagen-requerida" data-nombre="${img}" style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; margin-bottom: 0.5rem; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <span class="estado-imagen" style="font-size: 1.2rem;">⏳</span>
                    <span style="flex: 1; font-weight: 500;">${img}</span>
                    <input type="file" accept="image/*,.pdf" style="display: none;" onchange="procesarImagenSubida(this, '${img}')">
                    <button type="button" onclick="this.previousElementSibling.click()" style="background: #4299e1; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Seleccionar</button>
                </div>
            `).join('')}
        </div>

        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <p style="color: #718096; font-size: 0.9rem; margin-bottom: 1rem;">
                <strong>Nota:</strong> Las imágenes se asociarán a cada problema que las referencie.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" id="btn-cancelar-imagenes" style="background: #718096; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer;">Cancelar</button>
                <button type="button" id="btn-continuar-importacion" style="background: #48bb78; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer;" disabled>Continuar Importación</button>
            </div>
        </div>
    `;

    modal.appendChild(contenido);
    document.body.appendChild(modal);

    // Eventos de los botones
    document.getElementById('btn-cancelar-imagenes').onclick = () => {
        document.body.removeChild(modal);
        imagenesParaImportar = {};
    };

    document.getElementById('btn-continuar-importacion').onclick = () => {
        document.body.removeChild(modal);
        callback();
    };

    // Verificar si no hay imágenes requeridas
    if (imagenesRequeridas.length === 0) {
        document.getElementById('btn-continuar-importacion').disabled = false;
    }
}

// Procesar imagen subida
function procesarImagenSubida(input, nombreEsperado) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        // Guardar la imagen con el nombre esperado
        imagenesParaImportar[nombreEsperado] = {
            file: file,
            data: e.target.result,
            originalName: file.name
        };

        // Actualizar UI
        const item = input.closest('.imagen-requerida');
        const estado = item.querySelector('.estado-imagen');
        estado.textContent = '✅';
        estado.style.color = '#48bb78';

        // Verificar si todas las imágenes están subidas
        verificarImagenesCompletas();
    };
    reader.readAsArrayBuffer(file);
}

// Verificar si todas las imágenes requeridas están subidas
function verificarImagenesCompletas() {
    const items = document.querySelectorAll('.imagen-requerida');
    let todasSubidas = true;

    items.forEach(item => {
        const nombre = item.dataset.nombre;
        if (!imagenesParaImportar[nombre]) {
            todasSubidas = false;
        }
    });

    const btnContinuar = document.getElementById('btn-continuar-importacion');
    if (btnContinuar) {
        btnContinuar.disabled = !todasSubidas;
    }
}

// Importar múltiples ejercicios automáticamente (con soporte de imágenes)

async function importarMultiplesEjercicios(ejercicios) {
    let exitosos = 0;
    let fallidos = 0;
    const errores = [];
    
    // Mostrar progreso
    const progreso = document.createElement('div');
    progreso.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 10000;';
    progreso.innerHTML = '<p>Importando ejercicios... <span id="progreso-count">0</span>/' + ejercicios.length + '</p>';
    document.body.appendChild(progreso);
    
    for (let i = 0; i < ejercicios.length; i++) {
        try {
            const ejercicio = ejercicios[i];
            
            // Actualizar contador
            document.getElementById('progreso-count').textContent = i + 1;
            
            // Validar campos obligatorios
            if (!ejercicio.enunciado || !ejercicio.enunciado.trim()) {
                errores.push(`Ejercicio ${i + 1}: sin enunciado`);
                fallidos++;
                continue;
            }
            if (!ejercicio.solucion || !ejercicio.solucion.trim()) {
                errores.push(`Ejercicio ${i + 1}: sin solución`);
                fallidos++;
                continue;
            }
            if (!ejercicio.temas || !ejercicio.temas.trim()) {
                errores.push(`Ejercicio ${i + 1}: sin temas`);
                fallidos++;
                continue;
            }
            if (!ejercicio.dificultad || !ejercicio.dificultad.trim()) {
                errores.push(`Ejercicio ${i + 1}: sin dificultad`);
                fallidos++;
                continue;
            }
            if (!ejercicio.curso || !ejercicio.curso.trim()) {
                errores.push(`Ejercicio ${i + 1}: sin curso`);
                fallidos++;
                continue;
            }
            
            // Convertir curso a índice numérico
            let schoolYearIndex = '';
            if (ejercicio.curso) {
                schoolYearIndex = convertirCursoAIndice(ejercicio.curso);
                if (!schoolYearIndex) {
                    errores.push(`Ejercicio ${i + 1}: curso "${ejercicio.curso}" no reconocido`);
                }
            }
            
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            
            if (ejercicio.dificultad) {
                formData.append('difficulty', ejercicio.dificultad);
            }
            
            if (schoolYearIndex) {
                formData.append('school_year', schoolYearIndex);
            }
            
            formData.append('title', ejercicio.titulo ? ejercicio.titulo.trim() : '');
            formData.append('problem_tex', ejercicio.enunciado.trim());
            formData.append('hints', ejercicio.pistas ? ejercicio.pistas.trim() : '');
            formData.append('solution_tex', ejercicio.solucion ? ejercicio.solucion.trim() : '');
            formData.append('comments', ejercicio.comentarios ? ejercicio.comentarios.trim() : '');
            formData.append('source', ejercicio.fuente ? ejercicio.fuente.trim() : '');
            
            // Tags
            if (ejercicio.temas) {
                const tags = ejercicio.temas.split(',').map(t => t.trim()).filter(t => t);
                tags.forEach(tag => {
                    formData.append('tags[]', tag);
                });
            }

            // Buscar imágenes referenciadas en este ejercicio
            const contenidoEjercicio = (ejercicio.enunciado || '') + (ejercicio.solucion || '') + (ejercicio.pistas || '');
            const imagenesEjercicio = extraerNombresImagenes(contenidoEjercicio);

            // Añadir imágenes al formData
            imagenesEjercicio.forEach(nombreImg => {
                if (imagenesParaImportar[nombreImg]) {
                    const imgData = imagenesParaImportar[nombreImg];
                    const blob = new Blob([imgData.data], { type: imgData.file.type });
                    formData.append('imagenes[]', blob, nombreImg);
                }
            });

            console.log(`Enviando ejercicio ${i + 1}:`, {
                titulo: ejercicio.titulo || '(vacío)',
                difficulty: ejercicio.dificultad,
                school_year: schoolYearIndex,
                fuente: ejercicio.fuente || '(vacío)',
                pistas: ejercicio.pistas ? ejercicio.pistas.substring(0, 30) + '...' : '(vacío)',
                problem_tex: ejercicio.enunciado.substring(0, 50) + '...',
                solution_tex: ejercicio.solucion ? ejercicio.solucion.substring(0, 50) + '...' : 'sin solución'
            });
            
            const response = await fetch('{{ route("problemas.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.text();

            if (response.ok) {
                exitosos++;
                console.log(`Ejercicio ${i + 1} importado con éxito`);
            } else {
                fallidos++;
                console.error(`Error en ejercicio ${i + 1}:`, result);
                console.error(`Título del ejercicio ${i + 1}:`, ejercicio.titulo);
                console.error(`Longitud del título:`, ejercicio.titulo ? ejercicio.titulo.length : 0);
                errores.push(`Ejercicio ${i + 1}: ${result.substring(0, 200)}`);
            }
            
            // Pequeña pausa para no saturar el servidor
            await new Promise(resolve => setTimeout(resolve, 200));
            
        } catch (error) {
            fallidos++;
            console.error(`Excepción en ejercicio ${i + 1}:`, error);
            errores.push(`Ejercicio ${i + 1}: ${error.message}`);
        }
    }
    
    // Eliminar indicador de progreso
    document.body.removeChild(progreso);
    
    // Mostrar resumen
    let mensaje = `✅ Importación completada:\n${exitosos} ejercicios importados\n${fallidos} fallidos`;
    
    if (errores.length > 0 && errores.length <= 5) {
        mensaje += '\n\nErrores:\n' + errores.join('\n');
    } else if (errores.length > 5) {
        mensaje += '\n\n' + errores.length + ' errores (ver consola para detalles)';
        console.error('Errores detallados:', errores);
    }
    
    alert(mensaje);

    // Limpiar imágenes temporales
    imagenesParaImportar = {};

    // No redirigir automáticamente - dejar al usuario en la página de creación
    // para que pueda seguir importando o creando más problemas
}

// Normalizar prefijo de contenido (igual que en PHP)
function normalizePrefix(text, len = 100) {
    return (text || '').trim().replace(/\s+/g, ' ').substring(0, len);
}

// Validar y comprobar duplicados, luego mostrar preview interactivo
async function analizarYMostrarPreview(ejercicios, contenido) {
    // Paso 1: validación de estructura
    const analisis = ejercicios.map((ej, i) => {
        const issues = [];
        let estado = 'ok';

        if (!ej.enunciado || !ej.enunciado.trim()) { issues.push('Sin enunciado'); estado = 'error'; }
        if (!ej.solucion   || !ej.solucion.trim())  { issues.push('Sin solución');  estado = 'error'; }
        if (!ej.temas      || !ej.temas.trim())      { issues.push('Sin temas');     estado = 'error'; }
        if (!ej.dificultad || !ej.dificultad.trim()) { issues.push('Sin dificultad'); estado = 'error'; }
        if (!ej.curso      || !ej.curso.trim())      { issues.push('Sin curso');     estado = 'error'; }

        if (estado !== 'error') {
            if (!ej.fuente || !ej.fuente.trim())  { issues.push('Sin fuente (se cargará vacío)');  if (estado === 'ok') estado = 'warn'; }
            if (!ej.titulo || !ej.titulo.trim())  { issues.push('Sin título (se cargará vacío)');  if (estado === 'ok') estado = 'warn'; }
        }

        return { index: i, issues, estado, title_match: null, content_match: null };
    });

    // Paso 2: comprobar duplicados en la API
    const items = ejercicios.map(ej => ({
        title: ej.titulo || '',
        content_prefix: normalizePrefix(ej.enunciado || '')
    }));

    try {
        const response = await fetch('{{ route("api.check-duplicates.problemas") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ items })
        });
        const dupResults = await response.json();

        dupResults.forEach(r => {
            if (r.index >= analisis.length) return;
            const a = analisis[r.index];
            if (r.content_match) {
                a.issues.push(`Contenido duplicado → Problema #${r.content_match.id}`);
                a.estado = 'error';
                a.content_match = r.content_match;
            }
            if (r.title_match) {
                a.issues.push(`Título duplicado → Problema #${r.title_match.id}`);
                if (a.estado !== 'error') a.estado = 'warn';
                a.title_match = r.title_match;
            }
        });
    } catch (e) {
        console.warn('Error al verificar duplicados:', e);
    }

    mostrarPreviewProblemas(ejercicios, analisis, contenido);
}

// Mostrar overlay de preview con tabla interactiva
function mostrarPreviewProblemas(ejercicios, analisis, contenido) {
    const overlay = document.createElement('div');
    overlay.id = 'preview-problemas-overlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.55);z-index:10000;overflow-y:auto;padding:2rem 0;box-sizing:border-box;';

    const cargables = analisis.filter(a => a.estado !== 'error').length;

    const rows = analisis.map((a, i) => {
        const ej = ejercicios[i];
        const enunciado = (ej.enunciado || '').trim().substring(0, 80)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

        let rowBg = '';
        let icon = '✅';
        let checked = 'checked';
        let disabled = '';

        if (a.estado === 'error') {
            rowBg = 'background:#fff5f5;border-left:3px solid #e53e3e;';
            icon = '❌'; checked = ''; disabled = 'disabled';
        } else if (a.estado === 'warn') {
            rowBg = 'background:#fffbeb;border-left:3px solid #d69e2e;';
            icon = '⚠️';
        }

        const issuesHtml = a.issues.length
            ? `<ul style="margin:0;padding-left:1.2rem;font-size:0.8rem;color:#718096;">${a.issues.map(s => `<li>${s}</li>`).join('')}</ul>`
            : '<span style="color:#68d391;font-size:0.85rem;">OK</span>';

        return `<tr style="${rowBg}">
            <td style="padding:0.4rem 0.5rem;text-align:center;">
                <input type="checkbox" class="preview-checkbox" data-index="${i}" ${checked} ${disabled}>
            </td>
            <td style="padding:0.4rem 0.5rem;text-align:center;font-size:1.1rem;">${icon}</td>
            <td style="padding:0.4rem 0.5rem;text-align:center;font-weight:600;">${i + 1}</td>
            <td style="padding:0.4rem 0.5rem;font-family:monospace;font-size:0.82rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${enunciado}">${enunciado || '(sin enunciado)'}…</td>
            <td style="padding:0.4rem 0.5rem;">${issuesHtml}</td>
        </tr>`;
    }).join('');

    overlay.innerHTML = `
        <div style="background:white;padding:2rem;border-radius:8px;width:90%;max-width:920px;margin:auto;">
            <h2 style="margin-top:0;color:#2d3748;">Se encontraron ${ejercicios.length} problemas — revisa antes de cargar</h2>
            <p style="color:#718096;margin-bottom:1rem;">❌ = no cargable (falta campo obligatorio o contenido duplicado) &nbsp;|&nbsp; ⚠️ = aviso (cargable) &nbsp;|&nbsp; ✅ = correcto.</p>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;margin-bottom:1.5rem;">
                    <thead>
                        <tr style="background:#f7fafc;border-bottom:2px solid #e2e8f0;">
                            <th style="padding:0.5rem;width:36px;"></th>
                            <th style="padding:0.5rem;width:36px;"></th>
                            <th style="padding:0.5rem;width:32px;">#</th>
                            <th style="padding:0.5rem;text-align:left;">Enunciado</th>
                            <th style="padding:0.5rem;text-align:left;">Estado / Detalles</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            <div style="display:flex;gap:1rem;justify-content:flex-end;align-items:center;flex-wrap:wrap;">
                <span style="color:#718096;margin-right:auto;"><span id="preview-selected-count">${cargables}</span> seleccionados de ${ejercicios.length}</span>
                <button type="button" id="btn-cancelar-preview" style="background:#718096;color:white;border:none;padding:0.75rem 1.5rem;border-radius:4px;cursor:pointer;">Cancelar</button>
                <button type="button" id="btn-cargar-preview" style="background:#48bb78;color:white;border:none;padding:0.75rem 1.5rem;border-radius:4px;cursor:pointer;">
                    Cargar seleccionados (<span id="btn-preview-count">${cargables}</span>)
                </button>
            </div>
        </div>`;

    document.body.appendChild(overlay);

    overlay.querySelectorAll('.preview-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            const n = overlay.querySelectorAll('.preview-checkbox:checked').length;
            document.getElementById('preview-selected-count').textContent = n;
            document.getElementById('btn-preview-count').textContent = n;
        });
    });

    document.getElementById('btn-cancelar-preview').onclick = () => document.body.removeChild(overlay);

    document.getElementById('btn-cargar-preview').onclick = () => {
        const selectedIndices = Array.from(overlay.querySelectorAll('.preview-checkbox:checked'))
            .map(cb => parseInt(cb.dataset.index));

        if (selectedIndices.length === 0) {
            alert('No hay problemas seleccionados.');
            return;
        }

        const seleccionados = selectedIndices.map(i => ejercicios[i]);
        document.body.removeChild(overlay);
        prepararYCargarSeleccionados(seleccionados, contenido);
    };
}

// Manejar imágenes y lanzar importación de los ejercicios seleccionados
function prepararYCargarSeleccionados(ejercicios, contenido) {
    const imagenesRequeridas = extraerNombresImagenes(contenido);

    if (imagenesRequeridas.length > 0) {
        mostrarModalImagenes(imagenesRequeridas, () => {
            importarMultiplesEjercicios(ejercicios);
        });
    } else {
        importarMultiplesEjercicios(ejercicios);
    }
}

// Función para calcular distancia Levenshtein
function levenshteinDistance(str1, str2) {
    const m = str1.length;
    const n = str2.length;
    const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));

    for (let i = 0; i <= m; i++) dp[i][0] = i;
    for (let j = 0; j <= n; j++) dp[0][j] = j;

    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            if (str1[i - 1].toLowerCase() === str2[j - 1].toLowerCase()) {
                dp[i][j] = dp[i - 1][j - 1];
            } else {
                dp[i][j] = 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
            }
        }
    }
    return dp[m][n];
}

// Cache de todos los tags para comparación Levenshtein
let allTagsCache = [];

// Cargar todos los tags una vez al inicio
function loadAllTags() {
    fetch('/api/topics/buscar?q=')
        .then(response => response.json())
        .then(data => {
            allTagsCache = data;
            console.log('Tags cargados para Levenshtein:', allTagsCache.length);
        })
        .catch(error => console.error('Error cargando tags:', error));
}

// Buscar tag similar usando Levenshtein
function findSimilarTag(inputTag) {
    if (!inputTag || allTagsCache.length === 0) return null;

    const inputLower = inputTag.toLowerCase().trim();

    for (const existingTag of allTagsCache) {
        const existingLower = existingTag.toLowerCase();

        // Si es exactamente igual, no hay cambio
        if (inputLower === existingLower) return null;

        // Calcular distancia
        const distance = levenshteinDistance(inputTag, existingTag);

        // Si la distancia es pequeña (1-2), sugerir el tag existente
        if (distance > 0 && distance <= 2) {
            return existingTag;
        }
    }
    return null;
}

// Función para adjuntar autocompletado a un input de tag
function attachTagAutocomplete(input) {
    if (!input) return;

    const suggestionsDiv = input.parentElement.querySelector('.tag-suggestions');
    if (!suggestionsDiv) return;

    let debounceTimer;

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const term = this.value.trim();

        if (term.length < 2) {
            suggestionsDiv.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/api/topics/buscar?q=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        suggestionsDiv.innerHTML = data.map(topic =>
                            `<div class="tag-suggestion-item" data-title="${topic}">${topic}</div>`
                        ).join('');
                        suggestionsDiv.style.display = 'block';
                    } else {
                        suggestionsDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error buscando tags:', error);
                    suggestionsDiv.style.display = 'none';
                });
        }, 300);
    });

    // Al hacer clic en una sugerencia
    suggestionsDiv.addEventListener('click', function(e) {
        if (e.target.classList.contains('tag-suggestion-item')) {
            input.value = e.target.dataset.title;
            suggestionsDiv.style.display = 'none';
        }
    });

    // Al perder el foco, verificar si hay un tag similar
    input.addEventListener('blur', function() {
        setTimeout(() => {
            suggestionsDiv.style.display = 'none';

            const inputValue = this.value.trim();
            if (!inputValue) return;

            const similarTag = findSimilarTag(inputValue);
            if (similarTag && similarTag !== inputValue) {
                alert(`Tag cambiado a "${similarTag}" (similar a "${inputValue}")`);
                this.value = similarTag;
            }
        }, 200);
    });

    // Cerrar sugerencias al presionar Escape
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            suggestionsDiv.style.display = 'none';
        }
        // Al presionar Enter, seleccionar la primera sugerencia si hay
        if (e.key === 'Enter') {
            const firstSuggestion = suggestionsDiv.querySelector('.tag-suggestion-item');
            if (firstSuggestion && suggestionsDiv.style.display === 'block') {
                e.preventDefault();
                input.value = firstSuggestion.dataset.title;
                suggestionsDiv.style.display = 'none';
            }
        }
    });
}

// Función para añadir nuevo input de tag
function addTagInput() {
    const container = document.getElementById('tags-container');
    const newRow = document.createElement('div');
    newRow.className = 'tag-input-row';
    newRow.style.position = 'relative';
    newRow.innerHTML = `
        <input type="text" name="tags[]" class="tag-input" placeholder="Escribe un tag..." autocomplete="off">
        <div class="tag-suggestions"></div>
        <button type="button" class="btn-remove-tag" onclick="this.parentElement.remove()">−</button>
    `;
    container.appendChild(newRow);
    attachTagAutocomplete(newRow.querySelector('.tag-input'));
    newRow.querySelector('.tag-input').focus();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Iniciando vista previa');

    // Cargar todos los tags para comparación Levenshtein
    loadAllTags();

    // Adjuntar autocompletado a todos los inputs de tag existentes
    document.querySelectorAll('.tag-input').forEach(input => {
        attachTagAutocomplete(input);
    });

    const problemInput = document.getElementById('problem_tex');
    const solutionInput = document.getElementById('solution_tex');

    if (problemInput) {
        problemInput.addEventListener('input', () => updatePreview('problem_tex', 'problem_preview'));

        // Cargar vista previa inicial si hay contenido (inmediatamente, sin esperar)
        if (problemInput.value.trim()) {
            console.log('Cargando vista previa inicial del enunciado');
            // Ejecutar inmediatamente sin el debounce de 500ms
            renderPreviewImmediate('problem_tex', 'problem_preview');
        }
    }

    if (solutionInput) {
        solutionInput.addEventListener('input', () => updatePreview('solution_tex', 'solution_preview'));

        // Cargar vista previa inicial si hay contenido (inmediatamente, sin esperar)
        if (solutionInput.value.trim()) {
            console.log('Cargando vista previa inicial de la solución');
            // Ejecutar inmediatamente sin el debounce de 500ms
            renderPreviewImmediate('solution_tex', 'solution_preview');
        }
    }
});

// Función para renderizar vista previa inmediatamente (sin debounce)
function renderPreviewImmediate(inputId, previewId) {
    const texContent = document.getElementById(inputId).value;
    const previewDiv = document.getElementById(previewId);

    if (!texContent.trim()) {
        previewDiv.innerHTML = '<p style="color: #a0aec0; font-style: italic;">La vista previa aparecerá aquí...</p>';
        return;
    }

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
            // Renderizar MathJax en el nuevo contenido
            if (window.MathJax) {
                MathJax.typesetPromise([previewDiv]).catch(err => console.error('MathJax error:', err));
            }
        } else if (data.error) {
            previewDiv.innerHTML = '<p style="color: #e53e3e;">❌ Error: ' + data.error + '</p>';
        }
    })
    .catch(error => {
        console.error('Error al procesar LaTeX:', error);
        previewDiv.innerHTML = '<p style="color: #e53e3e;">❌ Error al procesar la vista previa</p>';
    });
}
</script>