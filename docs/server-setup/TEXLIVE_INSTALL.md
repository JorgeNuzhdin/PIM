# TexLive — instalación/reinstalación en el servidor

PIM compila PDF en el servidor con `pdflatex`. Esta guía describe cómo
reinstalar TexLive cuando el hosting migra la arquitectura, se rompe la
instalación, o hay que cambiar de versión.

## Síntomas de que está roto

- Botones "Descargar PDF" en hojas / carrito devuelven 500.
- En `storage/logs/laravel.log`:
  ```
  pdflatex pass 1: exit code 126
  Error compilando PDF para sheet ... Errores: Error desconocido
  ```
- Exit code 126 = "command found but not executable". La causa más probable
  es que el binario instalado es de otra arquitectura (ARM↔x86) tras
  una migración del hosting.

## Diagnóstico rápido

```bash
# 1. ¿Qué arquitectura tiene la máquina ahora?
uname -m                              # → x86_64 / aarch64 / ...

# 2. ¿Qué arquitectura tiene el binario instalado?
file $(grep PDFLATEX_PATH /home/www/PIM/.env | cut -d= -f2)
# Si dice "ARM aarch64" y uname dice "x86_64" → arquitectura cambiada.

# 3. ¿Pdflatex corre directamente?
$(grep PDFLATEX_PATH /home/www/PIM/.env | cut -d= -f2) --version
# Si sale "cannot execute binary file: Exec format error" → reinstalar.
```

## Reinstalación completa

### 1. Descargar el installer fresco de TUG

```bash
cd ~
wget https://mirror.ctan.org/systems/texlive/tlnet/install-tl-unx.tar.gz
tar -xzf install-tl-unx.tar.gz
# Crea un directorio install-tl-YYYYMMDD/
```

### 2. Preparar el profile

Está en este repo en `docs/server-setup/texlive.profile`. Cópialo al home
del servidor y ajusta:

- `binary_x86_64-linux 1` (o `aarch64-linux`, según `uname -m`)
- Las rutas `TEXDIR` y similares si las quieres en otra ubicación

```bash
cp /home/www/PIM/docs/server-setup/texlive.profile ~/texlive.profile
# Editar si hace falta cambiar la arquitectura
```

### 3. Si ya hay una instalación rota, hacer backup primero

```bash
mv /home/www/texlive/2025 /home/www/texlive/2025.bak.$(date +%F)
```

### 4. Lanzar installer

```bash
# /tmp suele ser pequeño en hostings — redirigir tmp al home (mucho espacio)
mkdir -p ~/tlmgr-tmp
export TMPDIR=~/tlmgr-tmp

# Si el year actual de TexLive ya rotó (TL2026, TL2027...) y queremos
# mantener TL2025 — apuntar al mirror histórico. Si quieres la version
# actual, omitir -repository.
~/install-tl-*/install-tl \
    -profile ~/texlive.profile \
    -repository https://ftp.math.utah.edu/pub/tex/historic/systems/texlive/2025/tlnet-final \
    -no-interaction
```

La instalación tarda ~10–15 minutos. Termina con "Welcome to TeX Live!".

### 5. Instalar paquetes adicionales que `scheme-basic` no trae

```bash
export TMPDIR=~/tlmgr-tmp
/home/www/texlive/2025/bin/x86_64-linux/tlmgr \
    --repository https://ftp.math.utah.edu/pub/tex/historic/systems/texlive/2025/tlnet-final \
    install \
        collection-latexrecommended \
        collection-mathscience \
        collection-fontsrecommended \
        collection-pictures \
        collection-latexextra \
        mhsetup \
        babel-spanish
```

Las **collections** instalan ~50 paquetes en bloque cada una. Si compilando
una hoja sale `File 'X.sty' not found` sobre algo no cubierto, instálalo
puntual:

```bash
/home/www/texlive/2025/bin/x86_64-linux/tlmgr \
    --repository https://ftp.math.utah.edu/pub/tex/historic/systems/texlive/2025/tlnet-final \
    install X
```

### 6. Actualizar `.env` y limpiar caché

```bash
# .env debe apuntar al binario instalado
sed -i 's|PDFLATEX_PATH=.*|PDFLATEX_PATH=/home/www/texlive/2025/bin/x86_64-linux/pdflatex|' \
    /home/www/PIM/.env
grep PDFLATEX /home/www/PIM/.env

cd /home/www/PIM && php artisan config:clear
```

### 7. Verificar

```bash
# Compilación mínima
cat > /tmp/test.tex <<'EOF'
\documentclass{article}
\usepackage{mathtools}
\usepackage{tikz}
\begin{document}
$x^2 + y^2 = z^2$
\end{document}
EOF
cd /tmp && /home/www/texlive/2025/bin/x86_64-linux/pdflatex -interaction=nonstopmode test.tex
# Si dice "Output written on test.pdf" → OK.
```

Luego prueba a descargar el PDF de una hoja desde la web.

## Paquetes empaquetados en este repo (no necesitan tlmgr)

`LatexCompilerService::compile()` copia automáticamente todos los archivos
de `resources/tex/packages/` (.sty, .tex, .fd, .def, .tfm, .pfb, .map, .cfg, .pdf)
al directorio temporal de compilación. Por eso estos paquetes **NO** hay
que instalarlos con tlmgr:

- `adjustbox`, `caption`, `subcaption`
- `chessboard`, `chessfss`, `skak`, `xskak` + fuentes SkakNew/AlphaDia
- `collectbox`, `enumitem`, `environ`, `etoolbox`
- `eurosym`, `float`, `gensymb`, `icomma`, `ifmtarg`
- `listofitems`, `mathrsfs`, `mathtools`, `multirow`
- `pgfplots`, `stackengine`, `tikz-cd`, `tkz-euclide`
- `trimclip`, `trimspaces`, `twemojis`, `xifthen`, `xparse`

Si añades dependencias nuevas a problemas/métodos, prefiere bundlear el .sty
en `resources/tex/packages/` antes que pedirle al admin que ejecute tlmgr.

## Causas conocidas de roturas

1. **Migración de hosting ARM ↔ x86** (mayo 2026). Solución: reinstalar con
   `binary_<arch>-linux 1` en el profile.
2. **Disco lleno en /tmp** durante tlmgr install. Solución:
   `export TMPDIR=~/tlmgr-tmp`.
3. **TL frozen** tras release nueva. Solución: usar mirror histórico
   `https://ftp.math.utah.edu/pub/tex/historic/systems/texlive/YYYY/tlnet-final`.

## Health check (opcional pero recomendado)

Cron semanal que avisa si TexLive se cae:

```cron
0 8 * * 1 /home/www/texlive/2025/bin/x86_64-linux/pdflatex --version >/dev/null 2>&1 || \
    echo "TexLive caído en $(hostname). uname: $(uname -m). $(date)" | \
    mail -s "PIM PDF roto" jorgemoscu@gmail.com
```
