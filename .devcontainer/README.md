# DevContainer - Prestashop Webpay Module

Este devcontainer proporciona un entorno completo de desarrollo para el módulo Webpay de PrestaShop.

## 🚀 Inicio rápido

1. Abre el proyecto en VS Code
2. Cuando se te pregunte, selecciona "Reopen in Container"
3. Espera a que se construya el contenedor (puede tomar unos minutos la primera vez)
4. Una vez listo, PrestaShop estará disponible en http://localhost:8080

## 📋 Servicios incluidos

-   **PrestaShop 8.2.0** con PHP 8.1 con FPM
-   **MariaDB 10.11** como base de datos
-   **Apache** para servir el contenido
-   **Extensiones de base de datos de VS Code** (SQLTools + MySQL Client) para administración SQL
-   **Composer** para gestión de dependencias PHP

## 🔗 URLs de acceso

| Servicio      | Acceso                        | Credenciales               |
| ------------- | ----------------------------- | -------------------------- |
| PrestaShop    | http://localhost:8080         | -                          |
| Admin Panel   | http://localhost:8080/adminop | admin@admin.com / password |
| Base de datos | VS Code SQLTools/MySQL Client | prestashop / prestashop123 |

## 🛠️ Herramientas de desarrollo

### Administración de base de datos con VS Code

El devcontainer incluye dos extensiones poderosas para trabajar con la base de datos:

#### SQLTools

-   **Acceso**: Ctrl/Cmd + Shift + P → "SQLTools: Connect"
-   **Conexiones preconfiguradas**:
    -   `PrestaShop MariaDB` - Base de datos principal
    -   `MariaDB Root` - Acceso administrativo completo
-   **Funcionalidades**: Explorar tablas, ejecutar queries, exportar datos

#### MySQL Client

-   **Acceso**: Icono de base de datos en la barra lateral
-   **Funciones**: Navegador visual de tablas, editor SQL, gestión de datos

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

### Base de datos de pruebas

También se crea automáticamente `prestashop_test` para pruebas unitarias.

## 📝 Notas de desarrollo

1. **Permisos**: El usuario es root, por lo que tiene acceso completo al contenedor
2. **Persistencia**: Los datos de PrestaShop persisten entre reinicios
3. **Hot reload**: Los cambios en PHP se aplican inmediatamente

## 🐛 Solución de problemas

### PrestaShop no carga

Si el contenedor web no arranca, revisa si tienes el archivo `install.lock`, si existe eliminado y recarga el contenedor.

### Base de datos no conecta

```bash
# Verificar estado de MariaDB
mysql -h db -u prestashop -pprestashop123 -e "SELECT 1;"
```
