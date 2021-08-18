{* $Id$ *}
{* prints a single product with it's details *}
{* container *}
{$variantsControl = 'y'}


{if $product.bobshopProductActive == 1 or $product.bobshopProductActive == 2}
	{if $product.bobshopProductDetailPage != 3}

		<div style="{$divborder}" class="row">

			{* Header *}
			<div class="col-sm-12">
				<h2>{$product.bobshopProductName}</h2>
				<h3>{$product.bobshopProductVariantName}</h3>
				
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
							<p id="productPrice" style="display: none;">{$price}</p>
							<h3 style="font-weight: bold;">{$price|string_format:"%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}</h3>
							<p class="small">{tr}incl.{/tr} {$shopConfig[$rate]}% MwSt.</p>
							<p class="small" onClick='javascript:$( "#wpdialog_bobshop_shipping" ).dialog( "open" );'>{tr}Shipping Category{/tr}: {$product.bobshopProductShippingCat}
							<br>{tr}plus Shipping Costs{/tr}</p>
						{/if}
						<p class="small">{tr}Part Number{/tr}: {$product.bobshopProductProductId}</p>
						
						{if $showPrices and $shopConfig.bobshopConfigStockControl eq "n"}
							<p>{tr}Lead Time{/tr}: {$product.bobshopProductDeliveryTime}</p>
						{/if}
						
							
						{* stock control*}
						{if $shopConfig.bobshopConfigStockControl eq "y"}
							<p>{tr}Lead Time{/tr}: 
							{if $product.bobshopProductStockQuantity > 0}
								{if 
									($product.bobshopProductStockWarning == 0 and $product.bobshopProductStockQuantity < $shopConfig.bobshopConfigStockWarning)
									or
									($product.bobshopProductStockWarning  > 0 and $product.bobshopProductStockQuantity < $product.bobshopProductStockWarning)
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

							{*if $shopConfig['bobshopConfigStockControl'] eq "y"}
							<p>Lagerbestand: {$product.bobshopProductStockQuantity}<p>
						{/if*}

						{* variants control with supersets *}
						{if $variantsControl == 'y' and $variants|@count gt 0}
							{if $variants != ''}
								<div style='border: 0px solid grey'>
									<b>{tr}Additional variants of this product{/tr}</b><br>
									<table class='table table-sm table-striped table-hover'>
										<thead class="">
											<tr>
												<th scope="col">{tr}Part-Nb{/tr}</th>
												<th scope="col">{tr}Description{/tr}</th>
												<th scope="col">{tr}Price{/tr}</th>
											</tr>
										</thead>
										<tbody>
											{foreach $variants key=$variantProductId item=$variantProduct}
												{$variantPrice = $variantProduct.bobshopProductPrice + $variantProduct.bobshopProductPrice/100 * $shopConfig[$rate]}
												<tr>
													<th scope="row">
														<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$variantProductId}">{$variantProductId}</a>
													</th>
													<td>
														<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$variantProductId}">{$variantProduct.bobshopProductVariantName}</a>
													</td>
													<td style="text-align: right;">
														{$variantPrice|string_format:"%.2f"} {$shopConfig['bobshopConfigCurrencySymbol']}
													</td>
												</tr>
											{/foreach}
										</tbody>
									</table>
								</div>
							{/if}
							<br>
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
				{*<p>{$product.bobshopProductDescription|nl2br}</p>*}
				{wiki}{$product.bobshopProductDescription}{/wiki}
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
	title="{tr}Shipping Costs{/tr}"
	width="400"
	modal="y"
	wiki="bobshop_shipping"
}{/wikiplugin}