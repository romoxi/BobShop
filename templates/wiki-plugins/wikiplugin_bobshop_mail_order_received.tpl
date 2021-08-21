{* $Id$ *}

{* calculate the payment costs *}
{foreach from=$payment key=key item=row}
{if $key eq $order.bobshopOrderPayment}
	{$sumPayment = $row.{$shopConfig['paymentPriceFieldId']}}
{/if}
{/foreach}

<h1>{tr}Order confirmation{/tr}</h1>

<p>{tr}Dear Sir / Madam{/tr},<p>
<br>
<p>{tr}Many thanks for your order{/tr}.</p>
<br>
{*<p>Bestellnummer: {$order.bobshopOrderOrderNumber}</p>*}
<p>{tr}Order number{/tr}: {$orderNumberFormated}</p>
<br>
<br>
<p>{tr}We hereby confirm your incoming order{/tr}.</p>
<br>
<br>
<br>
<b>{tr}Invoice recipient{/tr}:</b><br>
<br>
{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}
<br>
<br>
<b>{tr}ordered products{/tr}:</b><br>
<br>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}
<br>
<br>
{tr}Yours sincerely{/tr}
<br>
<br>
{$shopConfig.bobshopConfigCompanySignature|nl2br}
<br>
<br>
<hr>
{if $shopConfig.bobshopConfigTermsOfServicePage != ''}
	<br>
	<br><b>{tr}terms and conditions{/tr} {tr}and{/tr} {tr}revocation instruction{/tr}</b><br>
	<br>
	{wikiplugin _name="INCLUDE" 
		page="{$shopConfig.bobshopConfigTermsOfServicePage}"
		nopage_text="{tr}could not be loaded{/tr}. {tr}please contact the service department{/tr}."
		parse_included_page="n"
		max_inclusions="1"
	}{/wikiplugin}
{/if}
	
{if $shopConfig.bobshopConfigRevocationNotice != ''}
	<br>
	<br><b>{tr}revocation instruction{/tr}</b><br>
	<br>
	{wikiplugin _name="INCLUDE" 
		page="{$shopConfig.bobshopConfigRevocationNotice}"
		nopage_text="{tr}could not be loaded{/tr}. {tr}please contact the service department{/tr}."
		parse_included_page="n"
		max_inclusions="1"
	}{/wikiplugin}
{/if}
