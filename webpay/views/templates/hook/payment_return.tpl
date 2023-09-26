<h3 class="page-subheading">Detalles del pago:</h3>
<p class="alert alert-success">Su pedido está pagado</p>
{foreach from=$detail->data item=item}
      <b>{$item.label}:</b> {$item.value}<br/>
{/foreach}

