{* $Id$ *}

{* calculate the payment costs *}
{foreach from=$payment key=key item=row}
{if $key eq $order.bobshopOrderPayment}
	{$sumPayment = $row.{$shopConfig['paymentPriceFieldId']}}
{/if}
{/foreach}

<h1>Bestellbestätigung</h1>

<p>Hallo {$userDataDetail['login']},<p>
	<br>
<p>vielen Dank für Deine Bestellung.</p>
<br>
<p>Hiermit bestätigen wir, dass Deine Bestellung bei uns angekommen ist. </p>
<br>
<p>Im nächsten Schritt senden wir Dir eine Auftragsbestätigung.</p>
<br>
<br>
<p>Hier nochmal die Bestellung im Überblick:</p>


<h2>Rechnungsempfänger:</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}


<h2>Bestellte Produkte:</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}

<br>
<h2>Rechtliches Zeug</h2>
<p>Widerrufs- und Rückgaberecht mit Link auf Website oder als Anhang PDF?</p>
<br>
<h2>Kontaktinfo oder Verkäufer oder Händler</h2>
{$shopConfig.shopConfig_companySignature|nl2br}
