{extends file='page.tpl'}
{assign var='current_step' value='payment'}

{block name="content"}

<p class="alert alert-danger">Lamentamos informarle que ha ocurrido un error con su pago. </p>
<div class="box order-confirmation">
    <p>
        {foreach from=$data item=item}
            <b>{$item.label}:</b> {$item.value}<br/>
        {/foreach}
    </p>
    <p>
        <a href="{$link->getPageLink('order', true, NULL, "step=3")}" class="btn btn-primary">Reintentar pago</a>
    </p>
</div>

{/block}

