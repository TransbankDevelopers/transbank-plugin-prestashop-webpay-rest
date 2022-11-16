<form action="{$post_url|escape:'htmlall':'UTF-8'}" method="post" style="clear: both; margin-top: 10px; max-width: 400px">

    <h2 class="">{l s='Configuración' mod='webpay'}</h2>
    {if isset($errors.merchantERR)}
        <div class="error">
            <p>{$errors.merchantERR|escape:'htmlall':'UTF-8'}</p>
        </div>
    {/if}

    <label for="form_webpay_commerce_code">{l s='Código de comercio' mod='webpay'}</label>
    <div class="margin-form"><input type="text" size="90" id="form_webpay_commerce_code" name="form_webpay_commerce_code" value="{$data_storeid|escape:'htmlall':'UTF-8'}"/></div>
    <br/>
    <label for="apiKey">{l s='API Key (llave secreta)' mod='webpay'}</label>
    <div class="margin-form"><input type="text" size="90" id="form_webpay_api_key" name="form_webpay_api_key" value="{$data_apikeysecret|escape:'htmlall':'UTF-8'}"/></div>

    <br/>
    <label for="form_webpay_order_after_payment">{l s='Estado pago aceptado' mod='webpay'}</label>
    <div class="margin-form">
        <select name="form_webpay_order_after_payment">
            <option value="{$paymentAcceptedStatusId}" {if $data_order_after_payment eq $paymentAcceptedStatusId}selected{/if}>{l s='Pago aceptado' mod='webpay'}</option>
            <option value="{$preparationStatusId}" {if $data_order_after_payment eq $preparationStatusId}selected{/if}>{l s='Preparación en curso' mod='webpay'}</option>
        </select>
    </div>

    <br/>
    <label for="form_environment">{l s='Ambiente' mod='webpay'}</label>
    <div class="margin-form">
        <select name="form_environment" onChange="
                if(this.options[0].selected){
                    cargaDatosWebpayIntegracion();
                }else if(this.options[1].selected){
                    cargaDatosWebpayProduccion();
                }" default="TEST">
            <option value="TEST" {if $data_environment eq "TEST"}selected{/if}>Integración</option>
            <option value="LIVE" {if $data_environment eq "LIVE"}selected{/if}>Producción</option>
        </select>
    </div>

    <br/>
    <label for="form_debug_active">{l s='Habilitar log detallado' mod='webpay'}</label>
    <div class="margin-form">
        <input type="checkbox" name="form_debug_active" value="1" {if $data_debug_active eq "1"}checked{/if} >
    </div>

    <br/>

    <div align="right">
        <button type="submit" value="1" id="webpay_updateSettings" name="webpay_updateSettings" class="btn btn-info pull-right">
            <i class="process-icon-save" value="{l s='Save Settings' mod='webpay'}"></i> Guardar
        </button>
    </div>

</form>

<script type="text/javascript">

    function cargaDatosWebpayIntegracion(){
        document.getElementById('form_webpay_commerce_code').value = "{$data_storeid_init|escape:'htmlall':'UTF-8'}";
        document.getElementById('form_webpay_api_key').value = "{$data_apikeysecret_init|escape:'htmlall':'UTF-8'}";
    }

    function cargaDatosWebpayProduccion(){
        document.getElementById('form_webpay_api_key').value = '';
        document.getElementById('form_webpay_commerce_code').value = '';
    }

</script>