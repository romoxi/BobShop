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

<form method="post" action="tiki-index.php?page=bobshop_checkout" style="display: inline;" class="">
{* display billing adress *}
<h2>Rechnungsempfänger</h2>
<p>Eingeloggt als: {$user}</p>
{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}
<hr>

{* payment *}
<h2>Zahlungsart</h2>
<table class="table table-hover">
	<colgroup>
		<col width='30%'>
		<col width='50%'>
		<col width='20%'>
	</colgroup>
	<tr>
		<th>
		</th>
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
				{$sumPaymentName = $row.{$shopConfig['paymentNameFieldId']}}
			{else}
				{$checked = ' '}
			{/if}
			<tr>
				<td>	
					<input {$checked} type="radio" name="payment" value="{$key}">
					<label>
					{wikiplugin _name="IMG" 
						fileId="{$row.{$shopConfig['paymentIconFieldId']}}"
						width="100px"
					}
					{/wikiplugin}
					</label>
				</td>
				<td>
						{$row.{$shopConfig['paymentNameFieldId']}} 
				</td>
				<td>
						{$row.{$shopConfig['paymentPriceFieldId']}|string_format: "%.2f"}
				</td>
			</tr>
		{/foreach}
</table>
<hr>

{* display cart *}
<h2>Warenkorbübersicht</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}

<hr>
{*{$smarty.now|date_format:'%Y-%m-%d %H:%M:%S'}<br>*}

{* display revocation notice agreement *}
<input required type="checkbox" name="revocation" value="{$smarty.now}">
<label>Ich habe die aktuelle Widerrufsbelehrung gelesen und stimme dieser zu.</label>
<br><a target='_blank' href="tiki-index.php?page={$shopConfig['shopConfig_TermsOfServicePage']}">Widerrufsbelehrung in neuem Fenster anzeigen.</a>
<hr>

{* display tos agreement *}
<input required type="checkbox" name="tos" value="{$smarty.now}">
<label>Ich habe die aktuellen AGB's gelesen und stimme diesen zu.</label>
<br><a target='_blank' href="tiki-index.php?page={$shopConfig['shopConfig_RevocationNotice']}">Allgemeine Geschäftsbedingungen in neuem Fenster anzeigen.</a>
<hr>

<input type="hidden" name="action" value="checkout">
<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="tiki-index.php?page=bobshop_cart">Back</a>
<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['shopConfig_checkoutButtonText']|escape}{/tr}">
</form>