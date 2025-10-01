{extends file='page.tpl'}

{block name='page_title'}
  {$strings.title|escape:'html':'UTF-8'}
{/block}

{block name='page_content'}
  {if $errors}
    <ul class="alert alert-danger" role="alert">
      {foreach from=$errors item=e}<li>{$e|escape:'html':'UTF-8'}</li>{/foreach}
    </ul>
  {/if}

  {if $success}
    <ul class="alert alert-success" role="alert">
      {foreach from=$success item=s}<li>{$s|escape:'html':'UTF-8'}</li>{/foreach}
    </ul>
{/if}
    <img src="{$oneclick_image_url|escape:'html':'UTF-8'}" alt="Oneclick" class="mb-2" height="50" />
  {if !$cards|@count}
    <p>{$strings.no_cards|escape:'html':'UTF-8'}</p>
  {else}
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>{l s='Tipo' mod='webpay'}</th>
            <th>{l s='Terminada en' mod='webpay'}</th>
            <th>{l s='Creada' mod='webpay'}</th>
            <th>{l s='Acciones' mod='webpay'}</th>
          </tr>
        </thead>
        <tbody>
        {foreach from=$cards item=card}
          <tr>
            <td>{$card.card_type|escape:'html':'UTF-8'}</td>
            <td>{$card.card_number|escape:'html':'UTF-8'}</td>
            <td>{$card.created_at|escape:'html':'UTF-8'}</td>
            <td>
              <form method="post" action="{$list_url|escape:'html':'UTF-8'}" style="display:inline" onsubmit="return confirm('{l s='¿Eliminar esta tarjeta?' mod='webpay'}');">
                <input type="hidden" name="id_card" value="{$card.id_card|intval}">
                <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html':'UTF-8'}">
                <input type="submit" class="btn btn-outline-danger btn-sm" value="{$strings.delete|escape:'html':'UTF-8'}">
              </form>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
  {/if}

  <a class="btn btn-link" href="{$back_to_account_url|escape:'html':'UTF-8'}">{$strings.back|escape:'html':'UTF-8'}</a>
{/block}
