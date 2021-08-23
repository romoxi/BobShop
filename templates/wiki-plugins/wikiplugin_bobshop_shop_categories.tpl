{* $Id$ *}
{* prints a list of all products *}
{$divborder = ''}
{$divborder = 'border: solid 1px black;'}

{function category level=0}
	{foreach $data key=itemId item=entry}
		{$level} {$entry.{$shopConfig['categoryNameFieldId']}}
		<br>
		{if $entry.subset|@count gt 0}
			{category data=$entry.subset level=$level + 1}
		{/if}
	{/foreach}
{/function}

{* show the categories*} 
{category data=$categories}