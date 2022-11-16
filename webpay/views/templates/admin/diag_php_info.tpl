<!-- PHP INFO -->
<div id="php_info" class="tab-pane fade">
    <h2 class="">{l s='Informe PHP info ' mod='webpay'}</h2>
    <form method="post" action="../modules/webpay/libwebpay/CreatePdf.php" target="_blank">
        <input type="hidden" name="environment" value="{$data_environment}">
        <input type="hidden" name="storeID" value="{$data_storeid}">
        <input type="hidden" name="apiKeySecret" value="{$data_apikeysecret}">
        <input type="hidden" name="document" value="php_info">
        <button type = "submit">
            <i class="icon-file-text" value="{l s='Crear PHP info' mod='webpay'}"></i> Crear PHP info
        </button>
    </form>

    <hr style="border-width: 2px">

    <br>
    <h2 class="">{l s='php_info' mod='webpay'}</h2>
    <span style="font-size: 10px; font-family:monospace; display: block; background: white;overflow: hidden; width: 90%;" >{$php_info}</span><br>
</div>