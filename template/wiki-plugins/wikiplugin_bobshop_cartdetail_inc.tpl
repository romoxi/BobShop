<table width="100%" border="1px">
	<colgroup>
		<col width="5%">
		<col width="45%">
		<col width="5%">
		<col width="20%">
		<col width="20%">
		<col width="5%">
	</colgroup>
	<tr>
		<th >Produkt#</th>
		<th >Bezeichnung</th>
		<th >Anzahl</th>
		<th >Einzelpreis</th>
		<th >Gesamtpreis</th>
		<th >MwSt</th>
	</tr>

	{* display the products *}	
	{foreach from=$orderItems item=product}
		<tr>
			<td>{$product.{$shopConfig['productProductIdFieldId']}} -{$product.{$shopConfig['productShippingCatFieldId']}} </td>
			<td>{$product.{$shopConfig['productNameFieldId']}}</td>
			<td>{$product.{$shopConfig['orderItemQuantityFieldId']}}</td>
			<td style="text-align: right">{$product.{$shopConfig['productPriceFieldId']}|string_format: "%.2f"}</td>
			<td style="text-align: right">{{$product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}|string_format: "%.2f"}</td>
			{assign var="rate" value="shopConfig_taxrate{$product.{$shopConfig['productTaxrateCatFieldId']}}"}
			<td style="text-align: right">{$shopConfig[$rate]}</td>
			
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
	{/foreach}
	
	{$sumTaxrates = $sumTaxrate1 + $sumTaxrate2 + $sumTaxrate3}

{* sum of products *}
<tr>
	<td></td>
	<td>
		<b>Summe Warenwert</b>
	</td>
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
	<td style="text-align: right">
		{$sumShipping|string_format: "%.2f"}
	</td>
	<td style="text-align: right">
		{assign var="rate" value="shopConfig_taxrate{$shopConfig['shopConfig_shippingCostTaxrateCat']}"}
		{$shopConfig[$rate]}
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
<tr>
	<td></td>
	<td>
		MwSt
	</td>
	<td style="text-align: right">
		{$shopConfig['shopConfig_taxrate1']}
	</td>
	<td style="text-align: right">
		{$sumTaxrate1|string_format: "%.2f"}
	</td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td>
		MwSt
	</td>
	<td style="text-align: right">
		{$shopConfig['shopConfig_taxrate2']}
	</td>
	<td style="text-align: right">
		{$sumTaxrate2|string_format: "%.2f"}
	</td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td>
		MwSt
	</td>
	<td style="text-align: right">
		{$shopConfig['shopConfig_taxrate3']}
	</td>
	<td style="text-align: right">
		{$sumTaxrate3|string_format: "%.2f"}
	</td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td>
		MwSt gesamt
	</td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		{$sumTaxrates|string_format: "%.2f"}
	</td>
	<td></td>
</tr>

{if $showPayment == 1}
{* Payment Sum *}
<tr>
	<td></td>
	<td>
		Bezahlkosten
	</td>
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
		<b>Endsumme</b>
	</td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		<b>{$sumEnd|string_format: "%.2f"}</b>
	</td>
	<td></td>
</tr>


</table>
