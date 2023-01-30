# Changelog
Todos los cambios notables a este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
y este proyecto adhiere a [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.0.1-Beta] 2023-01-30
- Se agrega un servicio para recolectar datos que nos permitira darle mayor seguimiento a las versiones del plugin y las versiones de Prestashop mas usadas.

## [2.0.0-Beta] 2023-01-24
- Se realiza un refactor de la totalidad del código para mejorar su legibilidad
- Se migran los formularios del módulo de administración a Symfony.
- Se sube la versión minima soportada a 1.7.6.0
- Se mejora el log detallado para darle seguimiento a los errores.
- Se mejora visualmente el módulo de administracion para Webpay.
- Se agrego un módulo de administración para Oneclick.
- Se agrego la opción de inscribir tarjetas utilizando Oneclick.
- Se agrego soporte para agregar multiples tarjetas por usuario para Oneclick.

## [1.1.3] 2022-11-16
- Se agrega un log detallado para darle seguimiento a los errores.
- Se agrega un try catch en la inicialización del servicio de telemetría.

## [1.1.2] 2022-03-29
- Se soluciona el problema que se produce al comparar el monto que se paga por Webpay y el monto del carrito cuando tiene decimales.

## [1.1.1] 2021-12-29
- Se soluciona un problema al configurar las credenciales de producción.

## [1.1.0] 2021-12-14
- Se actualiza SDK de PHP a versión 2.0, por lo que ahora se usa la API v1.2 de Transbank.
- Se elimina botón para la generación del PDF de diagnostico.

## [1.0.3] 2021-08-18
- Se arregla caso en que usuarios pueden manipular monto de carrito durante el proceso de pago.

## [1.0.2]
- Permite configurar estado del pedido [PR 13](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/pull/13).
- Se agregan datos de la transacción al detalle de la orden [PR 12](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/pull/12).
- Se mejora coding style [PR 14](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/pull/14).
- Se mejora compatibilidad con Prestashop 1.7.7 [PR 18](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/pull/18).

## [1.0.1] - 2020-11-12
- Se soluciona error 500 cuando una transacción era rechazada [PR 5](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/pull/5).
- Se mejora documentación de instalación [PR 4](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/pull/4).

## [1.0.0] - 2020-11-12
- Primer release.
