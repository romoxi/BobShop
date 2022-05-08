{* $Id$ *}
{* prints a list of all products *}
{$divborder = ''}
{$divborder = 'border: solid 1px black;'}

{$showStructuresName = 'y'}

{* some nice style*}
<style type="text/css">
{literal}
	ul, #bs
	{
		list-style-type: none;
	}
	
	#bs
	{
		margin: 5px;
		padding: 3px;
	}
	
	.caret
	{
		border: 0px solid darkred;
		cursor: pointer;
		user-select: none;
	}

	.caret::before 
	{
	  content: "\25B6";
	  color: black;
	  display: inline-block;
	  margin-right: 5px;
	  padding-inline-start: 0px !important;
	}

	.caret-down::before 
	{
	  transform: rotate(90deg);
	}

	.nested 
	{
	  /*display: none;*/
	  padding: 5px;
	}

	.active 
	{
	  display: block;
	}
{/literal}	
</style>



{* functions *}
{function category}
	{foreach $data key=key item=entry}
		{if !$entry|is_array}
			<li class="caret">
				{$entry} {$categories.{$entry}.{$shopConfig['categoriesNameFieldId']}}
			</li>
		{/if}
		{if $entry|is_array}
			<ul class="nested">
				<li>{category data=$entry}</li>
			</ul>
		{/if}
	{/foreach}
{/function}


{* show the categories*} 
<div id="st0">
	{foreach $categoriesStructures key=$structureItemId item=$structureData}
		{if $showStructuresName == 'y'}
			<ul id="bs"><li><span class="caret">{$structureItemId} {$categoriesStructures.{$structureItemId}.{$shopConfig['categoriesStructuresNameFieldId']}}</span>
		{/if}
		{category data=$structureData categories=$categories shopConfig=$shopConfig }
			
		{if $showStructuresName == 'y'}
			</ul>
		{/if}
	{/foreach}
</div>







{foreach $categoriesStructures key=$structureItemId item=$structureData}
	{$structureData.{$shopConfig['categoriesStructuresNameFieldId']}}
	{**}
	{*$structureData.{$shopConfig['categoriesStructuresCategoriesIdsFieldId']}*}
	{foreach $structureData.{$shopConfig['categoriesStructuresCategoriesIdsFieldId']} item=$categoriesItemId}
		{**}
		<br>> {$categories.{$categoriesItemId}.{$shopConfig['categoriesNameFieldId']}}
	{/foreach}
		<br>
{/foreach}

{literal}
<script>
	var toggler = document.getElementsByClassName("caret");
	var i;

	for (i = 0; i < toggler.length; i++) 
	{
	  toggler[i].addEventListener("click", function() 
	  {
		this.parentElement.querySelector(".nested").classList.toggle("active");
		this.classList.toggle("caret-down");
	  });
	}
</script>
{/literal}