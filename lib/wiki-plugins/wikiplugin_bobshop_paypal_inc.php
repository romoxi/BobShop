<?php
/**
 * BobShop PayPal extension
 * This Plugin is for CMS TikiWiki
 * 
 * BobShop is a shopping cart system for TikiWiki. 
 * 
 * Copyright (c) 2020 by Robert Hartmann
 * 
 * Install:
 * see https://github.com/romoxi/bobshop
 * 
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/**
 * Config Section 
 */

//login data from PayPayl REST API app
$clientId = 'uzDk';
$secret   = '7Yu8';

if($shopConfig['bobshopConfigOpMode'] == 'sandbox')
{
	$paypalURL = 'https://api-m.sandbox.paypal.com';
	// mit -m von schak
	//$paypalURL = 'https://api.sandbox.paypal.com';
}
elseif($shopConfig['bobshopConfigOpMode'] == 'default')
{
	$paypalURL = 'https://api-m.paypal.com';
	// mit -m von schak
	//$paypalURL = 'https://api.paypal.com';
}

//END Config Section

//echo 'Sie werden auf die PayPal-Seite weitergeleitet.';




function storeOrderDataPayPal($fieldId, $data, $shopConfig)
{
	global $tikilib;
	
	$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);

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
	//echo '<hr>'. $out .'<hr>';
	//print_r($out);
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

/**
 * 
 */
function getRequestStringPayPal($sums, $merchantName, $shopConfig)
{
	$paypalOrder = '{"intent": "CAPTURE", "purchase_units": [{"amount": 
					{
					"currency_code": "'. $shopConfig['bobshopConfigCurrencyShortcut'] .'",
					"value": "'. sprintf("%.2F", str_replace(",", ".", $sums['sumEnd'])).'"}
					}],
					"application_context":
					{
						"brand_name": "'. $merchantName .'",
						"landing_page": "LOGIN",
						"shipping_preference": "NO_SHIPPING",
						"user_action": "PAY_NOW",
						"return_url": "'. $_SERVER["SCRIPT_URI"] .'?page=bobshop_paypalAfterTransaction",
						"cancel_url": "'. $_SERVER["SCRIPT_URI"] .'?page=bobshop_paypalAfterTransaction"
					}										
				}';
	return $paypalOrder;
}

?>