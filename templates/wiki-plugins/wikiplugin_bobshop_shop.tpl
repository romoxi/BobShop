{* $Id$ *}
{$divborder = 'border: solid 1px black;'}
{$divborder = ''}

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
						{wikiplugin _name="IMG" 
							fileId="{$row.bobshopProductPic1}"
							width="250px"
						}
						{/wikiplugin}
					</a>
				</div>
				
				{* 2 column *}
				<div style="{$divborder}" class="col-sm-5">
					<p>{$row.bobshopProductDescription|nl2br}</p>
				</div>


				{* 3 column *}
				<div style="{$divborder}" class="col-sm-4 text-right">
					{assign var="rate" value="shopConfig_taxrate{$row.bobshopProductTaxrateCat}"}
					{if $showPrices}
						{$price = $row.bobshopProductPrice + $row.bobshopProductPrice/100 * $shopConfig[$rate]}
						<h3 style="font-weight: bold;">{$price|string_format:"%.2f"} {$shopConfig['shopConfig_currencySymbol']}</h3>
						<p class="small">inkl. {$shopConfig[$rate]}% MwSt.<br>zzgl. Versandkosten</p>
					{/if}
					<p class="small">Artikelnr.: {$row.bobshopProductProductId}</p>
					{if $showPrices}
						<p>Lieferzeit.: {$row.bobshopProductDeliveryTime}</p>
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
