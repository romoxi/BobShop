{* $Id$ *}
{* prints a list of all products *}
{$divborder = 'border: solid 1px black;'}
{$divborder = ''}

{* function for sorting elements dropdown stuff*}
{function name=sorting_option value="" text="" lastSort="sort_sort_order"}
	{if $lastSort == $value}
		{$selected="selected"}
	{else}
		{$selected=""}
	{/if}
	<option {$selected} value="{$value}">{$text}</option>
{/function}


{* sorting buttons *}
<div class="col-sm-12" style="text-align: right">
	<form  action={$page}>
		<label for="sorting">{tr}{$shopConfig['bobshopConfigSortingLabelText']}{/tr}</label>
		<select name="sort_order" id="sort_order" onchange="this.form.submit()">
			{sorting_option lastSort={$lastSort} value="sort_sort_order" text="{tr}{$shopConfig['bobshopConfigSortingDefaultText']}{/tr}"}
			{sorting_option lastSort={$lastSort} value="sort_price_up" text="{tr}{$shopConfig['bobshopConfigSortingPriceUpText']}{/tr}"}
			{sorting_option lastSort={$lastSort} value="sort_price_down" text="{tr}{$shopConfig['bobshopConfigSortingPriceDownText']}{/tr}"}
			{sorting_option lastSort={$lastSort} value="sort_name" text="{tr}{$shopConfig['bobshopConfigSortingNameText']}{/tr}"}
		</select>
	</form>
</div>


{* display a list of products*}
{foreach from=$products item=row}
	{if $row.bobshopProductActive == 1}
	
	{* container *}
	<div style="{$divborder}" class="row">
		
		{* Header *}
		<div class="col-sm-12">
			<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">
				<h2>{$row.bobshopProductName}</h2>
			</a>

			{* 1 column *}
			<div style="{$divborder}" class="row">
				
				<div style="{$divborder}" class="col-sm-3">
					{* image with link to detail *}
					<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">
						{if $row.bobshopProductPic1 != ''}
							{wikiplugin _name="IMG" 
								fileId="{$row.bobshopProductPic1}"
							}
							{/wikiplugin}
						{else}
							{wikiplugin _name="IMG" 
								fileId="{$shopConfig['bobshopConfigProductPicMissingPic']}"
							}
							{/wikiplugin}
							
						{/if}
					</a>
				</div>
				
				{* 2 column *}
				<div style="{$divborder}" class="col-sm-5">
					{*<p>{$row.bobshopProductDescription|nl2br}</p>*}
					{wiki}{$row.bobshopProductDescription}{/wiki}
				</div>


				{* 3 column *}
				<div style="{$divborder}" class="col-sm-4 text-right">
					{assign var="rate" value="bobshopConfigTaxrate{$row.bobshopProductTaxrateCat}"}
					{if $showPrices}
						{if $row.bobshopProductVariantProductIds != ''}
							{tr}from-price{/tr}
						{/if}
						{$price = $row.bobshopProductPrice + $row.bobshopProductPrice/100 * $shopConfig[$rate]}
						<h3 style="font-weight: bold;">{$price|string_format:"%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</h3>
						<p class="small">{tr}incl.{/tr} {$shopConfig[$rate]}% {tr}VAT{/tr}</p>
						<p class="small" onClick='javascript:$( "#wpdialog_bobshop_shipping" ).dialog( "open" );'>{tr}Shipping Category{/tr}: {$row.bobshopProductShippingCat}
						<br>{tr}plus Shipping Costs{/tr}</p>
						{*
						<p class="small" onClick='javascript:$( "#ver_{$row.bobshopProductProductId}" ).dialog( "open" );'>zzgl. Versandkosten</p>
							{wikiplugin _name="DIALOG"
								autoOpen="n"
								id="ver_{$row.bobshopProductProductId}"
								title="Versandkosten"
								modal="y"
								wiki="bobshop_shipping"
							}{/wikiplugin}
						*}
					{/if}
					<p class="small">{tr}Part Number{/tr}: {$row.bobshopProductProductId}</p>

					{* stock control*}
					{if $shopConfig.bobshopConfigStockControl eq "y"}
						{* <p>Lagerbestand: {$row.bobshopProductStockQuantity}<p> *}
						<p>{tr}Lead Time{/tr}: 
						{if $row.bobshopProductStockQuantity > 0}
							{if 
								($row.bobshopProductStockWarning == 0 and $row.bobshopProductStockQuantity < $shopConfig.bobshopConfigStockWarning)
								or
								($row.bobshopProductStockWarning  > 0 and $row.bobshopProductStockQuantity < $row.bobshopProductStockWarning)
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

					{if $showPrices and $shopConfig.bobshopConfigStockControl eq "n"}
						<p>{tr}Lead Time{/tr}: {$row.bobshopProductDeliveryTime}</p>
					{/if}
					
					{if $cart}
						{$productId = $row.bobshopProductProductId}
						{include file="templates/wiki-plugins/wikiplugin_bobshop_button_add.tpl"}
					{/if}					
					{*
					<a class="btn btn-primary  mb-2" target="" data-role="button" data-inline="true" title="Da muss ich mehr wissen!" 
					   href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">Da muss ich mehr wissen!</a>
					   *}
				</div>
			</div>
		</div>
	</div>
	
	<hr>
	{/if}
{/foreach}

{* popup by click on shipping costs *}
{wikiplugin _name="DIALOG"
	autoOpen="n"
	id="wpdialog_bobshop_shipping"
	title="{tr}Shipping Costs{/tr}"
	width="400"
	modal="y"
	wiki="bobshop_shipping"
}{/wikiplugin}