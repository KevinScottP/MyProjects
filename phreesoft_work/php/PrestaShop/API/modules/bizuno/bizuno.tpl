{if substr($ps_version, 0, 3) == '1.5'}
	<fieldset>
		<legend><img src="../modules/bizuno/{$biz_id}.png" height="16" />{$biz_title}</legend>
			{if isset($downloaded) && $downloaded}
				{l s='This order has been Downloaded.'}<br>
			{else}
				<div id="bizuno"><button type="button" onClick="javascript: orderDownload('{$order}');">{l s='Download This Order'}</button></div>
			{/if}
	</fieldset>
{else}
	<div class="panel">
		<div class="panel-heading">
			<i class="icon-credit-card"></i>{$biz_title}
			{if isset($downloaded) && $downloaded}
				<span class="badge">1</span>
			{else}
				<span class="badge">0</span>
			{/if}
		</div>
		<div id="bizuno" class="well hidden-print">
			<div class="block_content">
				<p>
				{if isset($downloaded) && $downloaded}
					This order has been Downloaded.<br>
				{else}
					<a href="javascript: orderDownload('{$order}');" title="Download">Download This Order</a><br>
				{/if}
				</p>
			</div>
		</div>
	</div>
{/if}

<script type="text/javascript">
<!--
function orderDownload(id) {
	if (!id) return;
	$.ajax({
		url: "{$base_dir}../modules/bizuno/download.php?order_id="+id,
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		success: function(message) {
			var text = '';
			var all_good = true;
			if (message.error) {
				text += "Please fix the following errors: \n";
				all_good = false;
				for (var i=0; i<message.error.length; i++) text += message.error[i].text+"\n";
			}
			if (message.warning) {
				text += "Warnings: \n";
				for (var i=0; i<message.warning.length; i++) text += message.warning[i].text+"\n";
			}
			if (message.success) for (var i=0; i<message.success.length; i++) {
				text += message.success[i].text+"\n";
			}
			if (text) alert(text);
			if (all_good) $('#bizuno').remove();
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			alert("Ajax Error: "+errorThrown+' - '+XMLHttpRequest.responseText+"\nStatus: "+textStatus, 'info');
		},
	});
}
// -->
</script>