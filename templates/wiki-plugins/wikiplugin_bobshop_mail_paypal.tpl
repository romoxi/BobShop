{* $Id$ *}
{* This is the for email after the paypal transmission was done *}

<h1>{tr}payment information{/tr}</h1>
<br>
<p>{tr}Dear Sir / Madam{/tr},<p>
<br>
<br>
{if $error eq ''}
	
	<p>recorded payment information:</p>
	
	<p>{tr}order number{/tr}: {$orderNumberFormated}</p>
	{* transactionIdResponse *}
	<p>{tr}transaction number{/tr}: {$order.bobshopOrderPaymentOrderId}</p>
	<p>{tr}Total amount{/tr}: {$order.bobshopOrderSumEnd}</p>
{else}
	
	<p>{tr}error during the payment prozess{/tr}</p>
	<br>
	<p>{tr}we will find an solution to fix that{/tr}</p>
	
{/if}
<br>
<br>
{tr}Yours sincerely{/tr}
<br>
<br>
{$shopConfig.bobshopConfigCompanySignature|nl2br}
<br>
<br>