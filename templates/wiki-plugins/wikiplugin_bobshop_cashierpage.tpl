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
{if $showPrices}
	<h2>Rechnungsempfänger</h2>
{else}
	<h2>Angebotsempfänger</h2>
{/if}
{if $shopConfig['bobshopConfigTikiUserRegistration'] == 'y'} 
	<p>Eingeloggt als: {$user}</p>
		{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}
	<br>
{else}
	<table class="table table-hover">
		<colgroup>
			<col width='20%'>
			<col width='80%'>
		</colgroup>

		{foreach $bobshopUserFields as $fieldname}
			{$required = ''}
			{$type = 'text'}


			{if !is_array($fieldname)} 
				{if array_key_exists($fieldname, $bobshopUserFields)}
					{if is_array($bobshopUserFields[$fieldname])}
						{if $bobshopUserFields[$fieldname][0] eq 'required'} 
							{$required = 'required'}
						{/if}
						{if $bobshopUserFields[$fieldname][0] eq 'email'} 
							{$type = 'email'}
						{/if}
					{/if}
				{/if}


				{if isset($bobshopUserData[$fieldname])}
					{$value = $bobshopUserData[$fieldname]}
				{else}
					{$value = ''}
				{/if}
				<tr>
					<td>
						<label class="col-from-label" for="f{$fieldname}">{tr}{$fieldname}{/tr}<label/>
					</td>
					<td>
						<input class="form-control" {$required} id="f{$fieldname}" type="{$type}" name="{$fieldname}" value="{$value}">

					</td>
				</tr>
			{/if}

		{/foreach}

	</table>
{/if}

<h2>Zahlungsmethode</h2>

{* payment *}
{if $showPrices}
{*<h2>Zahlungsart</h2>*}
<table class="table table-hover">
	<colgroup>
		<col width='30%'>
		<col width='50%'>
		<col width='20%'>
	</colgroup>
	<tr>
		<th>
			Auswahl
		</th>
		<th>
			Service
		</th>
		<th>
			Kosten
		</th>
	</tr>
	{foreach from=$payment key=key item=row}
		{*show only active payments*}
		{if {$row.{$shopConfig['paymentActiveFieldId']}} == 1}
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
					{if {$row.{$shopConfig['paymentIconFieldId']}} != ''}
					{wikiplugin _name="IMG" 
						fileId="{$row.{$shopConfig['paymentIconFieldId']}}"
						width="100px"
					}
					{/wikiplugin}
					{else}
						<p></p>
					{/if}
					</label>
				</td>
				<td>
					{$row.{$shopConfig['paymentNameFieldId']}} 
				</td>
				<td>
					{$row.{$shopConfig['paymentPriceFieldId']}|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
				</td>
			</tr>
		{/if}
	{/foreach}
</table>

{/if}

{* display cart *}
{*
<h2>Warenkorbübersicht</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}
<hr>
*}

{if $showPrices}
	{* display shipping *}
	
	{* shipping costs *}
	{foreach from=$orderItems item=product}
		{if $product.{$shopConfig['orderItemQuantityFieldId']} > 0}
			{assign var="cat" value="bobshopConfigShippingCostCat{$product.{$shopConfig['productShippingCatFieldId']}}"}
			{if $sumShipping < $shopConfig[$cat]}
				{$sumShipping = $shopConfig[$cat]}
			{/if}	
		{/if}
	{/foreach}
	{assign var="rate" value="bobshopConfigTaxrate{$shopConfig['bobshopConfigShippingCostTaxrateCat']}"}
	{if $shopConfig.bobshopConfigShippingCostTaxrateCat eq 1}
		{$sumShipping = $sumShipping + $shopConfig[$rate]/100 * $sumShipping}
	{elseif $shopConfig.bobshopConfigShippingCostTaxrateCat eq 2}
		{$sumShipping = $sumShipping + $shopConfig[$rate]/100 * $sumShipping}
	{elseif $shopConfig.bobshopConfigShippingCostTaxrateCat eq 3}
		{$sumShipping = $sumShipping + $shopConfig[$rate]/100 * $sumShipping}
	{/if}
	
	<h2>Versandart</h2><br>
	{* as long ther is only 1 shipping method it is checked by default *}
	{$checked = "checked='checked'"}
	<table class="table table-hover">
		<colgroup>
			<col width='30%'>
			<col width='50%'>
			<col width='20%'>
		</colgroup>
		<tr>
			<th>
				Auswahl
			</th>
			<th>
				Service
			</th>
			<th>
				Kosten
			</th>
		</tr>
		<tr>
			<td>	
				<input {$checked} type="radio" name="shipping" value="{$key}">
				<label>
				</label>
			</td>
			<td>
				DHL 
			</td>
			<td>
				{$sumShipping|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
			</td>
		</tr>
	</table>	


	<hr>
	{*{$smarty.now|date_format:'%Y-%m-%d %H:%M:%S'}<br>*}

	<br>
	<h2>AGB und Widerruf</h2>
	<br>
	{* display revocation notice agreement *}
	{if $shopConfig['bobshopConfigTermsOfServicePage'] != ''}
		<input required type="checkbox" name="tos" value="{$smarty.now}">
		<label>Ich habe die aktuellen AGB's gelesen und stimme diesen zu.</label>
		<br><a target='_blank' href="tiki-index.php?page={$shopConfig['bobshopConfigTermsOfServicePage']}">Allgemeine Geschäftsbedingungen in neuem Fenster anzeigen.</a>
		<hr>
	{/if}

	{* display tos agreement *}
	{if $shopConfig['bobshopConfigRevocationNotice'] != ''}
		<input required type="checkbox" name="revocation" value="{$smarty.now}">
		<label>Ich habe die aktuelle Widerrufsbelehrung gelesen und stimme dieser zu.</label>
		<br><a target='_blank' href="tiki-index.php?page={$shopConfig['bobshopConfigRevocationNotice']}">Widerrufsbelehrung in neuem Fenster anzeigen.</a>
		<hr>
	{/if}
{else}
	{* Datenschutz *}
	Datenschutz zustimmen.
	<hr>
{/if}

<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="tiki-index.php?page=bobshop_cart">{tr}Back{/tr}</a>

{if $showPrices}
	<input type="hidden" name="action" value="checkout">
	<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['bobshopConfigCheckoutButtonText']|escape}{/tr}">
{else}
	<input type="hidden" name="action" value="invite_offer">
	<input type="submit" class="btn btn-secondary" value="{tr}Angebot jetzt anfragen{/tr}">
{/if}
</form>