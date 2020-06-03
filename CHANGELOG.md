# Changelog
Todos los cambios notables a este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
y este proyecto adhiere a [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [3.1.0] - 2019-06-13
### Changed
- Se añade soporte comprobado al plugin hasta PHP 7.2.19 + PrestaShop 1.7.5.2
### Fixed
- Corrige botón `VERIFICAR CONEXIÓN` a Transbank en pantalla de administración del plugin.
- Corrige generación de PDFs en pantalla de administración del plugin.

## [3.0.19] - 2019-04-18
### Fixed
- Corrige configuración, Ya no es necesario incluir el certificado de Webpay

## [3.0.18] - 2019-04-04
### Fixed
- Corrige despliegue de información en el detalle de la transacción realizada, ahora se visualiza toda la información

## [3.0.17] - 2019-03-05
### Changed
- Se corrige compatibilidad para tener instalado Webpay y Onepay sin conflictos en el mismo Prestashop.

## [3.0.16] - 2019-01-14
### Changed
- Se elimina la condición de VCI == "TSY" || VCI == "" para evaluar la respuesta de getTransactionResult debido a que
esto podría traer problemas con transacciones usando tarjetas internacionales.

## [3.0.15] - 2018-12-27
### Fixed
- Corrige creación de url para webpay.
### Added
- Agrega logs de transacciones para poder obtener los datos como token, orden de compra, etc.. necesarios para el proceso de certificación.

## [3.0.14] - 2018-12-21
### Fixed
- Corrige validación de certificados

## [3.0.13] - 2018-12-20
### Fixed
- Se corrige un problema evitando que se pueda agregar items al carro durante el proceso de pago.
### Changed
- Se mejora el proceso de pago para los casos cancelado, con error y exitoso.
  
## [3.0.12] - 2018-12-06
### Changed
- Se mejoran las pantallas de error y éxito, ahora muestra la orden de compra, fecha y hora en las pantallas de error.

## [3.0.11] - 2018-11-30
### Changed
- Se corrige un problema evitando que se pueda agregar items al carro durante el proceso de pago.

## [3.0.10] - 2018-11-28
### Changed
- Se corrige un problema con plantillas de prestashop en las paginas de éxito y error del plugin.

## [3.0.9] - 2018-11-28
### Changed
- Se mejora la experiencia de pago.
- Se mejoran las validaciones internas del proceso de pago.

## [3.0.8] - 2018-11-27
### Changed
- Se mejora la creación del pdf de diagnóstico.
- Se elimina la comprobación de la extensión mcrypt dado que ya no es necesaria por el plugin.

## [3.0.7] - 2018-11-09
### Changed
- Se corrigen varios problemas internos del plugin para entregar una mejor experiencia en prestashop con Webpay.
- Ahora el certificado de transbank Webpay es opcional.
- Ahora soporta php 7.1
- Ahora soporta prestashop 1.7 y 1.6

## [3.0.6] - 2018-08-24
### Changed
- Se modifica código de comercio y certificados.

## [3.0.5] - 2018-08-16
### Changed
- Se modifica implementación de la herramienta de diagnóstico.

## [3.0.4] - 2018-05-28
### Changed
- Se modifica certificado de servidor para ambiente de integracion.

## [3.0.3] - 2018-05-18
### Changed
- Se corrige SOAP para registrar versiones.

## [3.0.2] - 2018-04-12
### Changed
- Se modifica certificado de servidor para ambiente de integracion.

## [3.0.1] - 2018-03-14
### Added
- Se agrega archivo "changelog" para mantener orden de cambios realizados plugin

### Modificado
- Se modifica validacion para  transacciones internacionales
