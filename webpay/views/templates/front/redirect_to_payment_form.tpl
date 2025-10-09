{extends file='page.tpl'}
{assign var='current_step' value='payment'}

{block name="content"}

{assign var='redirectType' value=$redirectType|default:''}
{assign var='payment_method' value=($redirectType|strstr:'oneclick') ? 'Webpay Oneclick' : 'Webpay Plus'}

    {if !isset($token_ws) || $token_ws == '' || !isset($redirectType) || $redirectType == ''}
        <div class="alert alert-danger">
            {l s='Ocurrió un error al procesar la transacción. Por favor reintente la operación.' mod='webpay'}
        </div>
        <div class="cart_navigation clearfix text-center">
            {if $redirectType == 'oneclick'}
                <a href="{$link->getModuleLink('webpay', 'oneclickcards')|escape:'html'}" class="btn btn-primary">
                    <i class="icon-chevron-left"></i> {l s='Volver a mis tarjetas' mod='webpay'}
                </a>
            {else}
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="btn btn-primary">
                    <i class="icon-chevron-left"></i> {l s='Otros métodos de pago' mod='webpay'}
                </a>
            {/if}
        </div>
    {else}
        <form method="post" id="to-payment-form" name="toPaymentForm" action="{$url|escape:'html'}">
            {if str_contains($redirectType, 'oneclick')}
                <input type="hidden" name="TBK_TOKEN" value="{$token_ws|escape:'html'}" />
            {else}
                <input type="hidden" name="token_ws" value="{$token_ws|escape:'html'}" />
            {/if}

            <div class="box cheque-box">
                <h3 class="page-subheading">{l s='Pago con' mod='webpay'} {$payment_method|escape:'html'}</h3>
                <p>{l s='Será redirigido al formulario para completar la transacción.' mod='webpay'}</p>
            </div>

            <div class="cart_navigation clearfix text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <span>{l s='Pagar' mod='webpay'} <i class="icon-chevron-right"></i></span>
                </button>
            </div>

            <div class="cart_navigation clearfix text-center mt-3 mb-2">
                {if $redirectType == 'oneclick-cards'}
                    <a href="{$link->getModuleLink('webpay', 'oneclickcards')|escape:'html'}" class="btn btn-primary btn-sm">
                        <i class="material-icons">arrow_back</i> {l s='Volver a mis tarjetas' mod='webpay'}
                    </a>
                {else}
                    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="btn btn-primary btn-sm">
                        <i class="material-icons">arrow_back</i> {l s='Elegir otros métodos de pago' mod='webpay'}
                    </a>
                {/if}
            </div>

            <script>
                document.getElementById('to-payment-form').submit();
            </script>
        </form>
    {/if}

{/block}
