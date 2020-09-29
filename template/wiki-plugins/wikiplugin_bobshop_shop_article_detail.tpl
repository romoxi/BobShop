{* $Id$ *}
<h3>Produktdetails</h3>
{foreach from=$products item=row}
	{if $row.bobshopProductProductId == $productId}
	
		<h2>{$row.bobshopProductName}</h2>

		{wikiplugin _name="IMG" 
			fileId="{$row.bobshopProductPic1}"
			width="250px"
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
		
		{* include the wikipage *}
		{if $row.bobshopProductWikipage != ''}
			<hr>
			{wikiplugin _name="INCLUDE" 
				page="{$row.bobshopProductWikipage}"
				nopage_text="wikisite not found"
			}
			{/wikiplugin}
		{/if}
	{/if}
	
	<hr>
{/foreach}
<a class="btn btn-primary  mb-2" target="" data-role="button" data-inline="true" title="Back" 
	   href="javascript:history.go(-1)">Back</a>
