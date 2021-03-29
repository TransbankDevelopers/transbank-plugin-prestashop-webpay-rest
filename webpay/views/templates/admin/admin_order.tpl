<div id="webpay_details" class="{if version_compare(_PS_VERSION_, '1.7.7.0', '>=')}col card mt-2{else}panel{/if}">
  <div class="{if version_compare(_PS_VERSION_, '1.7.7.0', '>=')}card-header{else}panel-heading{/if}">
    <img src="{$webpay._path|escape:'htmlall':'UTF-8'}logo.gif" {if version_compare(_PS_VERSION_, '1.7.7.0', '>=')}class="material-icons"{/if}/>
    {$webpay.title|escape:'htmlall':'UTF-8'}
  </div>
  <dl class="{if version_compare(_PS_VERSION_, '1.7.7.0', '>=')}card-body{else}dl-horizontal{/if}">
    {foreach from=$webpay.details item=details}
      <dt>{$details.desc|escape:'htmlall':'UTF-8'}:</dt>
      <dd>{$details.data|escape:'htmlall':'UTF-8'}</dd>
    {/foreach}
  </dl>
</div>
