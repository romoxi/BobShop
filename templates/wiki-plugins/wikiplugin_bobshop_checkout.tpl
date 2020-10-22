{* $Id$ *}

{* variables *}
{$sumProducts = 0}
{$sumTaxrate1 = 0}
{$sumTaxrate2 = 0}
{$sumTaxrate3 = 0}
{$sumTaxrates = 0}
{$sumShipping = 0}
{$sumPayment = 0}
{$sumEnd = 0}

{* calculate the payment costs *}
{foreach from=$payment key=key item=row}

{if $key eq $order.bobshopOrderPayment}
	{$checked = ' checked="checked" '}
	{$sumPayment = $row.{$shopConfig['paymentPriceFieldId']}}
{else}
	{$checked = ' '}
{/if}
{/foreach}

<form method="post" action="tiki-index.php?page=bobshop_order_submitted" style="display: inline;" class="">
	<h2>Rechnungsempfänger</h2>
	{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}

	<h2>Warenkorb</h2>
	{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}

	<hr>
	{* vars as hidden fields *}
	<input type="hidden" name="sumProducts" value="{$sumProducts}">
	<input type="hidden" name="sumTaxratCat1" value="{$sumTaxrate1}">
	<input type="hidden" name="sumTaxratCat2" value="{$sumTaxrate2}">
	<input type="hidden" name="sumTaxratCat3" value="{$sumTaxrate3}">
	<input type="hidden" name="sumTaxrates" value="{$sumTaxrates}">
	<input type="hidden" name="sumShipping" value="{$sumShipping}">
	<input type="hidden" name="sumPayment" value="{$sumPayment}">
	<input type="hidden" name="sumPaymentName" value="{$sumPaymentName}">
	<input type="hidden" name="sumEnd" value="{$sumEnd}">
	<input type="hidden" name="action" value="order_submitted">

	{* buttons *}
	<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="tiki-index.php?page=bobshop_cashierpage">{tr}Back{/tr}</a>
	<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['bobshopConfigBuyNowButtonText']} {$payment.{$order.bobshopOrderPayment}.{$shopConfig['paymentBuyNowButtonTextExtraTextFieldId']}|escape}{/tr}">
</form>
