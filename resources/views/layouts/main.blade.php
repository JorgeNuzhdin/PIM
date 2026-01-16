<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PIM')</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
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
        skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
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

.sheets-dropdown {
    position: relative;
    display: inline-block;
}

.sheets-dropdown-btn {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    font-size: inherit;
    font-weight: 600;
    padding: 0.5rem 1rem;
    margin: 0;
}

.sheets-dropdown-btn:hover {
    color: #cbd5e0;
}

.sheets-dropdown-content {
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

.sheets-dropdown-content::before {
    content: '';
    position: absolute;
    top: -0.25rem;
    left: 0;
    right: 0;
    height: 0.25rem;
}

.sheets-dropdown-content a {
    color: #333 !important;
    padding: 0.75rem 1rem;
    display: block;
    text-decoration: none;
}

.sheets-dropdown-content a:hover {
    background-color: #f5f5f5;
}

.sheets-dropdown:hover .sheets-dropdown-content {
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
        @yield('styles')
    </style>
</head>
<body>
    <nav class="navbar">
    <div class="logo">
        <a href="{{ route('homepage') }}">📐 Pequeño Instituto de Matemáticas</a>
    </div>
    <div style="display: flex; align-items: center; gap: 1.5rem;">
        
        
        <div>
            <a href="{{ route('problemas.index') }}">Ver Problemas</a>
            @auth
                @if(Auth::user()->canEditProblemas())
                    <a href="{{ route('problemas.create') }}">Añadir Problema</a>
                @endif

                <div class="sheets-dropdown">
                    <button class="sheets-dropdown-btn">
                        Hojas de problemas ▾
                    </button>
                    <div class="sheets-dropdown-content">
                        <a href="{{ route('pim-sheets.index') }}">Ver hojas</a>
                        @if(Auth::user()->canEditProblemas())
                            <a href="{{ route('pim-sheets.create') }}">Subir una hoja</a>
                        @endif
                    </div>
                </div>
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
                        @endif
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