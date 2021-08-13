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
		<label for="sorting">{$shopConfig['bobshopConfigSortingLabelText']}</label>
		<select name="sort_order" id="sort_order" onchange="this.form.submit()">
			{sorting_option lastSort={$lastSort} value="sort_sort_order" text={$shopConfig['bobshopConfigSortingDefaultText']}}
			{sorting_option lastSort={$lastSort} value="sort_price_up" text={$shopConfig['bobshopConfigSortingPriceUpText']}}
			{sorting_option lastSort={$lastSort} value="sort_price_down" text={$shopConfig['bobshopConfigSortingPriceDownText']}}
			{sorting_option lastSort={$lastSort} value="sort_name" text={$shopConfig['bobshopConfigSortingNameText']}}
		</select>
		{*<input type="submit" value="Sub">*}
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
						{$price = $row.bobshopProductPrice + $row.bobshopProductPrice/100 * $shopConfig[$rate]}
						<h3 style="font-weight: bold;">{$price|string_format:"%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</h3>
						<p class="small">inkl. {$shopConfig[$rate]}% MwSt.</p>
						<p class="small" onClick='javascript:$( "#wpdialog_bobshop_shipping" ).dialog( "open" );'>Versandkostenkategorie: {$row.bobshopProductShippingCat}
						<br>zzgl. Versandkosten</p>
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
					<p class="small">Artikelnr.: {$row.bobshopProductProductId}</p>

					{* stock control*}
					{if $shopConfig.bobshopConfigStockControl eq "y"}
						{* <p>Lagerbestand: {$row.bobshopProductStockQuantity}<p> *}
						<p>Lieferzeit: 
						{if $row.bobshopProductStockQuantity > 0}
							{if 
								($row.bobshopProductStockWarning == 0 and $row.bobshopProductStockQuantity < $shopConfig.bobshopConfigStockWarning)
								or
								($row.bobshopProductStockWarning  > 0 and $row.bobshopProductStockQuantity < $row.bobshopProductStockWarning)
							}
								<span style="color: orange;">Lagerbestand gering!</span>
							{else}
								<span style="color: green;">Ab Lager.</span>
							{/if}
						{else}
							<span style="color: red;">Derzeit nicht lieferbar!</span>
						{/if}
						</p>
					{/if}

					{if $showPrices and $shopConfig.bobshopConfigStockControl eq "n"}
						<p>Lieferzeit: {$row.bobshopProductDeliveryTime}</p>
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
	title="Versandkosten"
	width="400"
	modal="y"
	wiki="bobshop_shipping"
}{/wikiplugin}