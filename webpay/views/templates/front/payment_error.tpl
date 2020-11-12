{extends file='page.tpl'}
{assign var='current_step' value='payment'}

{block name="content"}

<p class="alert alert-danger">Lamentamos informarle que ha ocurrido un error con su pago. </p>
<div class="box order-confirmation">
    <p>
        <b>Respuesta de la Transacción:</b> {$WEBPAY_RESULT_DESC}
        {if isset($WEBPAY_RESULT_CODE)}
            <br/><b>Código de la Transacción:</b> {$WEBPAY_RESULT_CODE}
        {/if}
        {if isset($WEBPAY_VOUCHER_ORDENCOMPRA)}
            <br/><b>Orden de Compra:</b> {$WEBPAY_VOUCHER_ORDENCOMPRA}
        {/if}
        {if isset($WEBPAY_VOUCHER_TXDATE_FECHA)}
            <br/><b>Fecha de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_FECHA}
        {/if}
        {if isset($WEBPAY_VOUCHER_TXDATE_HORA)}
            <br/><b>Hora de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_HORA}
        {/if}
    </p>
    <p>
        <a href="{$link->getPageLink('order', true, NULL, "step=3")}" class="btn btn-primary">Reintentar pago</a>
    </p>
</div>

{/block}
