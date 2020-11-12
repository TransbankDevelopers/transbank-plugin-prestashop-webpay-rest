# Transbank Prestashop Webpay
Plugin oficial de Webpay para Prestashop

## Descripción
Este plugin **oficial** de Transbank te permite integrar Webpay fácilmente en tu sitio Prestashop. Está desarrollado en base al [SDK oficial de PHP](https://github.com/TransbankDevelopers/transbank-sdk-php)

### ¿Cómo instalar?
Puedes ver las instrucciones de instalación y la documentación completa del plugin en [transbankdevelopers.cl/plugin/prestashop/](https://www.transbankdevelopers.cl/plugin/prestashop/)

### Paso a producción
Al instalar el plugin, este vendrá configurado para funcionar en modo '**integración**'(en el ambiente de pruebas de Transbank). Para poder operar con dinero real (ambiente de **producción**), debes:

1. Tener tu propio código de comercio. Si no lo tienes, solicita Webpay Plus en [transbank.cl](https://transbank.cl)
2. Debes [generar tus credenciales](https://www.transbankdevelopers.cl/documentacion/como_empezar#credenciales-en-webpay)  (llave privada y llave pública) usando tu código de comercio. 
3. Enviar [esta planilla de integración](https://transbankdevelopers.cl/files/evidencia-integracion-webpay-plugins.docx) a soporte@transbank.cl, junto con la llave pública (generada en el paso anterior) y tu logo. 
4. Cuando Transbank confirme que ha cargado tu certificado público y logo, debes entrar a la pantalla de configuración del plugin dentro de Prestashop y colocar tu código de comercio, llave privada, llave pública y poner el ambiente de 'Producción'. 
5. Debes hacer una compra de $10 en el ambiente de producción para confirmar el correcto funcionamiento. 

Puedes ver más información sobre este proceso en [este link](https://www.transbankdevelopers.cl/documentacion/como_empezar#puesta-en-produccion).

# Desarrollo
A continuación, encontrarás información necesaria para el desarrollo de este plugin. 

## Requisitos 
* PHP 5.6 o superior
* Prestashop 1.6 o superior

## Dependencias

El plugin depende de las siguientes librerías:

* transbank/transbank-sdk
* tecnickcom/tcpdf
* apache/log4php

Para cumplir estas dependencias, debes instalar [Composer](https://getcomposer.org), e instalarlas con el comando `composer install`.

    Opcionalmente puedes instalar composer ejecutando el bash `composer_install.sh` 
    que esta en la raiz de este proyecto. Te pedira tu contraseña de root.

## Nota  
- La versión del sdk de php se encuentra en el archivo `webpay/composer.json`

## Desarrollo

Para apoyar el levantamiento rápido de un ambiente de desarrollo, hemos creado la especificación de contenedores a través de Docker Compose.

Para usarlo seguir el siguiente [README Prestashop 1.7 con php 7.2](./docker-prestashop1.7-php7.2)  
Para usarlo seguir el siguiente [README Prestashop 1.7 con php 7.1](./docker-prestashop1.7-php7.1)  
Para usarlo seguir el siguiente [README Prestashop 1.7 con php 5.6](./docker-prestashop1.7-php5.6)  
Para usarlo seguir el siguiente [README Prestashop 1.6 con php 5.6](./docker-prestashop1.6-php5.6)  

### Crear el instalador del plugin

    ./package.sh

## Generar una nueva versión

Para generar una nueva versión, se debe crear un PR (con un título "Prepare release X.Y.Z" con los valores que correspondan para `X`, `Y` y `Z`). Se debe seguir el estándar semver para determinar si se incrementa el valor de `X` (si hay cambios no retrocompatibles), `Y` (para mejoras retrocompatibles) o `Z` (si sólo hubo correcciones a bugs).

En ese PR deben incluirse los siguientes cambios:

1. Modificar el archivo CHANGELOG.md para incluir una nueva entrada (al comienzo) para `X.Y.Z` que explique en español los cambios.

Luego de obtener aprobación del pull request, debes mezclar a master e inmediatamente generar un release en GitHub con el tag `vX.Y.Z`. En la descripción del release debes poner lo mismo que agregaste al changelog.

Con eso Travis CI generará automáticamente una nueva versión del plugin y actualizará el Release de Github con el zip del plugin.
