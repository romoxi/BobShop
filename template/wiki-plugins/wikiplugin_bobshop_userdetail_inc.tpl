{assign var=labels value=[
	'userTitle', 
	'userFirstName',
	'userName', 
	'userStreet', 
	'userStreetNumber', 
	'userZip', 
	'userCity', 
	'userPhone', 
	'userCountry' 
]}

<table width="80%" border="1px">
	{*
	<colgroup>
		<col width="30%">
		<col width="70%">
	</colgroup>
*}
	{* display the  *}	
	
	{foreach from=$userBobshop item=userData}
	{foreach from=$labels item=label}
		<tr>
			{*<td>{$label}</td>*}
			<td>{$userData.$label}</td>
		</tr>
	{/foreach}
	{/foreach}
</table>
