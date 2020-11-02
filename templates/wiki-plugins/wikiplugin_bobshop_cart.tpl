{* $Id$ *}
{if $status == 1}
	<h2>Warenkorb</h2>
	{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl"}
	<hr>
	<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="javascript:history.go(-1)">{tr}Back{/tr}</a>

	<form method="post" action="tiki-index.php?page=bobshop_cashierpage" style="display: inline;" class="">
		<input type="hidden" name="action" value="cashierbutton">
		{if $showPrices}
			<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['bobshopConfigCashierbutton']|escape}{/tr}">
		{else}
			<input type="submit" class="btn btn-secondary" value="{tr}Weiter zur Angebotsanfrage{/tr}">
		{/if}
		
	</form>

		
		
{elseif $status == 0}
	<h2>Der Warenkorb ist leer.</h2>
	<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="javascript:history.go(-1)">{tr}Back{/tr}</a>

{/if}
<hr>
{include file="templates/wiki-plugins/wikiplugin_bobshop_memory_code_button.tpl" scope="global"}