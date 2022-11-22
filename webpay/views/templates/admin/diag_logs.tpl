<!-- REGISTROS -->
<div class="tbk_card">
    <div class="tbk_card_container">
        <div style="display: none;">
            <h2>{l s='Configuración' mod='webpay'}</h2>
            <table class="table table-striped">
                <tr>
                    <td><div title="Al activar esta opción se habilita que se guarden los datos de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Activar Registro:" mod='webpay'} </strong></td>
                    <td class="tbk_table_td">
                        {if $lockfile}
                            <input type="checkbox" id="action_check" name="action_check" checked data-size="small" value="activate">
                        {else}
                            <input type="checkbox" id="action_check" name="action_check" data-size="small" state="false">
                        {/if}
                    </td>
                </tr>
            </table>
            <table class="table table-striped">
                <tr>
                    <td><div title="Cantidad de días que se conservan los datos de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Cantidad de Dias a Registrar" mod='webpay'}: </strong></td>
                    <td class="tbk_table_td"><input id="days" name="days" type="number" min="1" max="30" value="{$log_days}">{l s=" días"}</td>
                </tr>
                <tr>
                    <td><div title="Peso máximo (en Megabytes) de cada archivo que guarda los datos de las compras mediante Webpay" class="label label-info">?</div> <strong>{l s="Peso máximo de Registros" mod='webpay'}:  </strong></td>
                    <td class="tbk_table_td"><select style="width: 100px; display: initial;" id="size" name="size">
                            {for $c=1 to 10}
                                <option value="{$c}" {if $c eq $log_size}selected{/if}>{$c}</option>
                            {/for}
                        </select> {l s="Mb" mod='webpay'}</td>
                </tr>
            </table>
            <div class="btn btn-primary"> {l s='Actualizar Parametros' mod='webpay'}</div>
        </div>
        <h2 class="">{l s='Información de Registros' mod='webpay'}</h2>
    
        <table class="table table-striped">
            <tr style="display: none;">
                <td><div title="Informa si actualmente se guarda la información de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Estado de Registros" mod='webpay'}: </strong></td>
                <td class="tbk_table_td"><span id="action_txt" class="label label-success2">{l s='Registro-activado' mod='webpay' }</span><br> </td>
            </tr>
            <tr>
                <td><div title="Carpeta en el servidor en donde se guardan los archivos con la informacón de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Directorio de registros" mod='webpay'}: </strong></td>
                <td class="tbk_table_td td_log_dir">{$log_dir}</td>
            </tr>
            <tr>
                <td><div title="Cantidad de archivos que guardan la información de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Cantidad de Registros en Directorio" mod='webpay'}: </strong></td>
                <td class="tbk_table_td td_log_count">{$logs_count} </td>
            </tr>
            <tr>
                <td><div title="Lista los archivos archivos que guardan la información de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Listado de Registros Disponibles" mod='webpay'}: </strong></td>
                <td class="tbk_table_td td_log_files">
                    <ul style="font-size:0.8em;">
                        {foreach from=$logs_list item=index}
                            <li>{$index}</li>
                        {/foreach}
                    </ul>
                </td>
            </tr>
        </table>
    
        <h2 class="">{l s='Últimos Registros' mod='webpay'}</h2>
    
        <table class="table table-striped">
            <tr>
                <td><div title="Nombre del útimo archivo de registro creado" class="label label-info">?</div> <strong>{l s="Último Documento" mod='webpay'}: </strong></td>
                <td class="tbk_table_td td_log_last_file">{$log_file} </td>
            </tr>
            <tr>
                <td><div title="Peso del último archivo de registro creado" class="label label-info">?</div> <strong>{l s="Peso del Documento" mod='webpay'}: </strong></td>
                <td class="tbk_table_td td_log_file_weight">{$log_weight}</td>
            </tr>
            <tr>
                <td><div title="Cantidad de líneas que posee el último archivo de registro creado" class="label label-info">?</div> <strong>{l s="Cantidad de Líneas" mod='webpay'}: </strong></td>
                <td class="tbk_table_td td_log_regs_lines">{$log_regs_lines} </td>
            </tr>
        </table>
        <br>
        <pre>
            <span class="log_content" style="font-size: 10px; font-family:monospace; display: block; background: white;width: fit-content;" >{$logs}</span>
        </pre>
    </div>
    

</div>

<script type="text/javascript">
    
    function updateConfigLogs(){
        $.ajax({
            type:'POST',
            url:'../modules/webpay/libwebpay/UpdateConfigLog.php',
            dataType:'json',
            data: {
                status: document.getElementById("action_check").checked,
                max_days: $("#days").val(),
                max_weight: $("#size").val()
            },
            success:function(response) {
            }
        });
    }

</script>