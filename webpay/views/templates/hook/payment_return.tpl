
{if ($WEBPAY_RESULT_CODE == 0)}
    <p class="alert alert-success">{l s='Su pedido está completo' mod='webpay'}</p>
    <div class="box order-confirmation">
        <h3 class="page-subheading">Detalles del pago:</h3>
        <p>
            <b>Respuesta de la transacción:</b> {$WEBPAY_RESULT_DESC}
            <br/><b>Número de tarjeta:</b> **** **** **** {$WEBPAY_VOUCHER_NROTARJETA}
            <br/><b>Fecha de transacción:</b> {$WEBPAY_VOUCHER_TXDATE_FECHA}
            <br/><b>Hora de transacción:</b> {$WEBPAY_VOUCHER_TXDATE_HORA}
            <br/><b>Monto compra: </b> ${$WEBPAY_VOUCHER_TOTALPAGO}
            <br/><b>Orden de compra:</b> {$WEBPAY_VOUCHER_ORDENCOMPRA}
            <br/><b>Código de autorización:</b> {$WEBPAY_VOUCHER_AUTCODE}
            <br/><b>Tipo de pago:</b> {$WEBPAY_VOUCHER_TIPOPAGO}
            <br/><b>Tipo de cuotas:</b> {$WEBPAY_VOUCHER_TIPOCUOTAS}
            {if ($WEBPAY_VOUCHER_NROCUOTAS != 0)}
                <br/><b>Número de cuotas:</b> {$WEBPAY_VOUCHER_NROCUOTAS}
                <br/><b>Monto de cada cuota:</b> ${$WEBPAY_VOUCHER_AMOUNT_CUOTAS}
            {/if}
        </p>
    </div>
{else}
    <p class="alert alert-danger">Ha ocurrido un error con su pago.</p>
    <div class="box order-confirmation">
        <h3 class="page-subheading">Detalles del pago:</h3>
        <p>
            <b>Respuesta de la transacción:</b> {$WEBPAY_RESULT_DESC}
            <br/><b>Código de respuesta:</b> {$WEBPAY_RESULT_CODE}
            <br/><b>Orden de compra:</b> {$WEBPAY_VOUCHER_ORDENCOMPRA}
            <br/><b>Fecha de transacción:</b> {$WEBPAY_VOUCHER_TXDATE_FECHA}
            <br/><b>Hora de transacción:</b> {$WEBPAY_VOUCHER_TXDATE_HORA}
        </p>
    </div>
{/if}
