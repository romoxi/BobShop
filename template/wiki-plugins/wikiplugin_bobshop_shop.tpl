{* $Id$ *}
{$divborder = 'border: solid 0px black;'}
{foreach from=$products item=row}
	<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">
		<h2>{$row.bobshopProductName}</h2><hr>
	</a>
	
	{* image with link to detail *}
	<div style="{$divborder} margin-right: 10px; float: left;">
	<a href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">
		{wikiplugin _name="IMG" 
			fileId="{$row.bobshopProductPic1}"
			width="250px"
		}
		{/wikiplugin}
	</a>
	</div>
	<div style="{$divborder} ">
	<p>{$row.bobshopProductDescription|nl2br}</p>
	<p><b>Preis: {$row.bobshopProductPrice|string_format:"%.2f"}</b></p>
	<p>Artikelnr.: {$row.bobshopProductProductId}</p>
	
	<a class="btn btn-primary  mb-2" target="" data-role="button" data-inline="true" title="Details" 
	   href="tiki-index.php?page={$page}&action=shop_article_detail&productId={$row.bobshopProductProductId}">Details</a>
	</div>
	<div style="clear: both;"></div>
	   <hr>
{/foreach}
