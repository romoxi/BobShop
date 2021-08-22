{* display the User Data *}	

{if $shopConfig['bobshopConfigTikiUserRegistration'] == 'y'} 
	<table width="80%" border="0px">
		<tr>
			<td>{$userBobshop.userTitle}</td>
		</tr>
		<tr>
			<td>{$userBobshop.userFirstName} {$userBobshop.userName}</td>
		</tr>
		<tr>
			<td>{$userBobshop.userStreet}</td>
		</tr>
		<tr>
			<td>{$userBobshop.userZip} {$userBobshop.userCity}</td>
		</tr>
		<tr>
			<td>{$userBobshop.userCountry}</td>
		</tr>
		<tr>
			<td>{$userBobshop.userPhone}</td>
		</tr>
		<tr>
	</table>
{/if}

{* for the bobshop own user system *}
{if $shopConfig['bobshopConfigTikiUserRegistration'] == 'n'} 
	<table class="table">
		<colgroup>
			<col width='20%'>
			<col width='80%'>
		</colgroup>
		
		{foreach from=$bobshopOrderBobshopUser key=key item=value}
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
{/if}