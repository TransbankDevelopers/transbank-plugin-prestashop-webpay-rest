{capture name=path}{l s='Pago a través de WebPay' mod='webpay'}{/capture}
<h2>{l s='Order summary' mod='webpay'}</h2>

{assign var='current_step' value='payment'}

<p class="alert alert-danger">Ha ocurrido un error con su pago.</p>
<div class="box order-confirmation">
    <p>
        <b>Respuesta de la Transaccion:</b> {$WEBPAY_RESULT_DESC}
        <br/><b>Código de la Transaccion:</b> {$WEBPAY_RESULT_CODE}
        <br/><b>Orden de Compra:</b> {$WEBPAY_VOUCHER_ORDENCOMPRA}
        <br/><b>Fecha de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_FECHA}
        <br/><b>Hora de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_HORA}
    </p>
</div>
