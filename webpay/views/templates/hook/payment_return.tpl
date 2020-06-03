
{if ($WEBPAY_RESULT_CODE == 0)}
    <p class="alert alert-success">{l s='Su pedido está completo.' mod='webpay'}</p>
    <div class="box order-confirmation">
        <h3 class="page-subheading">Detalles del pago:</h3>
        <p>
            <b>Respuesta de la Transaccion:</b> {$WEBPAY_RESULT_DESC}
            <br/><b>Código de la Transaccion:</b> {$WEBPAY_RESULT_CODE}
            <br/><b>Tarjeta de credito:</b> **********{$WEBPAY_VOUCHER_NROTARJETA}
            <br/><b>Fecha de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_FECHA}
            <br/><b>Hora de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_HORA}
            <br/><b>Monto Compra:</b> {$WEBPAY_VOUCHER_TOTALPAGO}
            <br/><b>Orden de Compra:</b> {$WEBPAY_VOUCHER_ORDENCOMPRA}
            <br/><b>Codigo de Autorizacion:</b> {$WEBPAY_VOUCHER_AUTCODE}
            <br/><b>Tipo de Pago:</b> {$WEBPAY_VOUCHER_TIPOPAGO}
            <br/><b>Tipo de Cuotas:</b> {$WEBPAY_VOUCHER_TIPOCUOTAS}
            <br/><b>Numero de cuotas:</b> {$WEBPAY_VOUCHER_NROCUOTAS}
        </p>
    </div>
{else}
    <p class="alert alert-danger">Ha ocurrido un error con su pago.</p>
    <div class="box order-confirmation">
        <h3 class="page-subheading">Detalles del pago:</h3>
        <p>
            <b>Respuesta de la Transaccion:</b> {$WEBPAY_RESULT_DESC}
            <br/><b>Código de la Transaccion:</b> {$WEBPAY_RESULT_CODE}
            <br/><b>Orden de Compra:</b> {$WEBPAY_VOUCHER_ORDENCOMPRA}
            <br/><b>Fecha de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_FECHA}
            <br/><b>Hora de Transaccion:</b> {$WEBPAY_VOUCHER_TXDATE_HORA}
        </p>
    </div>
{/if}
