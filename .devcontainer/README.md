# DevContainer - Prestashop Webpay Module

Este devcontainer proporciona un entorno completo de desarrollo para el módulo Webpay de PrestaShop.

## 🚀 Inicio rápido

1. Abre el proyecto en VS Code
2. Cuando se te pregunte, selecciona "Reopen in Container"
3. Espera a que se construya el contenedor (puede tomar unos minutos la primera vez)
4. Una vez listo, PrestaShop estará disponible en http://localhost:8080

## 📋 Servicios incluidos

-   **PrestaShop 9.0.3** con PHP 8.2.
-   **MariaDB 10.11** como base de datos.
-   **Apache** para servir el contenido.
-   **Extensiones de VS Code** para trabajar con PHP y PrestaShop
-   **Composer** para gestión de dependencias PHP.

## 🔗 URLs de acceso

| Servicio      | Acceso                          | Credenciales                         |
| ------------- | ------------------------------- | ------------------------------------ |
| PrestaShop    | http://localhost:8080           | -                                    |
| Admin Panel   | http://localhost:8080/admin-dev | admin@admin.com / password           |
| User Panel    | http://localhost:8080/mi-cuenta | test.user@example.com / Password123! |
| Base de datos | VS Code SQLTools/MySQL Client   | prestashop / prestashop123           |

## 🛠️ Herramientas de desarrollo

### Administración de base de datos con VS Code

El devcontainer incluye una extensión para trabajar con la base de datos:

#### SQLTools

-   **Acceso**: Ctrl/Cmd + Shift + P → "SQLTools: Connect"
-   **Conexiones preconfiguradas**:
    -   `PrestaShop MariaDB` - Base de datos principal
    -   `MariaDB Root` - Acceso administrativo completo
-   **Funcionalidades**: Explorar tablas, ejecutar queries, exportar datos

### Estructura del proyecto en el contenedor

```
/workspace/                    # Código fuente (montado desde el host)
/var/www/html/                # Instalación de PrestaShop
/var/www/html/modules/webpay/ # Módulo Webpay (enlazado desde /workspace/webpay)
```

## 🔧 Configuración del módulo

El módulo Webpay se monta automáticamente en `/var/www/html/modules/webpay/` y se activa, el sitio instala productos de prueba y configura el envió.

### Desarrollo del módulo

1. Los cambios se reflejan automáticamente en PrestaShop
2. Los logs se guardan en `.devcontainer/container/logs/`
3. Se ha incluido la carpeta de Prestashop en Intelephense para tener las referencias de código de Prestashop.

## 📦 Dependencias

Las dependencias de Composer se instalan automáticamente al crear el contenedor. Si necesitas instalar nuevas dependencias:

```bash
cd /workspace/webpay
composer require nueva-dependencia
```

## 🗄️ Base de datos

### Configuración por defecto

-   Host: `db`
-   Puerto: `3306`
-   Base de datos: `prestashop`
-   Usuario: `prestashop`
-   Contraseña: `prestashop123`

## 📝 Notas de desarrollo

1. **Permisos**: El usuario es root, por lo que tiene acceso completo al contenedor.
2. **Persistencia**: Los datos de PrestaShop **NO persisten** entre reinicios.
3. **Hot reload**: Los cambios en PHP se aplican inmediatamente.
4. **Logs**: Los logs se encuentra en .devcontainer/container/logs

## 🐛 Solución de problemas

### PrestaShop no carga

Si el contenedor web no arranca, revisa si tienes el archivo `install.lock`, si existe elimínalo y recarga el contenedor.

### Base de datos no conecta

```bash
# Verificar estado de MariaDB
mysql -h db -u prestashop -pprestashop123 -e "SELECT 1;"
```

## Edición devcontainer

En caso de editar el devcontainer, es importante que se reconstruya la imagen para que los cambios se reflejen si ya se uso anteriormente.
En algunas ocasiones detecta los cambios y el editor sugiere reconstruir el contenedor. En caso contrario se debe hacer manualmente.

### Reconstruir el devcontainer

-   Desde VS Code: abre la paleta de comandos (Ctrl/Cmd + Shift + P) → ejecuta **Dev Containers: Rebuild Container**. Selecciona **Rebuild Container** para iniciar el proceso.
-   Alternativa rápida: haz clic en el icono de la esquina inferior izquierda (Remote) → "Reopen in Container" y acepta la opción de reconstruir si se muestra.
-   Si no se aplica algún cambio (Docker no disponible o caché): reconstruye manualmente desde tu entorno Docker según tu flujo de trabajo local (ej. build sin caché), o elimina la imagen del devcontainer antes de reconstruir.
-   Nota importante: la reconstrucción vuelve a crear la imagen y el contenedor; cualquier dato no persistente en el contenedor (ej. instalación temporal de PrestaShop) se perderá. Asegúrate de respaldar lo necesario antes de reconstruir.
