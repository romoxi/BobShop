{* $Id$ *}
<form method="post" action="{query _type=relative _keepall=y}" style="display: inline;" class="">
	<input type="hidden" name="productId" value="{$productId|escape}">
	<input type="hidden" name="action" value="add_to_cart">
	<input type="submit" class="btn btn-primary" value="{tr}{$addToCartButtonText|escape}{/tr}">
</form>



