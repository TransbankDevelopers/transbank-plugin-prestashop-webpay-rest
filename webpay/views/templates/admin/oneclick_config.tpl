<div class="tbk_card">
    <div class="tbk_logo">
        <img src="../modules/webpay/views/img/oneclick.png" height="50">
    </div>
    <div class="tbk_card_container">
        <div class="tbk_row">
            <div class="tbk_column">
                <div class="tbk_card_title">Webpay Oneclick Mall</div><br>
                Permite a tus usuarios inscribir su tarjeta en su cuenta dentro de tu sitio, para que puedan comprar con un solo click.<br>
                Este plugin funciona en modalidad Mall, significa que tienes un código de comercio mall, un código de comercio tienda y un apikey.<br>
                Cuando un usuario inscribe su tarjeta, la asocia al código de comercio Mall, pero cuando se realiza una transacción, se pagará al código de comercio “tienda” y no al “mall”.<br>
            </div>
            <div class="tbk_column tbk_column_line_left">
                <form action="{$post_url|escape:'htmlall':'UTF-8'}" method="post">
                    {if isset($errors.merchantERR)}
                        <div class="error">
                            <p>{$errors.merchantERR|escape:'htmlall':'UTF-8'}</p>
                        </div>
                    {/if}
                    <label class="tbk_label" for="form_oneclick_environment">{l s='Ambiente' mod='webpay'}</label>
                    <select class="tbk_select" id="form_oneclick_environment" name="form_oneclick_environment" onChange="oneclickEnviromentChange()" default="TEST">
                        <option value="TEST" {if $data_oneclick_environment eq "TEST"}selected{/if}>Integración</option>
                        <option value="LIVE" {if $data_oneclick_environment eq "LIVE"}selected{/if}>Producción</option>
                    </select>
        
                    <label class="tbk_label" for="form_oneclick_mall_commerce_code">{l s='Código de Comercio Mall' mod='webpay'}</label>
                    <input class="tbk_input" type="text" id="form_oneclick_mall_commerce_code" name="form_oneclick_mall_commerce_code" value="{$data_oneclick_mall_commerce_code|escape:'htmlall':'UTF-8'}" placeholder="Ejemplo: 597055555541">
        
                    <label class="tbk_label" for="form_oneclick_child_commerce_code">{l s='Código de Comercio Tienda' mod='webpay'}</label>
                    <input class="tbk_input" type="text" id="form_oneclick_child_commerce_code" name="form_oneclick_child_commerce_code" value="{$data_oneclick_child_commerce_code|escape:'htmlall':'UTF-8'}" placeholder="Ejemplo: 597055555542">
        
                    <label class="tbk_label" for="form_oneclick_api_key">{l s='API Key (llave secreta)' mod='webpay'}</label>
                    <input class="tbk_input" type="text" id="form_oneclick_api_key" name="form_oneclick_api_key" value="{$data_oneclick_apikey|escape:'htmlall':'UTF-8'}" placeholder="Ejemplo: 579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C">
        
                    <label class="tbk_label" for="form_oneclick_order_after_payment">{l s='Estado Pago Aceptado' mod='webpay'}</label>
                    <select class="tbk_select" id="form_oneclick_order_after_payment" name="form_oneclick_order_after_payment">
                        <option value="{$paymentAcceptedStatusId}" {if $data_oneclick_order_after_payment eq $paymentAcceptedStatusId}selected{/if}>{l s='Pago aceptado' mod='webpay'}</option>
                        <option value="{$preparationStatusId}" {if $data_oneclick_order_after_payment eq $preparationStatusId}selected{/if}>{l s='Preparación en curso' mod='webpay'}</option>
                    </select>
        
                    <div class="tbk_right">
                        <button class="tbk_button" type="submit" value="1" id="btn_oneclick_update" name="btn_oneclick_update" >{l s='Guardar Cambios' mod='webpay'}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="tbk_card">
    <div class="tbk_card_container">
        <div class="tbk_row">
            <div class="tbk_column tbk_column_line">
                <div class="tbk_card_title">¿Quieres operar en producción?</div><br>
                Para operar en el ambiente productivo con dinero real debes tener tu código de comercio Mall, código de comercio Tienda y tu API Key (llave secreta).<br><br> 
                <strong>Código de Comercio</strong><br>
                Si no lo tienes puedes solicitarlo usando <a class="tbk_link" href="https://contrata.transbankdevelopers.cl/Oneclick/?wpcommerce=&wpenv=&utm_source=woocommerce_plugin&utm_medium=banner&utm_campaign=contrata">este formulario</a>.<br><br> 
                <strong>Tu API Key: Proceso de validación</strong><br>
                Para obtenerla debes comenzar el 
                <a class="tbk_link" target="_blank" href="https://form.typeform.com/to/fZqOJyFZ?typeform-medium=embed-snippet&amp;from=prestashop_oneclick" style="margin-top: 5px; display: inline-block;clear: both" data-mode="popup" class="typeform-share link button-primary" data-size="100" data-submit-close-delay="25">proceso de validación</a>.
                <br><br> 
                Si tienes dudas puedes revisar nuestras las 
                <a class="tbk_link" target="_blank" href="https://transbankdevelopers.cl/documentacion/como_empezar#puesta-en-produccion">instrucciones detalladas de cómo pasar a producción</a>.
            </div>
            <div class="tbk_column">
                <div class="tbk_card_title">Credenciales de Prueba</div><br>
                En el ambiente de integración debes probar usando tarjetas de crédito y débito de prueba.
                <a class="tbk_link" target="_blank" href="https://transbankdevelopers.cl/documentacion/como_empezar#tarjetas-de-prueba">Encuentra las tarjeta de prueba aquí </a>
                <br><br>
                Después de seleccionar el método de pago (en una compra de prueba), llegarás a una página de un banco de prueba.<br> 
                Debes ingresar estas credenciales:<br>
                <strong>Rut: 11.111.111-1</strong><br>
                <strong>Clave: 123</strong>
            </div>
        </div>
    </div>
</div>
  


<script type="text/javascript">
    function oneclickEnviromentChange(){
        if (document.getElementById('form_oneclick_environment').value == 'LIVE'){
            document.getElementById('form_oneclick_mall_commerce_code').value = "";
            document.getElementById('form_oneclick_child_commerce_code').value = "";
            document.getElementById('form_oneclick_api_key').value = "";
        }
        else{
            document.getElementById('form_oneclick_mall_commerce_code').value = "{$data_oneclick_mall_commerce_code_default|escape:'htmlall':'UTF-8'}";
            document.getElementById('form_oneclick_child_commerce_code').value = "{$data_oneclick_child_commerce_code_default|escape:'htmlall':'UTF-8'}";
            document.getElementById('form_oneclick_api_key').value = "{$data_oneclick_apikey_default|escape:'htmlall':'UTF-8'}";
        }
    }
</script>