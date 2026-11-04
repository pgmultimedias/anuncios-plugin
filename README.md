# Anuncios Rotativo Pro

Plugin de WordPress para administrar y mostrar banners rotativos, anuncios de código y videos.

## Estructura

```text
anuncios-rotativo-pro/
├── anuncios-rotativo-pro.php       # Cabecera del plugin, constantes y carga de módulos
├── assets/
│   ├── css/frontend.css            # Estilos del frontend
│   └── js/frontend.js              # Rotación base de banners
└── includes/
    ├── admin/
    │   ├── columns.php             # Columnas de administración
    │   ├── meta-boxes.php          # Edición y guardado de anuncios
    │   └── settings.php            # Pantallas y opciones del plugin
    ├── core/
    │   ├── helpers.php             # Funciones compartidas de anuncios
    │   └── post-type.php           # Registro del tipo de contenido
    ├── frontend/
    │   ├── assets.php              # Enqueue de CSS/JS y configuración dinámica
    │   ├── display-modes.php       # Pop-up, sticky, interstitial e In-Article
    │   └── shortcodes.php          # Shortcodes de anuncios
    └── cron.php                   # Tareas programadas de vencimiento
```

## Shortcodes

- `[anuncio_rotativo lugar='lugar_001']`
- `[anuncio_sticky]`

Los valores de `lugar` deben coincidir con el identificador del lugar seleccionado en el anuncio, por ejemplo `lugar_popup` o `lugar_001`.

## Desarrollo y empaquetado

El archivo instalable se distribuye como `anuncios-rotativo-pro.zip`. Al regenerarlo, conserva la carpeta superior `anuncios-rotativo-pro/` para que WordPress instale el plugin con su estructura completa.
