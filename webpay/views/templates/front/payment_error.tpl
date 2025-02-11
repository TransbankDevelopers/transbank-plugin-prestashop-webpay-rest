{extends file='page.tpl'}
{assign var='current_step' value='payment'}

{block name="content"}

<p class="alert alert-danger">{$errorMessage}</p>
<div class="box order-confirmation">
    <p>
        <a href="{$link->getPageLink('order', true, NULL, "step=3")}" class="btn btn-primary">Reintentar pago</a>
    </p>
</div>

{/block}
