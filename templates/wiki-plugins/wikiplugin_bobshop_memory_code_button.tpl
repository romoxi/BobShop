{* $Id$ *}
{* needs $shopConfig and $orderItems *}
{* this template is best to be included by the cart *}
{if $shopConfig.bobshopConfigMemoryOrders == 'y'}
	<p><b>{tr}Load or Save the Cart{/tr}</b></p>
	<div class="form-group row">
		{* input memory code an load *}
		<form method="post" action="{query _type=relative _keepall=y}">
			<div class="input-group col-xs-4">
				<input type="text" name="memory_code" placeholder="Code" class="form-control">
				<input type="hidden" name="action" value="load_order">
				<input type="submit" class="btn btn-primary" value="{tr}Load Cart{/tr}">
			</div>
		</form>
		{* save order *}
		{if !empty($orderItems)}
			<div>&nbsp;</div>
			<form method="post" action="{query _type=relative _keepall=y}">
				<input type="hidden" name="action" value="save_order">
				<input type="submit" class="btn btn-primary" value="{tr}Save Cart{/tr}">
			</form>
		{/if}
	</div>
{/if}
