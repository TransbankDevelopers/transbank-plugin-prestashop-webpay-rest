<div class="modal" id="tb_modal">
    <div class="modal-dialog" >
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <ul class="nav nav-tabs">
                    <li class="active" > <a data-toggle="tab" href="#info" class="tbk_tabs">{l s='Información' mod='webpay'}</a></li>
                    <li> <a data-toggle="tab" href="#php_info" class="tbk_tabs">{l s='PHP info' mod='webpay'}</a></li>
                    <li> <a data-toggle="tab" href="#logs" class="tbk_tabs">{l s='logs' mod='webpay'}</a></li>
                </ul>
            </div>
            <div class="modal-body">
                <div class="tab-content">
                    {include file="$view_base/admin/diag_info.tpl"}
                    {include file="$view_base/admin/diag_php_info.tpl"}
                    {include file="$view_base/admin/diag_logs.tpl"}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

