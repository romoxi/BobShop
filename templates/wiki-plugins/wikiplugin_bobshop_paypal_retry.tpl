{* $Id$ *}
{* display a button to retry the paypal stuff *}
<div class="alert alert-danger alert-dismissable">
	<div class="alert-heading h4">
		<span class="icon icon-error fas fa-exclamation-circle "></span>
		<span class="rboxtitle">
			{tr}An error occurred by paying with PayPal!{/tr}
		</span>
	</div>
	<div class="rbboxcontent" style="display: inline">
		<p>{tr}Do you want to try it again?{/tr}</p>
		{* yes *}
		<form method="post" action="{query _type=relative _keepall=y}">
			<div class="input-group col-xs-4">
				<input type="hidden" name="action" value="paypal_retry">
				<input type="submit" class="btn btn-primary" value="{tr}Pay with Paypal{/tr}">
			</div>
		</form>
		{* no *}
		<form method="post" action="{query _type=relative _keepall=y}">
			<div class="input-group col-xs-4">
				<input type="hidden" name="action" value="paypal_retry_no">
				<input type="submit" class="btn btn-primary" value="{tr}No{/tr}">
			</div>
		</form>
	</div>
</div>