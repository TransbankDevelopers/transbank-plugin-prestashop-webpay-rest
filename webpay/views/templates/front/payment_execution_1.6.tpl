
{capture name=path}{l s='Pago a través de WebPay' mod='webpay'}{/capture}
<h2>{l s='Order summary' mod='webpay'}</h2>

{assign var='current_step' value='payment'}

<form method="post" action="{$url}">
    {if ({$token_ws} == '')}
        <p class="alert alert-danger">Ocurrio un error al intentar conectar con WebPay o los datos de conexion son incorrectos.</p>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}
            </a>
        </p>
    {else}
        <input type="hidden" name="token_ws" value="{$token_ws}" />
        <div class="box cheque-box">
            <h3 class="page-subheading">Pago por WebPay</h3>
            <p>Se realizara la compra a traves de WebPay por un total de ${$amount}</p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}
            </a>
            <button type="submit" class="button btn btn-default button-medium">
                <span>Pagar<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    {/if}
</form>
