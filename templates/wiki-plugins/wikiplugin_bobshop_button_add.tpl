{* $Id$ *}
{*debug*}
{if $productId != 0 || $productId != ''}
{*<form method="post" action="{query _type=relative _keepall=y}" style="display: inline;" class="">*}
<form method="post" action="{query _type=relative _keepall=y}" style="display: inline;" class="">
	<input type="hidden" name="productId" value="{$productId|escape}">
	<input type="hidden" name="variant_selected" id="variant_selected" value="">
	<input type="hidden" name="action" value="add_to_cart">
	<input type="hidden" name="showdetails" value="{$showdetails|escape}">
	<input type="submit" class="btn btn-primary" value="{tr}{$shopConfig['bobshopConfigAddToCartButtonText']|escape}{/tr}">
</form>
{else}
	<p>productId not set</p>
{/if}