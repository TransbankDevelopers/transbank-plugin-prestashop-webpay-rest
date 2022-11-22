<div class="tbk_card">
    <div class="tbk_card_container">
        <h2 class="">{l s='Información del plugin y el ambiente' mod='webpay'}</h2>
        <table class="table table-striped">
            <tr>
                <td><div title="Nombre del E-commerce instalado en el servidor" class="label label-info">?</div> <strong>{l s='Software E-commerce' mod='webpay'}: </strong></td>
                <td class="tbk_table_td">{$ecommerce}</td>
            </tr>
            <tr>
                <td><div title="Versión de {$ecommerce} instalada en el servidor" class="label label-info">?</div> <strong>{l s='Version E-commerce' mod='webpay'}: </strong></td>
                <td class="tbk_table_td">{$ecommerce_version}</td>
            </tr>
            <tr>
                <td><div title="Versión del plugin Webpay para {$ecommerce} instalada actualmente" class="label label-info">?</div> <strong>{l s='Versión del plugin instalada' mod='webpay'}: </strong></td>
                <td class="tbk_table_td">{$current_plugin_version}</td>
            </tr>
            <tr>
                <td><div title="Última versión del plugin Webpay para {$ecommerce} disponible" class="label label-info">?</div> <strong>{l s='Última versión del plugin disponible' mod='webpay'}: </strong></td>
                <td class="tbk_table_td">{$last_plugin_version}</td>
            </tr>
        </table>
    
        <br>
    
        <h2 class="">{l s='php_extensions_status' mod='webpay'}</h2>
        <h4 class="tbk_table_title">{l s='Información Principal' mod='webpay'}</h4>
        <table class="table table-striped">
            <tr>
                <td><div title="Descripción del Servidor Web instalado" class="label label-info">?</div> <strong>{l s='Software Servidor' mod='webpay'}: </strong></td>
                <td class="tbk_table_td">{$server_version}</td>
            </tr>
        </table>
    
        <h4 class="tbk_table_title">{l s='PHP' mod='webpay'}</h4>
        <table class="table table-striped">
            <tr>
                <td><div title="Informa si la versión de PHP instalada en el servidor es compatible con el plugin de Webpay" class="label label-info">?</div> <strong>{l s='Estado de PHP' mod='webpay'}</strong></td>
                <td class="tbk_table_td"><span  class="label {if $php_status eq 'OK'}label-success2{else}label-warning{/if}">{$php_status}</td>
            </tr>
            <tr>
                <td><div title="Versión de PHP instalada en el servidor" class="label label-info">?</div> <strong>{l s='Versión instalada: ' mod='webpay'}: </strong></td>
                <td class="tbk_table_td">{$php_version}</td>
            </tr>
        </table>
    
        <h4 class="tbk_table_title">{l s='Extensiones PHP requeridas' mod='webpay'}</h4>
        <table class="table table-responsive table-striped">
            <tr>
                <th>{l s='Extensión' mod='webpay'}</th>
                <th>{l s='Estado' mod='webpay'}</th>
                <th class="tbk_table_td">{l s='Versión' mod='webpay'}</th>
            </tr>
            <tr>
                <td style="font-weight:bold">{l s='openssl' mod='webpay'}</td>
                <td> <span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$openssl_status}</span></td>
                <td class="tbk_table_td">{$openssl_version}</td>
            </tr>
            <tr>
                <td style="font-weight:bold">{l s='SimpleXml' mod='webpay'}</td>
    
                <td> <span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$SimpleXML_status}</span></td>
                <td class="tbk_table_td">{$SimpleXML_version}</td>
            </tr>
            <tr>
                <td style="font-weight:bold">{l s='dom' mod='webpay'}</td>
                <td><span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$dom_status}</span></td>
                <td class="tbk_table_td">{$dom_version}</td>
            </tr>
        </table>
    
        <br>
        <h2 class="">{l s='Validación Transacción' mod='webpay'}</h2>
        <h4 class="tbk_table_title">{l s='Petición a Transbank' mod='webpay'}</h4>
        <table class="table table-striped">
            <tr>
                <td class="tbk_table_td"> <button class="check_conn btn btn-sm btn-primary">Verificar Conexión</button>  </td>
            </tr>
        </table>
        <hr>
        <h4 class="tbk_table_title">{l s='Respuesta de Transbank' mod='webpay'}</h4>
        <table class="table table-striped">
            <tr id="row_response_status" style="display:none">
                <td><div title="Informa el estado de la comunicación con Transbank mediante método init_transaction" class="label label-info">?</div> <strong>{l s='status' mod='webpay'}: </strong></td>
                <td><span class="status-label label" style="display:none"></span></td>
            </tr>
            <tr id="row_response_url" style="display:none">
                <td><div title="URL entregada por Transbank para realizar la transacción" class="label label-info">?</div> <strong>{l s='URL' mod='webpay'}: </strong></td>
                <td class="tbk_table_trans content_url"></td>
            </tr>
            <tr id="row_response_token" style="display:none">
                <td><div title="Token entregada por Transbank para realizar la transacción" class="label label-info">?</div> <strong>{l s='Token' mod='webpay'}: </strong></td>
                <td class="tbk_table_trans content_token"></td>
            </tr>
            <tr id="row_error_message" style="display:none">
                <td><div title="Mensaje de error devuelto por Transbank al fallar init_transaction" class="label label-info">?</div> <strong>{l s='Error' mod='webpay'}: </strong></td>
                <td class="tbk_table_trans error_content"></td>
            </tr>
            <tr id="row_error_detail" style="display:none">
                <td><div title="Detalle del error devuelto por Transbank al fallar init_transaction" class="label label-info">?</div> <strong>{l s='Detalle' mod='webpay'}: </strong></td>
                <td class="tbk_table_trans error_detail_content"></td>
            </tr>
    
        </table>
    </div>
    
</div>