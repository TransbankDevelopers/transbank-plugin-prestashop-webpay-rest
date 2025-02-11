# Transbank PrestaShop Webpay

Plugin oficial de Webpay para PrestaShop

## Descripción

Este plugin **oficial** de Transbank te permite integrar Webpay fácilmente en tu sitio PrestaShop. Está desarrollado en base al [SDK oficial de PHP](https://github.com/TransbankDevelopers/transbank-sdk-php)

## ¿Cómo instalar?

Puedes ver las instrucciones de instalación y la documentación completa del plugin en [transbankdevelopers.cl/plugin/prestashop/](https://www.transbankdevelopers.cl/plugin/prestashop/#instalacion)

## Paso a producción

Al instalar el plugin, este vendrá configurado para funcionar en modo '**integración**'(en el ambiente de pruebas de Transbank). Para poder operar con dinero real (ambiente de **producción**), debes tener en cuenta la siguiente información:

Si ya tienes tu código de comercio de producción y llave secreta, solo debes entrar a la configuración de tu plugin [instrucciones en este link](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay-rest/blob/master/docs/INSTALLATION.md#configuraci%C3%B3n) y colocar:

Ambiente: Producción

Código de comercio: tu código de comercio de producción

Api Key: Tu llave secreta

Al guardar, el plugin funcionará inmediatamente en ambiente de producción y podrás operar con tarjetas y transacciones reales. Se te solicitará realizar una transacción real en este ambiente de producción por $50 para finalizar tu proceso.

Puedes ver más información sobre este proceso en [este link](https://www.transbankdevelopers.cl/documentacion/como_empezar#puesta-en-produccion).

# Desarrollo

A continuación, encontrarás información necesaria para el desarrollo de este plugin.

# Requisitos

-   PHP 7.0+ o superior
# Requisitos 
* PHP 7.0+ o superior
* Prestashop 1.7 o superior

# Dependencias

El plugin depende de las siguientes librerías:

-   transbank/transbank-sdk
-   monolog/monolog

Para cumplir estas dependencias, debes instalar [Composer](https://getcomposer.org), e instalarlas con el comando `composer install`.

    Opcionalmente puedes instalar composer ejecutando el bash `composer_install.sh`
    que esta en la raíz de este proyecto. Te pedirá tu contraseña de root.

## Nota

-   La versión del sdk de php se encuentra en el archivo `webpay/composer.json`

## Desarrollo

Para apoyar el levantamiento rápido de un ambiente de desarrollo, hemos creado la especificación de contenedores a través de Docker Compose.

Para usarlo seguir el siguiente [README PrestaShop 8.0.3 con php 8.0](./docker-prestashop-php8.0-pres8.0.3-apache/README.md)

Para usarlo seguir el siguiente [README PrestaShop 1.7.8.5 con php 7.4](./docker-prestashop-php7.4-pres1.7.8.6-apache/README.md)

### Crear el instalador del plugin

    ./package.sh

## Generar una nueva versión

Para generar una nueva versión, se debe crear un PR (con un título "Prepare release X.Y.Z" con los valores que correspondan para `X`, `Y` y `Z`). Se debe seguir el estándar semver para determinar si se incrementa el valor de `X` (si hay cambios no retrocompatibles), `Y` (para mejoras retrocompatibles) o `Z` (si sólo hubo correcciones a bugs).

En ese PR deben incluirse los siguientes cambios:

1. Modificar el archivo CHANGELOG.md para incluir una nueva entrada (al comienzo) para `X.Y.Z` que explique en español los cambios.

Luego de obtener aprobación del pull request, debes mezclar a master e inmediatamente generar un release en GitHub con el tag `vX.Y.Z`. En la descripción del release debes poner lo mismo que agregaste al changelog.

Con eso Travis CI generará automáticamente una nueva versión del plugin y actualizará el Release de Github con el zip del plugin.

## Estándares generales

-   Para los commits nos basamos en las siguientes normas: https://github.com/angular/angular.js/blob/master/DEVELOPERS.md#commits👀
-   Todas las mezclas a master se hacen mediante Pull Request ⬇️
-   Usamos inglés para los mensajes de commit 💬
-   Se pueden usar tokens como WIP en el subject de un commit separando el token con ':', por ejemplo -> 'WIP: this is a useful commit message'
-   Para los nombres de ramas también usamos inglés
-   Se asume que una rama de feature no mezclada, es un feature no terminado ⚠️
-   El nombre de las ramas va en minúscula 🔤
-   El nombre de la rama se separa con '-' y las ramas comienzan con alguno de los short lead tokens definidos a continuación, por ejemplo -> 'feat/tokens-configuration' 🌿

### **Short lead tokens**

`WIP` = En progreso

`feat` = Nuevos features

`fix` = Corrección de un bug

`docs` = Cambios solo de documentación

`style` = Cambios que no afectan el significado del código (espaciado, formateo de código, comillas faltantes, etc)

`refactor` = Un cambio en el código que no arregla un bug ni agrega una funcionalidad

`perf` = Cambio que mejora el rendimiento

`test` = Agregar test faltantes o los corrige

`chore` = Cambios en el build o herramientas auxiliares y librerías

## Reglas

1️⃣ - Si no se añaden test en el pull request, se debe añadir un video o gif mostrando el cambio realizado y demostrando que la rama no rompe nada.

2️⃣ - El pr debe tener 2 o mas aprobaciones para hacer el merge

3️⃣ - si un commit revierte un commit anterior deberá comenzar con "revert:" seguido con texto del commit anterior

## Pull Request

### Asunto ✉️

-   Debe comenzar con el short lead token definido para la rama, seguido de ':' y una breve descripción del cambio
-   Usar imperativos en tiempo presente: "change" no "changed" ni "changes"
-   No usar mayúscula en el inicio
-   No usar punto . al final

### Descripción 📃

Igual que en el asunto, usar imperativo y en tiempo presente. Debe incluir una mayor explicación de lo que se hizo en el pull request. Si no se añaden test en el pull request, se debe añadir un video o gif mostrando el cambio realizado y demostrando que la rama no rompe nada.
