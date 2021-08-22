{* $ID$ *}
{* template for the admin site *}

<h1>{tr}BobShop Administration{/tr}</h1>

{* show all carts *}
<h3>{tr}load all carts{/tr}</h3>
<div style="width: 300px">
	<form method="post" action="{query _type=relative _keepall=y}">
		<div class="input-group col-xs-4">
			<input type="hidden" name="action" value="admin_show_orders">
			<input type="hidden" name="status" value="all">
			<input type="submit" class="btn btn-primary" value="{tr}load all carts{/tr}">
		</div>
	</form>
</div>
		
{* show all orders *}
<h3>{tr}load all orders{/tr}</h3>
<div style="width: 300px">
	<form method="post" action="{query _type=relative _keepall=y}">
		<div class="input-group col-xs-4">
			<input type="hidden" name="action" value="admin_show_orders">
			<input type="hidden" name="status" value="submitted">
			<input type="submit" class="btn btn-primary" value="{tr}load all submitted orders{/tr}">
		</div>
	</form>
</div>
		
{* load by orderNumber *}
<h3>{tr}load order by orderNumber{/tr}</h3>
<div style="width: 300px">
	<form method="post" action="{query _type=relative _keepall=y}">
		<div class="input-group col-xs-4">
			<input type="text" name="orderNumber" placeholder="orderNumber" class="form-control">
			<input type="hidden" name="action" value="admin_show_order">
			<input type="submit" class="btn btn-primary" value="{tr}load order{/tr}">
		</div>
	</form>
</div>

<br>

{* Fancytable order list *}
{if $action == 'admin_show_orders'}
	{wikiplugin _name="FANCYTABLE"
		head=" |{$tableHead}"
		sortable="type:reset" 
		sortList="[0,n],[1,1]" 
		tsfilters="type: nofilter|y"
		tsfilteroptions="type:reset"
		tspaginate="y"
	}
		{foreach $orders key=orderItemId item=order}
			{$xi = 0}
			{capture row}
				{foreach $tableFields item=$fieldName}
					{assign var="field" value="bobshopOrder{$fieldName}"}
					{if $xi == 0}
						{*<a href="tiki-index.php?page=bobshop_admin&action=admin_show_order&orderId={$orderItemId}">go</a>*}
						<a href="tiki-index.php?page=bobshop_admin&action=admin_show_order&orderNumber={$order.bobshopOrderOrderNumber}">go</a>|
					{/if}
						
					{$order.$field}
					{$xi = $xi + 1}
					{if $xi < $tableFields|@count}|{/if}
				{/foreach}
			{/capture}
			{assign var=row value=$smarty.capture.row|regex_replace:"/[\r\n]/" : ""}
			{*assign var=row value=$row|substr:0:-5*}{*do not work*}
			{$row}
		{/foreach}
	{/wikiplugin}
{/if}

{* detail of one order with cart *}
{if $action == 'admin_show_order'}
	{*foreach $order key=$key item=$item}
		key: {$key} - item: {$item} <br>
	{/foreach*}
		<div style="word-wrap: break-word; overflow-wrap: break-word; width:100%;">
			<table style="table-layout: fixed;" class="table table-striped table-hover">
				<colgroup>
					<col width='30%'>
					<col width='70%'>
				</colgroup>

				{foreach from=$order key=key item=value}
					<tr>
						<td>
							{tr}{$key}{/tr}		
						</td>
						<td>
							{$value}		
						</td>
					</tr>
				{/foreach}
			</table>		
		</div>

	{* include the cart*}
	{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}	

{/if}

{**}
{**}
{**}
{**}