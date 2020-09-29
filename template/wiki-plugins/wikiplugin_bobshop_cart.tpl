{* $Id$ *}
{if $status == 1}
	<h2>Warenkorb</h2>
	{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl"}
	<hr>
	<form method="post" action="tiki-index.php?page=bobshop_cashierpage" style="display: inline;" class="wp_addtocart_form"{$form_data}>
			<input type="hidden" name="action" value="cashierbutton">

		<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['shopConfig_cashierButtonText']|escape}{/tr}">
	</form>
{elseif $status == 0}
	<h2>Der Warenkorb ist leer.</h2>
{/if}