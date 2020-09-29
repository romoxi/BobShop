{* $Id$ *}
{* display billing adress *}

{* variables *}
{$sumProducts = 0}
{$sumTaxrate1 = 0}
{$sumTaxrate2 = 0}
{$sumTaxrate3 = 0}
{$sumTaxrates = 0}
{$sumShipping = 0}
{$sumPayment = 0}

{$sumEnd = 0}

<form method="post" action="tiki-index.php?page=bobshop_checkout" style="display: inline;" class="">
<h2>Rechnungsempfänger</h2>
<p>Schon eingeloggt als: {$user}</p>
{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}
<hr>
{* payment *}
<h2>Zahlungsart</h2>
<table width='50%' border='1px'>
	<colgroup>
		<col colspan='70%'>
		<col colspan='30%'>
	</colgroup>
	<tr>
		<th>
			Service
		</th>
		<th>
			Kosten
		</th>
	</tr>
		{foreach from=$payment key=key item=row}
			{if $key eq $order.bobshopOrderPayment}
				{$checked = ' checked="checked" '}
				{$sumPayment = $row.{$shopConfig['paymentPriceFieldId']}}
			{else}
				{$checked = ' '}
			{/if}
			<tr>
				<td>	
					<input {$checked} type="radio" name="payment" value="{$key}">
					<label>
						{$row.{$shopConfig['paymentNameFieldId']}} 
				</td>
				<td>
						{$row.{$shopConfig['paymentPriceFieldId']}}
					</label>
				</td>
			</tr>
		{/foreach}
</table>
<hr>
{* display cart *}
<h2>Warenkorbübersicht</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}

<hr>

<input type="hidden" name="action" value="checkout">
<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['shopConfig_checkoutButtonText']|escape}{/tr}">
</form>