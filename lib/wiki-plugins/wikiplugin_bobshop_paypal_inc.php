<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Config Section 
 */

//login data from PayPayl REST API app
$clientId = '';
$secret   = '';

if($shopConfig['shopConfig_opMode'] == 'sandbox')
{
	$paypalURL = 'https://api.sandbox.paypal.com';
}
elseif($shopConfig['shopConfig_opMode'] == 'default')
{
	$paypalURL = 'https://api.paypal.com';
}

//END Config Section



function storeOrderDataPayPal($fieldId, $data, $shopConfig)
{
	global $tikilib;
	
	$order = get_tracker_shop_order_by_orderNumber($shopConfig);

	$result = $tikilib->query(
			"UPDATE 
				tiki_tracker_item_fields
			SET
				value = ?
			WHERE
				itemId = ?
			AND
				fieldId = ?
			", [
				$data,
				$order['itemId'], 
				$shopConfig[$fieldId]
				]
			);
	return;		
}

/**
 * Returns the token for further communication
 * 
 * @param type $clientId
 * @param type $secret
 * @return type
 */
function getTokenPayPal($clientId, $secret, $paypalURL)
{
	$header[0] = "Content-Type: application/json";
	$header[1] = "Accept-Language: en_US";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $paypalURL ."/v1/oauth2/token"); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_USERPWD, $clientId .":". $secret);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);      	

	$out = json_decode($output, true);
		
	return $out['access_token'];
}

function getMerchantIdPayPal($response)
{
	return $response['purchase_units'][0]['payee']['merchant_id'];
}

function createOrderPayPal($order, $token, $paypalURL)
{
	$header[0] = "Content-Type: application/json";
	$header[1] = "Authorization: Bearer ". $token;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $paypalURL ."/v2/checkout/orders"); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	//curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $order);
	
	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);      	

	$out = json_decode($output, true);

	return $out;
}

function showOrderPayPal($orderId, $token, $paypalURL)
{
	$header[0] = "Content-Type: application/json";
	$header[1] = "Authorization: Bearer ". $token;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $paypalURL ."/v2/checkout/orders/". $orderId); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	//curl_setopt($ch, CURLOPT_FAILONERROR, true);
	
	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);      	

	$out = json_decode($output, true);

	return $out;
}

function captureOrderPayPal($orderId, $token, $paypalURL)
{
	$header[0] = "Content-Type: application/json";
	$header[1] = "Authorization: Bearer ". $token;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $paypalURL ."/v2/checkout/orders/". $orderId ."/capture"); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	//curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, '');
	
	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);      	

	$out = json_decode($output, true);

	return $out;
}


/**
 * Return the approve link for the buyer to approve the order
 * @param type $response
 * @return type
 */
function getApproveLinkPayPal($response)
{
	$href = false;
	foreach($response['links'] AS $links)
	{
		if($links['rel'] == 'approve')
		{
			$href = $links['href'];
		}
	}
	return $href;
}

?>