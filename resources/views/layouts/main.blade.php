<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PIM')</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link rel="icon" type="image/png" href="{{ asset('images/logoPIM.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logoPIM.png') }}">
<!-- MathJax -->
<script>
window.MathJax = {
    tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']],
        processEscapes: true,
        processEnvironments: true,
        packages: {'[+]': ['color']}
    },
    options: {
        skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
        ignoreHtmlClass: 'tex2jax_ignore'
    },
    startup: {
        pageReady: () => {
            return MathJax.startup.defaultPageReady().then(() => {
                console.log('MathJax loaded and ready');
            });
        }
    }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

<!-- TikzJax para dibujos -->
<link rel="stylesheet" type="text/css" href="https://tikzjax.com/v1/fonts.css">
<script src="https://tikzjax.com/v1/tikzjax.js"></script>  
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #4a5568;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 1rem;
            font-weight: 600;
        }
        .navbar a:hover {
            color: #cbd5e0;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .logo a {
            color: white;
            text-decoration: none;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .carrito-link {
    font-size: 1.5rem;
    position: relative;
    text-decoration: none;
}
.carrito-badge {
    position: absolute;
    top: -8px;
    right: -10px;
    background-color: #e53e3e;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.75rem;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
}

.user-dropdown {
    position: relative;
    display: inline-block;
}

.user-dropdown-btn {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    font-size: inherit;
    padding: 0.5rem;
}

.user-dropdown-btn:hover {
    opacity: 0.8;
}

.user-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 180px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border-radius: 4px;
    z-index: 1000;
    overflow: hidden;
}

.user-dropdown-content a {
    color: #333 !important;
    padding: 0.75rem 1rem;
    display: block;
    text-decoration: none;
}

.user-dropdown-content a:hover {
    background-color: #f5f5f5;
}

.user-dropdown:hover .user-dropdown-content {
    display: block;
}

.nav-dropdown {
    position: relative;
    display: inline-block;
}

.nav-dropdown-btn {
    color: white;
    text-decoration: none;
    font-weight: 600;
    padding: 0.5rem 1rem;
    margin: 0;
    cursor: pointer;
    display: inline-block;
}

.nav-dropdown-btn:hover {
    color: #cbd5e0;
}

.nav-dropdown-content {
    display: none;
    position: absolute;
    left: 0;
    top: 100%;
    background-color: white;
    min-width: 200px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border-radius: 4px;
    z-index: 1000;
    overflow: hidden;
    margin-top: 0;
    padding-top: 0.25rem;
}

.nav-dropdown-content::before {
    content: '';
    position: absolute;
    top: -0.25rem;
    left: 0;
    right: 0;
    height: 0.25rem;
}

.nav-dropdown-content a {
    color: #333 !important;
    padding: 0.75rem 1rem;
    display: block;
    text-decoration: none;
}

.nav-dropdown-content a:hover {
    background-color: #f5f5f5;
}

.nav-dropdown:hover .nav-dropdown-content {
    display: block;
}

/* Navbar responsive */
@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        padding: 1rem;
        gap: 1rem;
    }
    
    .navbar > div:last-child {
        flex-direction: column;
        width: 100%;
        text-align: center;
    }
    
    .navbar a {
        display: block;
        margin: 0.25rem 0;
    }
}

/* === PAGINACIÓN === */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin: 2rem 0;
}

