
{capture name=path}{l s='Pago a través de Webpay' mod='webpay'}{/capture}
<h2>{l s='Order summary' mod='webpay'}</h2>

{assign var='current_step' value='payment'}

<form method="post" action="{$url}" name="webpayForm" id="webpay-form">
    {if ({$token_ws} == '')}
        <p class="alert alert-danger">Ocurrió un error al intentar conectar con WebPay o los datos de conexión son incorrectos.</p>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}
            </a>
        </p>
    {else}
        <input type="hidden" name="token_ws" value="{$token_ws}" />
        <div class="box cheque-box">
            <h3 class="page-subheading">Pago por WebPay</h3>
            <p>Se realizará la compra a través de WebPay por un total de ${$amount}</p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <button type="submit" class="button btn btn-default button-medium">
                <span>Pagar!<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
        <p>
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}
            </a>
        </p>
        <script>
            document.getElementById('webpay-form').submit();
        </script>
    {/if}
</form>

