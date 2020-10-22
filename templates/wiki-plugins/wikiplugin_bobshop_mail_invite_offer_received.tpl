{* $Id$ *}

<h1>Angebotsanfrage</h1>

<p>Hallo {$userDataDetail['login']},<p>
	<br>
<p>vielen Dank für Deine Anfrage.</p>
<br>
<p>Hiermit bestätigen wir, dass Deine Anfrage bei uns angekommen ist. </p>
<br>

<p>Hier nochmal die Anfrage im Überblick:</p>


<h2>Angebotsempfänger:</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_userdetail_inc.tpl" scope="global"}


<h2>Angefragte Produkte:</h2>
{include file="templates/wiki-plugins/wikiplugin_bobshop_cartdetail_inc.tpl" scope="global"}

<br>
<h2>Rechtliches Zeug</h2>
<p>Widerrufs- und Rückgaberecht mit Link auf Website oder als Anhang PDF?</p>
<br>
<h2>Kontaktinfo oder Verkäufer oder Händler</h2>
{$shopConfig.bobshopConfigCompanySignature|nl2br}

