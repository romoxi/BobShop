{* $Id$ *}
{* prints a single product with it's details *}
{* container *}

{if $product.bobshopProductActive == 1}
	{if $product.bobshopProductDetailPage != 3}

		<div style="{$divborder}" class="row">

			{* Header *}
			<div class="col-sm-12">
				<h2>{$product.bobshopProductName}</h2>
				{* 1 column *}
				<div style="{$divborder}" class="row">

					<div style="{$divborder}" class="col-sm-7">
						{* image with link to detail *}
						<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$product.bobshopProductProductId}">
							{if $product.bobshopProductPic1 != ''}
								{wikiplugin _name="IMG" 
									fileId="{$product.bobshopProductPic1}"
									width="100%"
									thumb="zoom"
								}
								{/wikiplugin}
							{else}
								{wikiplugin _name="IMG" 
									fileId="{$shopConfig['bobshopConfigProductPicMissingPic']}"
									width="250px"
								}
								{/wikiplugin}

							{/if}
						</a>
					</div>

					{* 2 column *}
					<div style="{$divborder}" class="col-sm-5 text-right">
						{assign var="rate" value="bobshopConfigTaxrate{$product.bobshopProductTaxrateCat}"}
						{if $showPrices}
							{$price = $product.bobshopProductPrice + $product.bobshopProductPrice/100 * $shopConfig[$rate]}
							<h3 style="font-weight: bold;">{$price|string_format:"%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</h3>
							<p class="small">inkl. {$shopConfig[$rate]}% MwSt.</p>
							<p class="small" onClick='javascript:$( "#wpdialog_bobshop_shipping" ).dialog( "open" );'>Versandkostenkategorie: {$product.bobshopProductShippingCat}
							<br>zzgl. Versandkosten</p>
						{/if}
						<p class="small">Artikelnr.: {$product.bobshopProductProductId}</p>
						{if $showPrices}
							<p>Lieferzeit: {$product.bobshopProductDeliveryTime}</p>
						{/if}

						{if $cart}
							{include file="templates/wiki-plugins/wikiplugin_bobshop_button_add.tpl"}
						{/if}
					</div>
				</div>
			</div>
		</div>

		{* Detail container *}
		<div style="{$divborder} margin-top: 20px;" class="row">
			<div style="{$divborder}" class="col-sm-12">
				<p>{$product.bobshopProductDescription|nl2br}</p>
			</div>
		</div>
	{/if}

	{* include the wikipage *}
	{if $product.bobshopProductWikipageName != '' && ($product.bobshopProductDetailPage == 1 || $product.bobshopProductDetailPage == 3 )}
		{*
		{wikiplugin _name="TRANSCLUDE" 
			page="{$product.bobshopProductWikipageName}"
			key1="test1"
			key2="test2"
		}
		hallo
		zwei
		
		{/wikiplugin}
		*}
		
		{wikiplugin _name="INCLUDE" 
			page="{$product.bobshopProductWikipageName}"
			nopage_text="wikisite not found"
			parse_included_page="y"
			
			max_inclusions="1"
		}
		{/wikiplugin}
	{/if}
				
{/if}
<hr>
<a class="btn btn-primary" target="" data-role="button" data-inline="true" title="Back" href="javascript:history.go(-1)">{tr}Back{/tr}</a>

{wikiplugin _name="DIALOG"
	autoOpen="n"
	id="wpdialog_bobshop_shipping"
	title="Versandkosten"
	width="400"
	modal="y"
	wiki="bobshop_shipping"
}{/wikiplugin}
