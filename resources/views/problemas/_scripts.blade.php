<script>
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


// Adjuntar eventos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const problemInput = document.getElementById('problem_tex');
    const solutionInput = document.getElementById('solution_tex');
    
    if (problemInput) {
        problemInput.addEventListener('input', () => updatePreview('problem_tex', 'problem_preview'));
        // Cargar vista previa inicial
        updatePreview('problem_tex', 'problem_preview');
    }
    
    if (solutionInput) {
        solutionInput.addEventListener('input', () => updatePreview('solution_tex', 'solution_preview'));
        // Cargar vista previa inicial
        updatePreview('solution_tex', 'solution_preview');
    }
});


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

    // Extraer metadatos globales (fuera de ejercicios)
    const temas = extraerComando(documento, 'temas');
    const fuente = extraerComando(documento, 'fuente');

    // Buscar todos los bloques de ejercicios con \begin{ejer}...\end{ejer}
    const regexEjer = /\\begin\{ejer\}([\s\S]*?)\\end\{ejer\}/g;
    let match;

    while ((match = regexEjer.exec(documento)) !== null) {
        const bloqueCompleto = documento.substring(match.index);

        // Buscar metadatos antes del ejercicio
        const antesEjer = documento.substring(0, match.index);
        const ultimosDatos = antesEjer.substring(Math.max(0, antesEjer.length - 500));

        const dificultad = extraerComando(ultimosDatos, 'dificultad') || '';
        const curso = extraerComando(ultimosDatos, 'curso') || '';
        const comentarios = extraerComando(ultimosDatos, 'comentarios') || '';

        // Extraer enunciado
        const enunciado = match[1].trim();

        // Buscar pistas después del ejercicio: \begin{pistas}...\end{pistas} o \pistas{...}
        let pistas = '';
        const pistasEnvMatch = bloqueCompleto.match(/\\begin\{pistas\}([\s\S]*?)\\end\{pistas\}/);
        if (pistasEnvMatch) {
            pistas = pistasEnvMatch[1].trim();
        } else {
            const pistasCmdMatch = bloqueCompleto.match(/\\pistas\{([\s\S]*?)\}(?=\s*(?:\\begin|\\solution|\\end|$|\n))/);
            if (pistasCmdMatch) {
                pistas = pistasCmdMatch[1].trim();
            }
        }

        // Buscar solución: \begin{proof}...\end{proof} o \solution{...}
        let solucion = '';
        const solucionProofMatch = bloqueCompleto.match(/\\begin\{proof\}(?:\[.*?\])?([\s\S]*?)\\end\{proof\}/);
        if (solucionProofMatch) {
            solucion = solucionProofMatch[1].trim();
        } else {
            const solucionCmdMatch = bloqueCompleto.match(/\\solution\{([\s\S]*?)\}(?=\s*(?:\\|$|\n))/);
            if (solucionCmdMatch) {
                solucion = solucionCmdMatch[1].trim();
            }
        }

        ejercicios.push({
            temas: temas,
            dificultad: dificultad,
            fuente: fuente,
            curso: curso,
            comentarios: comentarios,
            enunciado: enunciado,
            pistas: pistas,
            solucion: solucion
        });
    }

    // Buscar también ejercicios con \exercise{...}
    const regexExercise = /\\exercise\{([\s\S]*?)\}(?=\s*(?:\\solution|\\pistas|\\begin|$|\n\n))/g;
    let exerciseMatch;

    while ((exerciseMatch = regexExercise.exec(documento)) !== null) {
        const posicionExercise = exerciseMatch.index;
        const bloqueCompleto = documento.substring(posicionExercise);

        // Buscar metadatos antes del ejercicio
        const antesExercise = documento.substring(0, posicionExercise);
        const ultimosDatos = antesExercise.substring(Math.max(0, antesExercise.length - 500));

        const dificultad = extraerComando(ultimosDatos, 'dificultad') || '';
        const curso = extraerComando(ultimosDatos, 'curso') || '';
        const comentarios = extraerComando(ultimosDatos, 'comentarios') || '';

        // Extraer enunciado
        const enunciado = exerciseMatch[1].trim();

        // Buscar pistas después del ejercicio: \begin{pistas}...\end{pistas} o \pistas{...}
        let pistas = '';
        const pistasEnvMatch = bloqueCompleto.match(/\\begin\{pistas\}([\s\S]*?)\\end\{pistas\}/);
        if (pistasEnvMatch) {
            pistas = pistasEnvMatch[1].trim();
        } else {
            const pistasCmdMatch = bloqueCompleto.match(/\\pistas\{([\s\S]*?)\}(?=\s*(?:\\solution|\\exercise|\\begin|$|\n\n))/);
            if (pistasCmdMatch) {
                pistas = pistasCmdMatch[1].trim();
            }
        }

        // Buscar solución: \solution{...} o \begin{proof}...\end{proof}
        let solucion = '';
        const solucionCmdMatch = bloqueCompleto.match(/\\solution\{([\s\S]*?)\}(?=\s*(?:\\exercise|\\begin|$|\n\n))/);
        if (solucionCmdMatch) {
            solucion = solucionCmdMatch[1].trim();
        } else {
            const solucionProofMatch = bloqueCompleto.match(/\\begin\{proof\}(?:\[.*?\])?([\s\S]*?)\\end\{proof\}/);
            if (solucionProofMatch) {
                solucion = solucionProofMatch[1].trim();
            }
        }

        ejercicios.push({
            temas: temas,
            dificultad: dificultad,
            fuente: fuente,
            curso: curso,
            comentarios: comentarios,
            enunciado: enunciado,
            pistas: pistas,
            solucion: solucion
        });
    }

    return ejercicios;
}

// Extraer valor de un comando LaTeX
function extraerComando(texto, comando) {
    const regex = new RegExp(`\\\\${comando}\\{([^}]*?)\\}`, 'i');
    const match = texto.match(regex);
    return match ? match[1].trim() : '';
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
        '1 Primaria': 1, '1º Primaria': 1,
        '2 Primaria': 2, '2º Primaria': 2,
        '3 Primaria': 3, '3º Primaria': 3,
        '4 Primaria': 4, '4º Primaria': 4,
        '5 Primaria': 5, '5º Primaria': 5,
        '6 Primaria': 6, '6º Primaria': 6,
        '1 ESO': 7, '1º ESO': 7,
        '2 ESO': 8, '2º ESO': 8,
        '3 ESO': 9, '3º ESO': 9,
        '4 ESO': 10, '4º ESO': 10,
        '1 Bachillerato': 11, '1º Bachillerato': 11, '1 BACH': 11, '1º BACH': 11,
        '2 Bachillerato': 12, '2º Bachillerato': 12, '2 BACH': 12, '2º BACH': 12
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
            
            formData.append('title', ''); // Título vacío por defecto
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
                difficulty: ejercicio.dificultad,
                school_year: schoolYearIndex,
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

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Iniciando vista previa');
    
    const problemInput = document.getElementById('problem_tex');
    const solutionInput = document.getElementById('solution_tex');
    
    if (problemInput) {
        problemInput.addEventListener('input', () => updatePreview('problem_tex', 'problem_preview'));
        
        // Cargar vista previa inicial si hay contenido
        if (problemInput.value.trim()) {
            updatePreview('problem_tex', 'problem_preview');
        }
    }
    
    if (solutionInput) {
        solutionInput.addEventListener('input', () => updatePreview('solution_tex', 'solution_preview'));
        
        // Cargar vista previa inicial si hay contenido
        if (solutionInput.value.trim()) {
            updatePreview('solution_tex', 'solution_preview');
        }
    }
});
</script>