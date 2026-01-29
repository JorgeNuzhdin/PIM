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
            // Múltiples ejercicios: preguntar si importar todos
            if (confirm(`Se encontraron ${ejercicios.length} ejercicios. ¿Deseas importarlos todos automáticamente?`)) {
                importarMultiplesEjercicios(ejercicios);
            } else {
                rellenarFormulario(ejercicios[0]);
                alert(`Se cargó el primer ejercicio. Hay ${ejercicios.length - 1} más en el archivo.`);
            }
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

// Convertir nombre de curso a índice
function convertirCursoAIndice(curso) {
    const cursos = {
        '1 Primaria': 1, '1º Primaria': 1, '1Primaria': 1, '1ºPrimaria': 1,
        '2 Primaria': 2, '2º Primaria': 2, '2Primaria': 2, '2ºPrimaria': 2,
        '3 Primaria': 3, '3º Primaria': 3, '3Primaria': 3, '3ºPrimaria': 3,
        '4 Primaria': 4, '4º Primaria': 4, '4Primaria': 4, '4ºPrimaria': 4,
        '5 Primaria': 5, '5º Primaria': 5, '5Primaria': 5, '5ºPrimaria': 5,
        '6 Primaria': 6, '6º Primaria': 6, '6Primaria': 6, '6ºPrimaria': 6,
        '1 ESO': 7, '1º ESO': 7, '1ESO': 7, '1ºESO': 7,
        '2 ESO': 8, '2º ESO': 8, '2ESO': 8, '2ºESO': 8,
        '3 ESO': 9, '3º ESO': 9, '3ESO': 9, '3ºESO': 9,
        '4 ESO': 10, '4º ESO': 10, '4ESO': 10, '4ºESO': 10,
        '1 Bachillerato': 11, '1º Bachillerato': 11, '1 BACH': 11, '1º BACH': 11, '1Bachillerato': 11, '1ºBachillerato': 11, '1BACH': 11, '1ºBACH': 11,
        '2 Bachillerato': 12, '2º Bachillerato': 12, '2 BACH': 12, '2º BACH': 12, '2Bachillerato': 12, '2ºBachillerato': 12, '2BACH': 12, '2ºBACH': 12
    };

    return cursos[curso] || null;
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

// Importar múltiples ejercicios automáticamente

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
                errores.push(`Ejercicio ${i + 1}: ${result.substring(0, 100)}`);
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
    
    if (exitosos > 0) {
        window.location.href = '{{ route("problemas.index") }}';
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