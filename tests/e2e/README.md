# E2E Tests — Transbank PrestaShop Plugin

Tests end-to-end con Playwright que validan los flujos de pago del plugin Transbank sobre una instancia real de PrestaShop + MariaDB corriendo en el devcontainer.

## Qué se valida actualmente

### Webpay Plus

| Test                     | Archivo                                        | Descripción                                                                                                                                     |
| ------------------------ | ---------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Pago normal              | `webpay-plus/webpay-payment.spec.js`           | Flujo completo: login → carrito → checkout → pago en Transbank → confirmación de orden                                                          |
| Lock previene duplicados | `webpay-plus/webpay-duplicated-return.spec.js` | Simula dos pestañas retornando con el mismo token simultáneamente. Verifica que no se creen órdenes duplicadas                                  |
| Retry con lock ocupado   | `webpay-plus/webpay-duplicated-return.spec.js` | Fuerza el timeout de `GET_LOCK` adquiriendo el lock externamente. Verifica que el retry procesa la transacción correctamente                    |
| Reintentos agotados      | `webpay-plus/webpay-duplicated-return.spec.js` | Duplica la pestaña en el retorno. La primera procesa mientras la segunda agota los 3 reintentos. Verifica página de error sin duplicar la orden |

## Prerequisitos

- Devcontainer corriendo (`docker compose up` desde `.devcontainer/`).
- Módulo Webpay instalado y configurado en modo integración.
- Cliente de prueba creado en PrestaShop con dirección precargada.
- Navegadores de Playwright instalados.

## Setup

```bash
cd tests/e2e
pnpm install
pnpm setup
```

Copiar `.env.example` a `.env` y ajustar si es necesario:

```bash
cp .env.example .env
```

## Variables de entorno

| Variable            | Default                 | Descripción                       |
| ------------------- | ----------------------- | --------------------------------- |
| `BASE_URL`          | `http://localhost:8080` | URL de la instancia de PrestaShop |
| `CUSTOMER_EMAIL`    | `test.user@example.com` | Email del cliente de prueba       |
| `CUSTOMER_PASSWORD` | `Password123!`          | Contraseña del cliente de prueba  |
| `DB_HOST`           | `localhost`             | Host de MariaDB                   |
| `DB_PORT`           | `3306`                  | Puerto de MariaDB                 |
| `DB_USER`           | `prestashop`            | Usuario de MariaDB                |
| `DB_PASSWORD`       | `prestashop123`         | Contraseña de MariaDB             |
| `DB_NAME`           | `prestashop`            | Nombre de la base de datos        |

## Ejecución

```bash
# Todos los tests
pnpm test

# Tests de Webpay Plus
pnpm test:webpay-plus

# Modo debug (Playwright Inspector)
pnpm test:debug
```

Todos los comandos corren en modo **headed** (con navegador visible).

## Ver resultados

Playwright genera un reporte HTML después de cada ejecución:

```bash
pnpm report
```

Cuando un test falla se guardan automáticamente en `test-results/`:

- **Screenshot** de la página al momento del fallo.
- **Trace** interactivo (se abre con `pnpm playwright show-trace <archivo.zip>`).
- **Video** de la ejecución completa del test.

## Estructura

```
tests/e2e/
├── specs/                        # Tests agrupados por medio de pago
│   └── webpay-plus/
├── helpers/                      # Funciones reutilizables
│   ├── checkout.js               # Login, carrito, checkout PrestaShop
│   ├── webpay-form.js            # Formulario de tarjeta Transbank
│   ├── database.js               # Queries a MariaDB vía mysql2
│   └── assertions.js             # Assertions reutilizables (expectOrderConfirmation, expectPaymentError, etc.)
├── playwright.config.js
├── package.json
├── .env.example
└── .env                          # (gitignored)
```

## Agregar nuevos tests

1. Crear una carpeta en `specs/` para el medio de pago (ej. `specs/oneclick/`).
2. Crear archivos `.spec.js` dentro de esa carpeta.
3. Reutilizar los helpers existentes (`checkout.js`, `webpay-form.js`, `database.js`, `assertions.js`) o agregar nuevos en `helpers/`.
4. Agregar un script `test:<medio>` en `package.json` para ejecutar solo esa sección.

## Notas

- Los tests corren con `workers: 1` y `fullyParallel: false` porque comparten estado en la base de datos.
- El timeout general es de 120 segundos por test, dado que involucran redirecciones externas a Transbank.
- Los selectores de `checkout.js` asumen el theme Hummingbird con locale español (`PS_LANGUAGE=es`).
- `database.js` se conecta a MariaDB vía `mysql2` con queries parametrizadas. Los valores por defecto de conexión coinciden con los del `docker-compose.yml`, por lo que no es necesario configurar variables de DB si se usa el devcontainer estándar.