.pagination {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.page-item {
    display: inline-block;
    padding: 0.75rem 1rem;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    background-color: white;
    color: #4a5568;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 500;
    transition: all 0.2s;
    min-width: 45px;
    text-align: center;
}

.page-item:hover:not(.disabled):not(.active) {
    background-color: #f7fafc;
    border-color: #4a5568;
    transform: translateY(-2px);
}

.page-item.active {
    background-color: #4a5568;
    color: white;
    border-color: #4a5568;
    font-weight: 700;
}

.page-item.disabled {
    background-color: #f7fafc;
    color: #cbd5e0;
    cursor: not-allowed;
    border-color: #e2e8f0;
}

/* Botones de ordenamiento en paginación */
.sort-id-btn {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    background-color: white;
    color: #4a5568;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    cursor: pointer;
}

.sort-id-btn:hover {
    background-color: #f7fafc;
    border-color: #4a5568;
    transform: translateY(-2px);
}

.sort-id-btn.active {
    background-color: #4a5568;
    color: white;
    border-color: #4a5568;
}

/* Responsive */
@media (max-width: 768px) {
    .pagination {
        gap: 0.3rem;
        flex-wrap: wrap;
    }

    .page-item {
        padding: 0.5rem 0.7rem;
        font-size: 0.9rem;
        min-width: 35px;
    }
}

@media (max-width: 480px) {
    .pagination {
        gap: 0.25rem;
    }

    .page-item {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
        min-width: 30px;
    }
}

        @yield('styles')
    </style>
</head>
<body>
    <nav class="navbar">
    <div class="logo">
        <a href="{{ route('homepage') }}" style="display:flex; align-items:center; gap:0.5rem;">
            <img src="{{ asset('images/logoPIM.png') }}" alt="PIM" style="height:2rem; vertical-align:middle;">
            Pequeño Instituto de Matemáticas
        </a>
    </div>
    <div style="display: flex; align-items: center; gap: 1.5rem;">
        
        
        <div>
            {{-- Problemas: dropdown para cualquier usuario autenticado --}}
            @auth
                <div class="nav-dropdown">
                    <a href="{{ route('problemas.index') }}" class="nav-dropdown-btn">Problemas ▾</a>
                    <div class="nav-dropdown-content">
                        <a href="{{ route('problemas.index') }}">Ver problemas</a>
                        <a href="{{ route('problemas.create') }}">Añadir problema</a>
                    </div>
                </div>
            @else
                <a href="{{ route('problemas.index') }}">Problemas</a>
            @endauth

            {{-- Métodos: visible solo si no es usuario básico --}}
            @auth
                @if(Auth::user()->rol !== 'user')
                    @if(Auth::user()->canEditProblemas())
                        <div class="nav-dropdown">
                            <a href="{{ route('metodos.index') }}" class="nav-dropdown-btn">Métodos ▾</a>
                            <div class="nav-dropdown-content">
                                <a href="{{ route('metodos.index') }}">Ver métodos</a>
                                <a href="{{ route('metodos.create') }}">Añadir método</a>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('metodos.index') }}">Métodos</a>
                    @endif
                @endif
            @else
                <a href="{{ route('metodos.index') }}">Métodos</a>
            @endauth

            {{-- Hojas: link for all auth, dropdown for editors --}}
            @auth
                @if(Auth::user()->canEditProblemas())
                    <div class="nav-dropdown">
                        <a href="{{ route('pim-sheets.index') }}" class="nav-dropdown-btn">Hojas ▾</a>
                        <div class="nav-dropdown-content">
                            <a href="{{ route('pim-sheets.index') }}">Ver hojas</a>
                            <a href="{{ route('pim-sheets.create') }}">Subir una hoja</a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('pim-sheets.index') }}">Hojas</a>
                @endif

                @if(Auth::user()->canEditProblemas())
                    <a href="{{ route('tags.index') }}">Tags</a>
                @endif
            @endauth





            
            @auth
            <a href="{{ route('carrito.index') }}" class="carrito-link" style="position: relative;">
                🛒
                <span id="carrito-count" class="carrito-badge">0</span>
            </a>
            @endauth
            @guest
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Registro</a>
            @else
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        {{ Auth::user()->name }} ▾
                    </button>
                    <div class="user-dropdown-content">
                        @if(Auth::user()->rol === 'admin')
                            <a href="{{ route('admin.users.index') }}">Administrar usuarios</a>
                            @php $nPend = \App\Models\Problema::where('approved', 0)->count(); @endphp
                            <a href="{{ route('admin.problemas.pendientes') }}" style="display:flex; align-items:center; justify-content:space-between;">
                                <span>⏳ Problemas pendientes</span>
                                @if($nPend > 0)
                                    <span style="background:#e53e3e; color:white; border-radius:10px; padding:1px 7px; font-size:0.75rem; font-weight:700; margin-left:0.5rem;">{{ $nPend }}</span>
                                @endif
                            </a>
                            <a href="{{ route('admin.settings') }}">⚙️ Configuración</a>
                        @endif
                        @if(Auth::user()->canEditProblemas())
                            <a href="{{ route('problemas.con-errores') }}">⚠️ Problemas con errores</a>
                        @endif
                        <a href="{{ route('profile.edit') }}">Editar perfil</a>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Logout
                        </a>
                    </div>
                </div>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endguest

        </div>
    </div>
</nav>
  <main>
    @if(session('error'))
    <div style="background:#fed7d7;color:#742a2a;border:1px solid #fc8181;border-radius:6px;padding:0.85rem 1.25rem;margin:1rem auto;max-width:1200px;font-size:0.95rem;">
        ❌ {{ session('error') }}
    </div>
    @endif
    @if(session('success'))
    <div style="background:#c6f6d5;color:#276749;border:1px solid #68d391;border-radius:6px;padding:0.85rem 1.25rem;margin:1rem auto;max-width:1200px;font-size:0.95rem;">
        ✅ {{ session('success') }}
    </div>
    @endif
    @if(session('info'))
    <div style="background:#bee3f8;color:#2c5282;border:1px solid #90cdf4;border-radius:6px;padding:0.85rem 1.25rem;margin:1rem auto;max-width:1200px;font-size:0.95rem;">
        ℹ️ {{ session('info') }}
    </div>
    @endif
    @yield('content')
</main>

<script src="{{ asset('js/app.js') }}"></script>

<!-- Forzar renderizado de MathJax -->
<script>
if (typeof MathJax !== 'undefined') {
    MathJax.startup.promise.then(() => {
        MathJax.typesetPromise();
    });
}
</script>
<script>
// Actualizar contador del carrito al cargar la página
@auth
fetch('{{ route("carrito.count") }}')
    .then(response => response.json())
    .then(data => {
        document.getElementById('carrito-count').textContent = data.count;
    });
@endauth
</script>
@yield('scripts')
</body>
</html>