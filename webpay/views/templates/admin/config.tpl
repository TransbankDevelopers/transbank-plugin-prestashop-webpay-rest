<body onload="">
	<script>
		var REQUEST_PATH = '{Context::getContext()->link->getModuleLink('webpay', 'request')}';
	</script>
	<script src="../modules/webpay/views/js/request.js"> </script>
	<link href="../modules/webpay/views/css/config.css" rel="stylesheet">

	<!-- Tab links -->
	<div class="tbk_tab">
		<button class="tbk_tablinks" onclick="openTab(event, 'tab_webpayplus')">Webpay Plus</button>
		<button class="tbk_tablinks" onclick="openTab(event, 'tab_oneclick')">Oneclick</button>
		<button class="tbk_tablinks" onclick="openTab(event, 'tab_diagnostico')">Diagnóstico</button>
		<button class="tbk_tablinks" onclick="openTab(event, 'tab_log')">Logs</button>
		<button class="tbk_tablinks" onclick="openTab(event, 'tab_info')">PHP Info</button>
  	</div>

	<div id="tab_webpayplus" class="tbk_tabcontent">
	  {include file="$view_base/admin/webpay_config.tpl"}
	</div>
	<div id="tab_oneclick" class="tbk_tabcontent">
	  {include file="$view_base/admin/oneclick_config.tpl"}
	</div>
	<div id="tab_diagnostico" class="tbk_tabcontent">
		<div class="tbk_card">
			<div class="tbk_card_container">
				<form action="{$post_url|escape:'htmlall':'UTF-8'}" method="post">
                    {if isset($errors.merchantERR)}
                        <div class="error">
                            <p>{$errors.merchantERR|escape:'htmlall':'UTF-8'}</p>
                        </div>
                    {/if}
                    <label class="tbk_label" for="form_debug_active">{l s='Habilitar log detallado' mod='webpay'}</label>
					<input  class="tbk_input" type="checkbox" name="form_debug_active" value="1" {if $data_debug_active eq "1"}checked{/if} >
        
                    <div class="tbk_right">
                        <button class="tbk_button" type="submit" value="1" id="btn_common_update" name="btn_common_update" >{l s='Guardar Cambios' mod='webpay'}</button>
                    </div>
                </form>
			</div>
		</div>
		{include file="$view_base/admin/diag_info.tpl"}
	</div>
	<div id="tab_log" class="tbk_tabcontent">
		{include file="$view_base/admin/diag_logs.tpl"}
	</div>
	<div id="tab_info" class="tbk_tabcontent">
		{include file="$view_base/admin/diag_php_info.tpl"}
	</div>
	

	<script type="text/javascript">
		openTab(event, 'tab_webpayplus');
		function openTab(evt, cityName) {
			var i, tabcontent, tablinks;
			tabcontent = document.getElementsByClassName("tbk_tabcontent");
			for (i = 0; i < tabcontent.length; i++) {
				tabcontent[i].style.display = "none";
			}
			tablinks = document.getElementsByClassName("tbk_tablinks");
			for (i = 0; i < tablinks.length; i++) {
				tablinks[i].className = tablinks[i].className.replace(" active", "");
			}
			document.getElementById(cityName).style.display = "block";
			if (evt)
				evt.currentTarget.className += " active";
		}
	</script>

</body>
