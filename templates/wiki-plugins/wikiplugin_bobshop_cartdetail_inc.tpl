<table class="table table-hover">
	<colgroup>
		<col width="5%">
		<col width="45%">
		<col width="5%">
		<col width="5%">
		<col width="5%">
		
		{if $showPrices}
			<col width="15%">
			<col width="15%">
			<col width="5%">
		{/if}
	</colgroup>
	<tr>
		<th >Produkt#</th>
		<th >Bezeichnung</th>
		<th ></th>
		<th >Anzahl</th>
		<th ></th>
		{if $showPrices}
			<th >Einzelpreis</th>
			<th >Gesamt</th>
			<th >MwSt</th>
		{/if}
	</tr>

	{* display the products *}	
	{foreach from=$orderItems item=product}
		
		{if $product.{$shopConfig['orderItemQuantityFieldId']} > 0}
			<tr>
				<td>{$product.{$shopConfig['productProductIdFieldId']}} -{$product.{$shopConfig['productShippingCatFieldId']}} </td>
				<td>{$product.{$shopConfig['productNameFieldId']}}</td>
				<td>
					{if $showQuantityModify == 1}
					<a style="margin: 0; padding: 0; width: 20px; height: 20px;" class="btn btn-primary" data-role="button" data-inline="true" title="-" href="tiki-index.php?page={$page}&action=quantitySub&productId={$product.{$shopConfig['productProductIdFieldId']}}">-</a>
					{/if}
				</td>
				<td style="text-align: center;">{$product.{$shopConfig['orderItemQuantityFieldId']}}</td>
				<td>
					{if $showQuantityModify == 1}
					<a style="margin: 0; padding: 0; width: 20px; height: 20px;" class="btn btn-primary" data-role="button" data-inline="true" title="+" href="tiki-index.php?page={$page}&action=quantityAdd&productId={$product.{$shopConfig['productProductIdFieldId']}}">+</a>
					{/if}
				</td>

				{assign var="rate" value="shopConfig_taxrate{$product.{$shopConfig['productTaxrateCatFieldId']}}"}
				
				{if $showPrices}
					<td style="text-align: right">{$product.{$shopConfig['productPriceFieldId']}|string_format: "%.2f"}</td>
					<td style="text-align: right">{{$product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}|string_format: "%.2f"}</td>
					<td style="text-align: right">{$shopConfig[$rate]}%</td>
				{/if}
			</tr>

			{$sumProducts = $sumProducts + ($product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']} )}

			{* calculate the sum of taxes *}
			{if $product.{$shopConfig['productTaxrateCatFieldId']} eq 1}
				{$sumTaxrate1 = $sumTaxrate1 + $shopConfig[$rate]/100 * $product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}
			{elseif $product.{$shopConfig['productTaxrateCatFieldId']} eq 2}
				{$sumTaxrate2 = $sumTaxrate2 + $shopConfig[$rate]/100 * $product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}
			{elseif $product.{$shopConfig['productTaxrateCatFieldId']} eq 3}
				{$sumTaxrate3 = $sumTaxrate3 + $shopConfig[$rate]/100 * $product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}
			{/if}

			{* shipping costs *}
			{assign var="cat" value="shopConfig_shippingCostCat{$product.{$shopConfig['productShippingCatFieldId']}}"}
			{if $sumShipping < $shopConfig[$cat]}
				{$sumShipping = $shopConfig[$cat]}
			{/if}
		{/if}
	{/foreach}
	
	{$sumTaxrates = $sumTaxrate1 + $sumTaxrate2 + $sumTaxrate3}

{if $showPrices}
{* sum of products *}
<tr>
	<td></td>
	<td>
		<b>Summe Warenwert</b>
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		<b>{$sumProducts|string_format: "%.2f"}</b>
	</td>
	<td></td>
</tr>

{* shipping *}
<tr>
	<td></td>
	<td>
		Versandkosten
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		{$sumShipping|string_format: "%.2f"}
	</td>
	<td style="text-align: right">
		{assign var="rate" value="shopConfig_taxrate{$shopConfig['shopConfig_shippingCostTaxrateCat']}"}
		{$shopConfig[$rate]}%
	</td>
</tr>

	{if $product.{$shopConfig['shopConfig_shippingCostTaxrateCat']} eq 1}
		{$sumTaxrate1 = $sumTaxrate1 + $shopConfig[$rate]/100 * $sumShipping}
	{elseif $product.{$shopConfig['shopConfig_shippingCostTaxrateCat']} eq 2}
		{$sumTaxrate2 = $sumTaxrate2 + $shopConfig[$rate]/100 * $sumShipping}
	{elseif $product.{$shopConfig['shopConfig_shippingCostTaxrateCat']} eq 3}
		{$sumTaxrate3 = $sumTaxrate3 + $shopConfig[$rate]/100 * $sumShipping}
	{/if}

{* VAT *}
{if $sumTaxrate1 > 0}
	<tr>
		<td></td>
		<td>
			MwSt
		</td>
		<td></td>
		<td style="text-align: right">
			{$shopConfig['shopConfig_taxrate1']}%
		</td>
		<td></td>
		<td style="text-align: right">
			{$sumTaxrate1|string_format: "%.2f"}
		</td>
		<td></td>
		<td></td>
	</tr>
{/if}
{if $sumTaxrate2 > 0}
	<tr>
		<td></td>
		<td>
			MwSt
		</td>
		<td></td>
		<td style="text-align: right">
			{$shopConfig['shopConfig_taxrate2']}%
		</td>
		<td></td>
		<td style="text-align: right">
			{$sumTaxrate2|string_format: "%.2f"}
		</td>
		<td></td>
		<td></td>
	</tr>
{/if}
{if $sumTaxrate3 > 0}
	<tr>
		<td></td>
		<td>
			MwSt
		</td>
		<td></td>
		<td style="text-align: right">
			{$shopConfig['shopConfig_taxrate3']}%
		</td>
		<td></td>
		<td style="text-align: right">
			{$sumTaxrate3|string_format: "%.2f"}
		</td>
		<td></td>
		<td></td>
	</tr>
{/if}
<tr>
	<td></td>
	<td>
		MwSt gesamt
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		{$sumTaxrates|string_format: "%.2f"}
	</td>
	<td></td>
</tr>

{* Payment Sum *}
{if $showPayment == 1}
	<tr>
		<td></td>
		<td>
			{$sumPaymentName = $payment.{$order['bobshopOrderPayment']}.{$shopConfig['paymentNameFieldId']}}
			{$sumPaymentName} 
			{if $sumPayment > 0}
				(Kosten)
			{/if}
		</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td style="text-align: right">
			{$sumPayment|string_format: "%.2f"}
		</td>
		<td></td>
	</tr>
{/if}


{* sum End *}
{$sumEnd = $sumPayment + $sumTaxrates + $sumProducts + {$shopConfig['shopConfig_shippingCostCat1']}}
<tr>
	<td></td>
	<td>
		<b>Endsumme inkl. MwSt</b>
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		<b>{$sumEnd|string_format: "%.2f"}</b>
	</td>
	<td></td>
</tr>
{/if}

</table>

{if $showPrices}
<i>Alle Preisangaben in {$shopConfig['shopConfig_currency']}.</i>
{/if}