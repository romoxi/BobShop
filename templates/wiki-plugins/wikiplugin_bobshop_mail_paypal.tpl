{* $Id$ *}

<h1>{tr}payment information{/tr}
	
{if $error eq ''}
	
	<p>Wir haben die PayPal Zahlung unter folgenden Kennungen verzeichnet:</p>
	
	<p>Bestellnummer: {$orderNumberFormated}</p>
	{* transactionIdResponse *}
	<p>Transaktionsnummer: {$order.bobshopOrderPaymentOrderId}</p>
	<p>Gesamtsumme: {$order.bobshopOrderSumEnd}</p>
{else}
	
	<p>Bei der Bezahlung ist ein Fehler aufgetreten.</p>
	<br>
	<p>Wir kümmern uns persönlich um den Vorgang.</p>
	
{/if}
<br>
<br>
Mit freundlichen Grüßen von Ihrem Kundenservice
<br>
<br>
{$shopConfig.bobshopConfigCompanySignature|nl2br}
<br>
<br>