{* $Id$ *}

<h1>Angebotsanfrage</h1>
<p>Hallo {$userDataDetail['login']},<p>
<br>
<p>vielen Dank für Ihre Anfrage.</p>
<br>
<p>Hiermit bestätigen wir, dass Ihre Anfrage bei uns angekommen ist. </p>
<br>
<p>Hier nochmal die Anfrage im Überblick:</p>
<br>
<b>Angebotsempfänger:</b><br>
<br>
{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}
<br>
<br><b>Angefragte Produkte:</b><br>
<br>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}
<br>
<br>
Mit freundlichen Grüßen von Ihrem Kundenservice
<br>
<br>
{$shopConfig.bobshopConfigCompanySignature|nl2br}
<br>
<br>
<hr>
{if $shopConfig.bobshopConfigTermsOfServicePage != ''}
	<br>
	<br><b>Allgemeine Geschäftsbedingungen und Widerrufsbelehrung</b><br>
	<br>
	{wikiplugin _name="INCLUDE" 
		page="{$shopConfig.bobshopConfigTermsOfServicePage}"
		nopage_text="Text konnte nicht geladen werden. Bitte wenden Sie sich an unseren Kundenservice."
		parse_included_page="n"
		max_inclusions="1"
	}{/wikiplugin}
{/if}
	
{if $shopConfig.bobshopConfigRevocationNotice != ''}
	<br>
	<br><b>Widerrufsbelehrung</b><br>
	<br>
	{wikiplugin _name="INCLUDE" 
		page="{$shopConfig.bobshopConfigRevocationNotice}"
		nopage_text="Text konnte nicht geladen werden. Bitte wenden Sie sich an unseren Kundenservice."
		parse_included_page="n"
		max_inclusions="1"
	}{/wikiplugin}
{/if}

