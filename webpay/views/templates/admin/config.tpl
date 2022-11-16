
<body onload="">
<div>
	<script>
		var REQUEST_PATH = '{Context::getContext()->link->getModuleLink('webpay', 'request')}';
	</script>
	<link href="../modules/webpay/views/css/bootstrap-switch.css" rel="stylesheet">
	<link href="../modules/webpay/views/css/tbk.css" rel="stylesheet">
	<script src="../modules/webpay/views/js/request.js"> </script>
	<script src="https://unpkg.com/bootstrap-switch"></script>

	<h2>{l s='Pago electrónico con Tarjetas de Crédito o Redcompra a través de Webpay Plus REST' mod='webpay'}</h2>
	<button  class ="btn btn-primary" data-toggle="modal" data-target="#tb_modal">Realizar diagnóstico</button>
	<hr>

	{include file="$view_base/admin/webpay_config.tpl"}
	{include file="$view_base/admin/diagnostico.tpl"}
</div>
</body>
