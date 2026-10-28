@extends('layouts.main')

@section('title', 'El proyecto - PIM')

@section('styles')
.proyecto-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 2rem 2.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    color: #2d3748;
    line-height: 1.75;
}
.proyecto-container h1 {
    margin-top: 0;
}
.proyecto-container h1 {
    font-size: 2rem;
    text-align: center;
    margin-bottom: 0.5rem;
}
.proyecto-lead {
    text-align: center;
    color: #4a5568;
    font-size: 1.1rem;
    margin-bottom: 2.5rem;
}
.proyecto-container h2 {
    font-size: 1.35rem;
    margin: 2.5rem 0 1rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid #e2e8f0;
}
.proyecto-container a {
    color: #4299e1;
    text-decoration: none;
}
.proyecto-container a:hover {
    text-decoration: underline;
}
.bloques {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}
.bloque {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
}
.bloque h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    color: #2b6cb0;
}
.bloque p {
    margin: 0;
    color: #4a5568;
    font-size: 0.95rem;
}
.acceso-tabla {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    border-collapse: separate;
    border-spacing: 0;
    overflow: hidden;
    font-size: 0.95rem;
}
.acceso-tabla th,
.acceso-tabla td {
    padding: 0.85rem 1rem;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
}
.acceso-tabla th {
    background: #f7fafc;
    color: #4a5568;
    width: 30%;
}
.acceso-tabla tr:last-child th,
.acceso-tabla tr:last-child td {
    border-bottom: none;
}
.cta {
    text-align: center;
    margin: 2.5rem 0 1rem;
}
.cta a.btn {
    display: inline-block;
    background: #4299e1;
    color: white;
    padding: 0.6rem 1.4rem;
    border-radius: 6px;
    font-weight: 600;
    margin: 0.25rem 0.4rem;
    transition: background 0.2s;
}
.cta a.btn:hover {
    background: #3182ce;
    text-decoration: none;
}
.cta a.btn.secundario {
    background: white;
    color: #2b6cb0;
    border: 1px solid #90cdf4;
}
.cta a.btn.secundario:hover {
    background: #ebf8ff;
}
@media (max-width: 760px) {
    .proyecto-container { margin: 1rem; padding: 1.5rem 1.25rem; }
    .bloques { grid-template-columns: 1fr; }
    .acceso-tabla th { width: 40%; }
}
@endsection

@section('content')
<div class="proyecto-container">
    <h1>El proyecto</h1>
    <p class="proyecto-lead">
        Una biblioteca de problemas de matemáticas no curriculares, nacida del trabajo del
        <a href="https://www.icmat.es/PIM/" target="_blank" rel="noopener">Pequeño Instituto de Matemáticas</a> (ICMAT).
    </p>

    <p>
        Los problemas que hacen pensar no suelen aparecer en los libros de texto: problemas de olimpiada,
        de ingenio, de círculos matemáticos. Durante años se han ido acumulando en hojas sueltas, archivos LaTeX
        y carpetas personales. Esta plataforma los reúne en un solo sitio, los ordena y los pone al alcance
        de quien los quiera usar en clase, en un club de matemáticas o para preparar olimpiadas.
    </p>

    <h2>Qué contiene</h2>
    <div class="bloques">
        <div class="bloque">
            <h3>Problemas</h3>
            <p>
                Cada problema está clasificado por tema, tags, nivel de dificultad y curso recomendado,
                y muchos incluyen pistas y solución. Se pueden buscar y filtrar combinando estos criterios.
            </p>
        </div>
        <div class="bloque">
            <h3>Métodos</h3>
            <p>
                Explicaciones de técnicas e ideas de resolución (invariantes, principio del palomar,
                paridad…) que conectan problemas distintos que se resuelven con la misma idea.
            </p>
        </div>
        <div class="bloque">
            <h3>Hojas temáticas</h3>
            <p>
                Las hojas que se han trabajado en las sesiones del PIM, curso a curso: grupos de problemas
                seleccionados y ordenados alrededor de un tema.
            </p>
        </div>
    </div>

    <h2>Herramientas</h2>
    <p>
        Además de consultar, la plataforma sirve para trabajar con grupos de problemas:
    </p>
    <ul>
        <li>reunir problemas en un <strong>carrito</strong>, reordenarlos y guardarlos como hoja propia;</li>
        <li>generar a partir de ellos un <strong>PDF</strong> o el código <strong>LaTeX</strong> listo para editar;</li>
        <li>presentarlos en pantalla, con los retos y las soluciones en páginas separadas;</li>
        <li>proponer problemas nuevos y avisar de erratas en los existentes.</li>
    </ul>

    <h2>Acceso</h2>
    <table class="acceso-tabla">
        <tr>
            <th>Sin registro</th>
            <td>Información general y un problema aleatorio en la página principal.</td>
        </tr>
        <tr>
            <th>Con registro</th>
            <td>
                Al <a href="{{ route('register') }}">registrarte</a> se activan nuevos permisos: los problemas
                y las hojas temáticas usados en el PIM durante los últimos cursos, el carrito y la propuesta
                de problemas.
            </td>
        </tr>
        <tr>
            <th>Bajo solicitud</th>
            <td>
                Si eres docente o colaboras con actividades de matemáticas, puedes
                <a href="{{ route('contacto') }}">solicitar</a> permisos ampliados: ver los problemas y hojas
                de otros años, acceder a los métodos, descargar en LaTeX o crear tus propios métodos y hojas temáticas.
            </td>
        </tr>
    </table>

    <div class="cta">
        @guest
            <a href="{{ route('register') }}" class="btn">Registrarse</a>
        @endguest
        <a href="{{ route('contacto') }}" class="btn secundario">Solicitar permisos</a>
    </div>
</div>
@endsection
