<table class="table table-hover">
	{* for the mails don't colgroup *}
	{if !isset($mailer) || (isset($mailer) && $mailer == 0)}
		<colgroup>
			<col width="5%">
			<col width="45%">
			<col width="3%">
			<col width="9%">
			<col width="3%">

			{if $showPrices}
				<col width="15%">
				<col width="15%">
				<col width="5%">
			{/if}
		</colgroup>
	{/if}
	
	<tr>
		<th>{tr}Part-Nb{/tr}</th>
		<th>{tr}Designation{/tr}</th>
		<th colspan="3" style="text-align: center;">{tr}Quantity{/tr}</th>
		{*<th >Anzahl</th>
		<th ></th>*}
		{if $showPrices}
			<th>{tr}Unit Price{/tr}</th>
			<th>{tr}Total Price{/tr}</th>
			<th>{tr}VAT{/tr}</th>
		{/if}
	</tr>

	{* display the products *}	
	{if $showQuantityModify == 1}
	<form method="post" id="2" action="tiki-index.php?page=bobshop_cart" style="display: inline;" class="">
	{/if}
	{foreach from=$orderItems item=product}
		
		{if $product.{$shopConfig['orderItemQuantityFieldId']} > 0}
			<tr>
				<td><a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$product.{$shopConfig['productProductIdFieldId']}}">
					{$product.{$shopConfig['productProductIdFieldId']}}
					</a>
				</td>
				<td>{$product.{$shopConfig['productNameFieldId']}}
					{* stock control*}
					{if $shopConfig['bobshopConfigStockControl'] eq "y"}
						<p style="font-size: small">{tr}Lead Time{/tr}: 
						{if $product.{$shopConfig['productStockQuantityFieldId']} >= $product.{$shopConfig['orderItemQuantityFieldId']}}
							{if 
								($product.{$shopConfig['productStockWaringFieldId']} == 0 and $product.{$shopConfig['productStockQuantityFieldId']} < $shopConfig['bobshopConfigStockWarning'])
								or
								($product.{$shopConfig['productStockWarningFieldId']}  > 0 and $product.{$shopConfig['productStockQuantityFieldId']} < $product.{$shopConfig['productStockWarningFieldId']})
							}
								<span style="color: orange;">{tr}Low Inventory{/tr}</span>
							{else}
								<span style="color: green;">{tr}Ex Stock{/tr}</span>
							{/if}
						{else}
							<span style="color: red;">{tr}Out Of Stock{/tr}</span>
						{/if}
						</p>
					{/if}					
					
					
				</td>
				<td style="padding-left: 0; padding-right: 5px;">
					{if $showQuantityModify == 1}
						<a class="btn btn-primary btn-xs" data-role="button" data-inline="true" title="-" href="tiki-index.php?page={$page}&action=quantitySub&productId={$product.{$shopConfig['productProductIdFieldId']}}">
						{icon name="minus"}</a>
					{/if}
				</td>
				{if $showQuantityModify == 1}
					<td style="padding-left: 0; padding-right: 0;">
						<input type="text" name="quantity{$product.{$shopConfig['productProductIdFieldId']}}" value="{$product.{$shopConfig['orderItemQuantityFieldId']}}" class="form-control">
					</td>
				{else}
					<td style="text-align: center;">{$product.{$shopConfig['orderItemQuantityFieldId']}}</td>
				{/if}
				<td style="padding-left: 5px; padding-right: 0;">
					{if $showQuantityModify == 1}
						<a class="btn btn-primary btn-xs" data-role="button" data-inline="true" title="+" href="tiki-index.php?page={$page}&action=quantityAdd&productId={$product.{$shopConfig['productProductIdFieldId']}}">
						{icon name="create"}</a>
					{/if}
				</td>

				{assign var="rate" value="bobshopConfigTaxrate{$product.{$shopConfig['productTaxrateCatFieldId']}}"}
				{assign var="quantity" value="{$product.{$shopConfig['orderItemQuantityFieldId']}}"}
				{if $showPrices}
					
					<td style="text-align: right">{$product.{$shopConfig['productPriceFieldId']}|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</td>
					<td style="text-align: right">{math equation="q * p" q=$quantity p=$product.{$shopConfig['productPriceFieldId']} format="%.2f"}</td>
					<td style="text-align: right">{$shopConfig[$rate]} %</td>
				{/if}
			</tr>

			{*$sumProducts = $sumProducts + ($product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']} )*}
			{$sumProducts = $sumProducts + ($quantity * $product.{$shopConfig['productPriceFieldId']} )}

			{* calculate the sum of taxes *}
			{if $product.{$shopConfig['productTaxrateCatFieldId']} eq 1}
				{$sumTaxrate1 = $sumTaxrate1 + $shopConfig[$rate]/100 * $product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}
			{elseif $product.{$shopConfig['productTaxrateCatFieldId']} eq 2}
				{$sumTaxrate2 = $sumTaxrate2 + $shopConfig[$rate]/100 * $product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}
			{elseif $product.{$shopConfig['productTaxrateCatFieldId']} eq 3}
				{$sumTaxrate3 = $sumTaxrate3 + $shopConfig[$rate]/100 * $product.{$shopConfig['orderItemQuantityFieldId']} * $product.{$shopConfig['productPriceFieldId']}}
			{/if}

			{* shipping costs *}
			{assign var="cat" value="bobshopConfigShippingCostCat{$product.{$shopConfig['productShippingCatFieldId']}}"}
			{if $sumShipping < $shopConfig[$cat]}
				{$sumShipping = $shopConfig[$cat]}
			{/if}
		{/if}
	{/foreach}
	
	

{if $showPrices}
{* sum of products *}
<tr>
	<td></td>
	<td>
		<b>{tr}Sum{/tr}</b>
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		<b>{$sumProducts|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</b>
	</td>
	<td></td>
</tr>

{* shipping *}
<tr>
	<td></td>
	<td>
		{tr}Shipping Costs{/tr}
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		{$sumShipping|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
	</td>
	<td style="text-align: right">
		{assign var="rate" value="bobshopConfigTaxrate{$shopConfig['bobshopConfigShippingCostTaxrateCat']}"}
		{$shopConfig[$rate]} %
	</td>
</tr>

	{if $shopConfig.bobshopConfigShippingCostTaxrateCat eq 1}
		{$sumTaxrate1 = $sumTaxrate1 + $shopConfig[$rate]/100 * $sumShipping}
	{elseif $shopConfig.bobshopConfigShippingCostTaxrateCat eq 2}
		{$sumTaxrate2 = $sumTaxrate2 + $shopConfig[$rate]/100 * $sumShipping}
	{elseif $shopConfig.bobshopConfigShippingCostTaxrateCat eq 3}
		{$sumTaxrate3 = $sumTaxrate3 + $shopConfig[$rate]/100 * $sumShipping}
	{/if}

{* VAT *}
{if $sumTaxrate1 > 0}
	<tr>
		<td></td>
		<td>
			{tr}VAT{/tr}
		</td>
		{*<td></td>*}
		<td colspan="3" style="text-align: right">
			{$shopConfig['bobshopConfigTaxrate1']} %
		</td>
		{*<td></td>*}
		<td style="text-align: right">
			{$sumTaxrate1|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
		</td>
		<td></td>
		<td></td>
	</tr>
{/if}
{if $sumTaxrate2 > 0}
	<tr>
		<td></td>
		<td>
			{tr}VAT{/tr}
		</td>
		{*<td></td>*}
		<td colspan="3" style="text-align: right">
			{$shopConfig['bobshopConfigTaxrate2']} %
		</td>
		{*<td></td>*}
		<td style="text-align: right">
			{$sumTaxrate2|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
		</td>
		<td></td>
		<td></td>
	</tr>
{/if}
{if $sumTaxrate3 > 0}
	<tr>
		<td></td>
		<td>
			{tr}VAT{/tr}
		</td>
		{*<td></td>*}
		<td colspan="3" style="text-align: right">
			{$shopConfig['bobshopConfigTaxrate3']} %
		</td>
		{*<td></td>*}
		<td style="text-align: right">
			{$sumTaxrate3|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
		</td>
		<td></td>
		<td></td>
	</tr>
{/if}
{$sumTaxrates = $sumTaxrate1 + $sumTaxrate2 + $sumTaxrate3}
<tr>
	<td></td>
	<td>
		{tr}Sum{/tr} {tr}VAT{/tr}
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		{$sumTaxrates|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
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
				({tr}Costs{/tr})
			{/if}
		</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td style="text-align: right">
			{$sumPayment|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
		</td>
		<td></td>
	</tr>
{/if}
	
{* sum End *}
{$sumEnd = $sumPayment + $sumTaxrates + $sumProducts + $sumShipping}
<tr>
	<td></td>
	<td>
		<b>{tr}Total amount incl. VAT{/tr}</b>
	</td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td style="text-align: right">
		<b>{$sumEnd|string_format: "%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</b>
	</td>
	<td></td>
</tr>
{/if}

</table>

{if $showPrices}
	<i>{tr}All price quotations in{/tr} {$shopConfig['bobshopConfigCurrency']}.</i>
{/if}

	{if $showQuantityModify == 1}
		<br><br>
		<input type="hidden" name="action" value="modify_quantity">
		<input type="submit" class="btn btn-primary" value="{tr}Refresh Cart{/tr}">
		</form>
	{/if}