# Manual de instalación para Plugin Prestashop

## Descripción

Este plugin oficial ha sido creado para que puedas integrar Webpay fácilmente en tu comercio, basado en Prestashop.

## Requisitos

Debes tener instalado previamente Prestashop.

Habilitar los siguientes módulos / extensiones para PHP:
- Soap
- OpenSSL 1.0.1 o superior
- SimpleXML
- DOM 2.7.8 o superior

## Instalación de Plugin

1. Dirígete a [https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay/releases/latest](https://github.com/TransbankDevelopers/transbank-plugin-prestashop-webpay/releases/latest), y descargue la última versión disponible del plugin.

  Una vez descargado el plugin, ingresa a la página de administración de Prestashop (usualmente en _misitio.com_/admin), y dirígete a Módulos, Módulos y Servicios, indicado a continuación:

  ![Paso 1](img/paso1.png)
  
2. Haz click sobre el botón "Subir un módulo":

  ![Paso 2](img/paso2.png)
  
3. Se abrirá un cuadro para subir el módulo descargado previamente. Procede a arrastrar el archivo, o haz click en "selecciona el archivo" y selecciónalo desde tu computador:

  ![Paso 3](img/paso3.png)

4. Prestashop procederá a instalar el módulo. Una vez finalizado, se te indicará que el módulo fue instalado, y cuando esto suceda debes hacer click en el botón "Configurar":

  ![Paso 4](img/paso4.png)

## Configuración

Este plugin posee un sitio de configuración que te permitirá ingresar credenciales que Transbank te otorgará, y además podrás generar un documento de diagnóstico en caso que Transbank te lo pida.

Para acceder a la configuración, debes seguir los siguientes pasos:

1. Dirígete a la página de administración de Prestashop (usualmente en _misitio.com_/admin), y luego anda a Módulos, Módulos y Servicios.

  ![Paso 1](img/paso1.png)

2. Busca "Webpay", y presiona el botón "Configurar":

  ![Paso 2](img/paso5.png)

3. ¡Ya está! Estás en la pantalla de configuración del plugin, debes ingresar la siguiente información:

  * **Ambiente**: Ambiente hacia donde se realiza la transacción. 
  * **Código de comercio**: Es lo que te identifica como comercio.
  * **Llave Privada**: Llave secreta que te autoriza y valida a hacer transacciones.
  * **Certificado**: Llave publica que te autoriza y valida a hacer transacciones.
  * **Certificado Transbank**: Llave secreta de webpay que te autoriza y valida a hacer transacciones.

  Las opciones disponibles para _Ambiente_ son: "Integración" para realizar pruebas y certificar la instalación con Transbank, y "Producción" para hacer transacciones reales una vez que Transbank ha aprobado el comercio.
  
### Credenciales de Prueba

Para el ambiente de Integración, puedes utilizar las siguientes credenciales para realizar pruebas:

* Código de comercio: `597020000540`
* Llave Privada: Se puede encontrar [aquí - private_key](https://github.com/TransbankDevelopers/transbank-webpay-credenciales/blob/master/integracion/Webpay%20Plus%20-%20CLP/597020000540.key)
* Certificado Publico: Se puede encontrar [aquí - public_cert](https://github.com/TransbankDevelopers/transbank-webpay-credenciales/blob/master/integracion/Webpay%20Plus%20-%20CLP/597020000540.crt)
* Certificado Webpay: Se puede encontrar [aquí - webpay_cert](https://github.com/TransbankDevelopers/transbank-sdk-php/blob/master/lib/webpay/webpay.php#L39)

1. Guardar los cambios presionando el botón [Guardar]

2. Además, puedes generar un documento de diagnóstico en caso que Transbank te lo pida. Para ello, haz click en el botón "Información" ahí podrás descargar un pdf.

  ![Paso 6](img/paso6.png)

  ![Paso 7](img/paso7.png)

## Prueba de instalación con transacción

En ambiente de integración es posible realizar una prueba de transacción utilizando un emulador de pagos online.

* Ingresa al comercio

  ![demo1](img/demo1.png)

* Ya con la sesión iniciada, ingresa a cualquier sección para agregar productos

  ![demo2](img/demo2.png)

* Agrega al carro de compras un producto, selecciona el carro de compras y luego presiona el botón [Pasar por caja]:

  ![demo3](img/demo3.png)

* Presiona el botón [Pasar por caja]:

  ![demo4](img/demo4.png)

* Selecciona método de envío y presiona el botón [Continuar]
  
  Debes asegurarte que tu dirección de envio sea en Chile.

  ![demo5](img/demo5.png)

* Selecciona método de pago con tarjetas de crédito o Redcompra, selecciona los "términos de servicio" y luego presiona el botón [Pedido con obligación de pago] y luego el botón [Pagar]

  ![demo6](img/demo6.png)

  ![demo6.1](img/demo6.1.png)

* Una vez presionado el botón para iniciar la compra, se mostrará la ventana de pago Webpay y deberás seguir el proceso de pago.

Para pruebas puedes usar los siguientes datos:  

* Número de tarjeta: `4051885600446623`
* Rut: `11.111.111-1`
* Cvv: `123`
  
![demo7](img/demo7.png)

![demo8](img/demo8.png)

Para pruebas puedes usar los siguientes datos:  

* Rut: `11.111.111-1`
* Clave: `123`

![demo9](img/demo9.png)

Puedes aceptar o rechazar la transacción

![demo10](img/demo10.png)

![demo11](img/demo11.png)

![demo12](img/demo12.png)
  
* Serás redirigido a Prestashop y podrás comprobar que el pago ha sido exitoso.

 ![demo13](img/demo13.png)

* Además si accedes al sitio de administración sección (Pedidos / Pediso) se podrá ver la orden creada y el detalle de los datos entregados por Webpay.

 ![order1](img/order1.png)

 ![order2](img/order2.png)
