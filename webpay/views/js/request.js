$( document ).ready(function() {

	$(".check_conn").click(function(){

		$(".btn_status").hide();
		$(".check_conn").text("Verificando ...");
		$("#row_response_url").hide();
		$("#row_response_token").hide();
		$("#row_error_message").hide();
		$("#row_error_detail").hide();
		$("#row_response_status").hide();
		$(".error-content").empty();
		$(".error_detail_content").empty();
		$(".content_url").empty();
		$(".content_token").empty();

		var data = {
			"type" : "checkInit",
			"MODE" : $("input[name='ambient']").val(),
			"C_CODE" : $("input[name='storeID']").val(),
			"PUBLIC_CERT" : $("input[name='certificate']").val(),
			"PRIVATE_KEY" : $("input[name='secretCode']").val(),
			"WEBPAY_CERT" : $("input[name='certificateTransbank']").val()
		};

		$.post('../modules/webpay/controllers/front/request.php',data,function(response){

			$(".status-label").show();
			$(".check_conn").text("Verificar Conexión");
			$("#row_response_status").show();

			if(response.success)
			{
				$(".status-label").removeClass("label-success2");
				$(".status-label").removeClass("label-danger2");

				console.log(response.msg.status.string);

				if(response.msg.status.string == "OK")
				{
					$(".status-label").text("OK");
					$(".status-label").addClass("label-success2");
					$(".content_url").append(response.msg.response.url);
					$(".content_token").append('<pre>'+response.msg.response.token_ws+'</pre>');
					$("#row_response_url").show();
					$("#row_response_token").show();
				}
				else
				{
					$(".status-label").addClass("label-danger2");
					$(".status-label").text("ERROR");
					$("#row_error_message").show();
					$("#row_error_detail").show();
					$(".error-content").append(response.msg.response.error);
					$(".error_detail_content").append('<pre><code>'+response.msg.response.detail+'</code></pre>');
				}

				$(".td_log_dir").empty();
					$(".td_log_count").empty();
					$(".td_log_files").empty();
					$(".td_log_last_file").empty();
					$(".td_log_file_weight").empty();
					$(".td_log_regs_lines").empty();
					$(".log_content").empty();

					$(".td_log_dir").append(response.log.log_dir);
					$(".td_log_count").append(response.log.logs_count.log_count);

					var ul_content = '<ul style="font-size:0.8em;">';

					response.log.logs_list.map(function(log_list){

						ul_content += '<li>'+log_list+'</li>';

					});

					ul_content += '</ul>';

					$(".td_log_files").append(ul_content);
					$(".td_log_last_file").append(response.log.last_log.log_file);
					$(".td_log_file_weight").append(response.log.last_log.log_weight);
					$(".td_log_regs_lines").append(response.log.last_log.log_regs_lines);
					$(".log_content").append(response.log.last_log.log_content);
			}
			else
			{
				$(".status-label").removeClass("label-success2");
				$(".status-label").removeClass("label-danger2");

				$(".status-label").addClass("label-danger2");
				$(".status-label").append("ERROR");

				$(".error_detail_content").append('<pre><code>'+response.msg+'</code></pre>');

				$("#row_error_message").show();
				$("#row_error_detail").show();

			}

		},'json');

	});

	$('#tb_modal').on('shown.bs.modal', function (e) {

		$("#row_response_url").hide();
		$("#row_response_token").hide();
		$("#row_error_message").hide();
		$("#row_error_detail").hide();
		$("#row_response_status").hide();

	});

});
