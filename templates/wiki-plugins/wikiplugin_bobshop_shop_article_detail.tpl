{* $Id$ *}
{foreach from=$products item=row}
	{if $row.bobshopProductProductId == $productId}	
	
	{* container *}
	<div style="{$divborder}" class="row">
		
		{* Header *}
		<div class="col-sm-12">
			<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">
				<h2>{$row.bobshopProductName}</h2>
			</a>

			{* 1 column *}
			<div style="{$divborder}" class="row">
				
				<div style="{$divborder}" class="col-sm-7">
					{* image with link to detail *}
					<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">
						{wikiplugin _name="IMG" 
							fileId="{$row.bobshopProductPic1}"
							width="100%"
							thumb="zoom"
						}
						{/wikiplugin}
					</a>
				</div>
	
				{* 2 column *}
				<div style="{$divborder}" class="col-sm-5 text-right">
					{assign var="rate" value="shopConfig_taxrate{$row.bobshopProductTaxrateCat}"}
					{$price = $row.bobshopProductPrice + $row.bobshopProductPrice/100 * $shopConfig[$rate]}
					<h3 style="font-weight: bold;">{$price|string_format:"%.2f"} {$shopConfig['shopConfig_currencySymbol']}</h3>
					<p class="small">inkl. {$shopConfig[$rate]}% MwSt.<br>zzgl. Versandkosten</p>
					<p class="small">Artikelnr.: {$row.bobshopProductProductId}</p>
					<p>Lieferzeit.: {$row.bobshopProductDeliveryTime}</p>

					<form method="post" action="{query _type=relative _keepall=y}" style="display: inline;" class="wp_addtocart_form"{$form_data}>
						<input type="hidden" name="productId" value="{$productId|escape}">
						<input type="hidden" name="action" value="add_to_cart">
						<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['shopConfig_addToCartButtonText']|escape}{/tr}">
					</form>
				</div>
			</div>
		</div>
	</div>
	
	{* Detail container *}
	<div style="{$divborder} margin-top: 20px;" class="row">
		<div style="{$divborder}" class="col-sm-12">
			<p>{$row.bobshopProductDescription|nl2br}</p>
		</div>
	</div>
					
		
		{* include the wikipage *}
		{if $row.bobshopProductWikipage != ''}
			<hr>
			{wikiplugin _name="INCLUDE" 
				page="{$row.bobshopProductWikipage}"
				nopage_text="wikisite not found"
			}
			{/wikiplugin}
		{/if}
				
				
	<hr>
	{/if}
{/foreach}

<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="javascript:history.go(-1)">Back</a>


{*
<h3>Produktdetails</h3>
{foreach from=$products item=row}
	{if $row.bobshopProductProductId == $productId}
	
		<h2>{$row.bobshopProductName}</h2>

		{wikiplugin _name="IMG" 
			fileId="{$row.bobshopProductPic1}"
			width="400px"
		}
		{/wikiplugin}
		
		<p>{$row.bobshopProductDescription|nl2br}</p>
		<p>Preis: {$row.bobshopProductPrice|string_format:"%.2f"}</p>
		<p>Artikelnr.: {$row.bobshopProductProductId}</p>
		<hr>
		<form method="post" action="{query _type=relative _keepall=y}" style="display: inline;" class="wp_addtocart_form"{$form_data}>
			<input type="hidden" name="productId" value="{$productId|escape}">
			<input type="hidden" name="action" value="add_to_cart">
			<input type="submit" class="btn btn-secondary" value="{tr}{$shopConfig['shopConfig_addToCartButtonText']|escape}{/tr}">
		</form>
		
	
		{if $row.bobshopProductWikipage != ''}
			<hr>
			{wikiplugin _name="INCLUDE" 
				page="{$row.bobshopProductWikipage}"
				nopage_text="wikisite not found"
			}
			{/wikiplugin}
		{/if}
	{/if}
	
{/foreach}
<hr>
*}