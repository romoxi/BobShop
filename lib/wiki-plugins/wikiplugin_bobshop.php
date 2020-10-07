<?php
function wikiplugin_bobshop_info()
{
    return array(
        'name' => tra('BobShop'),
        'description' => tra('Create a Shop.'),
        'prefs' => array( 'wikiplugin_bobshop' ),                        
        'params' => array(
                'type' => array(
                    'required' => false,
                    'name' => tra('Type'),
                    'description' => tra('What to do'),
                    'filter' => 'text',
                    'since' => '1.0',
                ),
                'productId' => array(
                    'required' => false,
                    'name' => tra('Product Number'),
                    'description' => tra('Product Number to be stored in the order'),
                    'filter' => 'text',
                    'since' => '1.0',
                ),
         ),
    );
}

/*
 * Main function of plugin
 * 
 *
 * 
 */
function wikiplugin_bobshop($data, $params) 
{	
    global $user;
	global $smarty;
	
	$output = '';
      
    extract($params, EXTR_SKIP);
	
	$shopConfig = get_tracker_shop_config();
	//print_r($shopConfig);
	
	$fieldNames['bobshopOrderSessionId'] = $_SESSION['__Laminas']['_VALID']['Laminas\Session\Validator\Id'];
	$shopConfig['currentOrderNumber'] = get_tracker_shop_orders_order_number_by_session_id($fieldNames['bobshopOrderSessionId'], $shopConfig);
	//print_r($_REQUEST);
	
	//echo $shopConfig['shopConfig_companySignature'];
	
	global $tikilib;
	global $userlib;
	
	//paypal test
	//paypalREST();
	
	/**
	 * Returns some detailed user info
	 * userId, email, login ...
	 */
	$userDataDetail = $userlib->get_user_info($user);
	//print_r($userDataDetail); echo '<hr>';

	/**
	 * Returns
	 * Array ( [usersTrackerId] => 11 [usersFieldId] => 31 [group] => Registered [user] => admin ) 
	 */
	$userData = $userlib->get_usertracker(1);
	//echo 'uid: '. $uid .'<br>';	print_r($userData);
	
	//print_r($shopConfig);
	
	//print_r($_SERVER);
	//echo $_SERVER['SCRIPT_URI'];
		
	/*
	 * checks if the "add to cart" button is pressed
	 * and if yes, then store the action a tracker
	 * 
	 */
	//if ($_SERVER['REQUEST_METHOD'] == 'POST') 
	if (isset($_REQUEST)) 
	{
		global $jitRequest;
		global $jitPost;
		global $jitGet;
		$action = $jitRequest->action->text();
	
		switch($action)
		{
			case 'quantityAdd':
				update_tracker_shop_order_items_quantity_add($jitGet->productId->text(), $shopConfig);
				break;
			
			case 'quantitySub':
				update_tracker_shop_order_items_quantity_Sub($jitGet->productId->text(), $shopConfig);
				break;
			
			case 'add_to_cart':
				$fieldNames['bobshopOrderUser'] = 'bobshop';
				$fieldNames['bobshopOrderItemQuantity'] = 1;

				//insert a new order, if there isnt one
				if(isset($_POST['productId']))
				{
					$fieldNames['bobshopOrderItemProductId'] = $jitPost->productId->text();
					$orderVars = insert_tracker_shop_order($fieldNames, $shopConfig);

					$fieldNames['bobshopOrderItemOrderNumber'] = $orderVars['orderNumber'];

					//insert the articel in the bobshop_order_items tracker
					insert_tracker_shop_order_items($fieldNames, $shopConfig);

					//show message
					$output .= message('Artikel wurde zum Warenkorb hinzugefügt.', '');
				}
				else
				{
					showError('productId not set');
				}
				
				break;
			
			case 'cashierbutton':
				if($user)
				{
					$action = 'cashierpage';
					//for mail testing
					//send_order_received($sums, $userDataDetail, $shopConfig);
				}
				else
				{
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_login.tpl');
				}
				break;
				
			case 'checkout':
				if($user)
				{
					$orderItems = get_tracker_shop_order_items($shopConfig);
					$order = get_tracker_shop_order_by_orderNumber($shopConfig);
					$payment = get_tracker_shop_payment($shopConfig);
					update_tracker_shop_payment($order, $jitRequest->payment->text(), $shopConfig);
					//revocation
					if($jitPost->revocation->text() != '')
					{
						update_tracker_shop_order_revocation(time(), $order, $shopConfig);
					}
					else
					{
						echo 'Widerruf nicht zugestimmt';
					}
					if($jitPost->tos->text() != '')
					{
						update_tracker_shop_order_tos(time(), $order, $shopConfig);
						update_tracker_shop_order_username($user, $shopConfig);
					}
					else
					{
						echo 'AGB nicht zugestimmt';
					}
					
					$params['type'] = 'checkout';
				}
				else
				{
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_login.tpl');
				}	
				
				break;
			
			case 'shop_article_detail':
				echo '<hr>';
				$products = get_tracker_shop_products_by_trackerID($shopConfig['productsTrackerId']);
				//print_r($products);
				$smarty->assign('products', $products);
				$smarty->assign('shopConfig', $shopConfig);
				$smarty->assign('productId', $jitRequest->productId->text());
				$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_shop_article_detail.tpl');
				$params['type'] = '';
				break;
			
			case 'order_submitted':
				$sumFields = array(
					'sumProducts',
					'sumTaxrate1',
					'sumTaxrate2',
					'sumTaxrate3',
					'sumTaxrates',
					'sumShipping',
					'sumPayment',
					'sumPaymentName',
					'sumEnd'
				);
				
				$sums = array();
				
				foreach($sumFields AS $value)
				{
					$sums[$value] = $jitPost->$value->text();
				}
				
				$order = get_tracker_shop_order_by_orderNumber($shopConfig);

				update_tracker_shop_order_submitted($sums, $order, $shopConfig);
				
				//send a order-received mail
				$output .= send_order_received($sums, $userDataDetail, $shopConfig);
				
				$payment = get_tracker_shop_payment($shopConfig);
				

				//if paypal is selected
				if($payment[$order['bobshopOrderPayment']][$shopConfig['paymentFollowUpScriptFieldId']] == 'PAYPAL')
				{
					include('wikiplugin_bobshop_paypal_inc.php');
					echo 'Sie werden auf die PayPal-Seite weitergeleitet.';
					
					//get the token
					$token = getTokenPayPal($clientId, $secret, $paypalURL);
					echo '<br>token: '. $token .'<hr>';
					//order json string to place the order
					if(empty($token))
					{
						echo '<hr>paypal error 100 - invailid token<hr>';
						exit;
					}
					else
					{
						$paypalOrder = '{"intent": "CAPTURE", "purchase_units": [{"amount": {"currency_code": "EUR","value": "'.$sums['sumEnd'].'"}}],
											"application_context":
											{
												"brand_name": "IMMERGIE - EuGen GmbH TEST",
												"landing_page": "LOGIN",
												"shipping_preference": "NO_SHIPPING",
												"user_action": "PAY_NOW",
												"return_url": "'. $_SERVER["SCRIPT_URI"] .'?page=bobshop_paypalAfterTransaction",
												"cancel_url": "'. $_SERVER["SCRIPT_URI"] .'?page=bobshop_paypalAfterTransaction"
											}										
										}';
						echo $paypalOrder;
						$orderPayPal = createOrderPayPal($paypalOrder, $token, $paypalURL);
					}
					
					//is the order created?
					if($orderPayPal['status'] != 'CREATED')
					{
						//echo '<hr>paypal error 101 - order not created<hr>';
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 101: '. $orderPayPal['status'], $shopConfig);
						$output .= message('Error', 'paypal error 101 - order not created', 'errors');
						update_tracker_shop_order_status(1, $shopConfig);
					}
					else
					{
						storeOrderDataPayPal('orderPaymentOrderIdFieldId', $orderPayPal['id'], $shopConfig);
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'CREATED', $shopConfig);
						$approveLink = getApproveLinkPayPal($orderPayPal);
						if($approveLink != false)
						{
							storeOrderDataPayPal('orderPaymenApproveLinkFieldId', $approveLink, $shopConfig);
							echo 'link: '. $approveLink;
							echo 'Sie werden zu PayPal weitergeleitet.';
							header("location: ". $approveLink);
						}
					}
				}
				break;
		}
	}

	
     
    switch($params['type'])
	{
		case 'show_shop':
			$products = get_tracker_shop_products_by_trackerID($shopConfig['productsTrackerId']);
			$smarty->assign('page', $jitRequest->page->text());
			$smarty->assign('products', $products);
			$smarty->assign('shopConfig', $shopConfig);
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_shop.tpl');
			break;

		//print the add to cart button
		case 'add_to_cart_button':
			$smarty->assign('productId', $params['productId']);
			$smarty->assign('add_to_cart_button', $shopConfig['shopConfig_add_to_cart_button_text']);
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_button_add.tpl');
			break;
		
		//show the cart
		case 'show_cart':
			$orderItems = get_tracker_shop_order_items($shopConfig);
			if(!empty($orderItems))
			{
				$smarty->assign('showQuantityModify', 1);
				$smarty->assign('status', 1);
				$smarty->assign('showPayment', 0);
				$smarty->assign('orderItems', $orderItems);
				$smarty->assign('shopConfig', $shopConfig);
				$smarty->assign('fieldNamesById', array_flip($shopConfig));
			}
			else
			{
				$smarty->assign('status', 0);
			}
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_cart.tpl');
			break;
		
		case 'show_cashier':
			if(isset($user))
			{
				//print_r($shopConfig);
				update_tracker_shop_order_username($user, $shopConfig);
				$userBobshop = get_tracker_shop_user_by_user($user, $shopConfig);
				$orderItems = get_tracker_shop_order_items($shopConfig);
				$order = get_tracker_shop_order_by_orderNumber($shopConfig);
				//if there ist no payment set, use the default
				if($order['bobshopOrderPayment'] == 0)
				{
					$order['bobshopOrderPayment'] = $shopConfig['shopConfig_paymentDefault'];
				}
				$payment = get_tracker_shop_payment($shopConfig);
				//print_r($payment);
				$smarty->assign('userBobshop', $userBobshop);
				$smarty->assign('showPayment', 1);
				$smarty->assign('payment', $payment);
				$smarty->assign('order', $order);
				$smarty->assign('user', $user);
				$smarty->assign('orderItems', $orderItems);
				$smarty->assign('shopConfig', $shopConfig);
				$smarty->assign('fieldNamesById', array_flip($shopConfig));
				$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_cashierpage.tpl');
			}
			break;
			
		case 'checkout':
			//print_r($shopConfig);
			if(isset($user))
			{
				$userBobshop = get_tracker_shop_user_by_user($user, $shopConfig);
				$orderItems = get_tracker_shop_order_items($shopConfig);
				//print_r($orderItems);
				$order = get_tracker_shop_order_by_orderNumber($shopConfig);
				//print_r($order);
				$payment = get_tracker_shop_payment($shopConfig);
				//print_r($payment);
				$smarty->assign('userBobshop', $userBobshop);
				$smarty->assign('payment', $payment);
				$smarty->assign('showPayment', 1);
				$smarty->assign('order', $order);
				$smarty->assign('user', $user);
				$smarty->assign('orderItems', $orderItems);
				$smarty->assign('shopConfig', $shopConfig);
				$smarty->assign('fieldNamesById', array_flip($shopConfig));
				$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_checkout.tpl');
			}
			break;
			
		case 'order_submitted':

			break;

		case 'paypal_after_transaction':
			include('wikiplugin_bobshop_paypal_inc.php');
			//GET vars: token => orderId; PayerId => the id from paypal (not used in our shop
			
			$orderIdResponse = $jitGet->token->text();
			
			//if the is no order in process ...
			if(empty($orderIdResponse))
			{
				header("location: index.php");
			}

			$token = getTokenPayPal($clientId, $secret, $paypalURL);
					
			//get some new info from the order
			$response = showOrderPayPal($orderIdResponse, $token, $paypalURL);

			if($response['status'] != 'APPROVED' && $response['status'] != 'COMPLETED')
			{
				//echo '<hr>paypal error 102 - order not approved. Status = '. $response['status'] .' orderId = '. $orderIdResponse .'<hr>';
				if($response['status'] == 'CREATED')
				{
					$output .= message('Abbruch', 'Der Bezahlvorgang wurde abgebrochen!', 'errors');
					update_tracker_shop_order_status(1, $shopConfig);
				}
				else
				{
					storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 102: '. $response['status'], $shopConfig);
					$output .= message('Error', 'paypal error 102 - order not approved. Status = '. $response['status'] .' orderId = '. $orderIdResponse, 'errors');
					update_tracker_shop_order_status(1, $shopConfig);
				}
			}
			else
			{
				if($response['status'] == 'APPROVED')
				{
					$payerIdResponse = $jitGet->PayerID->text();
					storeOrderDataPayPal('orderPaymentPayerIdFieldId', $payerIdResponse, $shopConfig);
					storeOrderDataPayPal('orderPaymentStatusFieldId', 'APPROVED', $shopConfig);

					//capture the payment
					$captureResponse = captureOrderPayPal($orderIdResponse, $token, $paypalURL);
					$response = showOrderPayPal($orderIdResponse, $token, $paypalURL);
				}
				if($response['status'] != 'COMPLETED')
				{
					//echo '<hr>paypal error 103 - order not completed. Status = '. $response['status'] .'<hr>';
					storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 103: '. $response['status'], $shopConfig);
					update_tracker_shop_order_status(1, $shopConfig);
					$output .= message('Error', 'paypal error 103 - order not completed. Status = '. $response['status'], 'errors');
					
				}
				else
				{
					storeOrderDataPayPal('orderPaymentPayeeMerchantIdFieldId', getMerchantIdPayPal($response), $shopConfig);
					storeOrderDataPayPal('orderPaymentStatusFieldId', 'COMPLETED', $shopConfig);
					//echo 'order completed';
					$smarty->assign('orderIdResponse', $orderIdResponse);
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_paypal_completed.tpl');
				}
			}

			break;		
	}
	
	return '~np~' . $output .'~/np~';
}


/**
 * read the configuration from the tracker
 * 
 * returns
 */
function get_tracker_shop_config()
{
    global $tikilib;

	//get the trackerIds
	$shopConfig['productsTrackerId']	= get_tracker_shop_trackerId('bobshop_products');
	$shopConfig['ordersTrackerId']		= get_tracker_shop_trackerId('bobshop_orders');
	$shopConfig['orderItemsTrackerId']	= get_tracker_shop_trackerId('bobshop_order_items');
	$shopConfig['paymentTrackerId']		= get_tracker_shop_trackerId('bobshop_payment');
	$shopConfig['userTrackerId']		= get_tracker_shop_trackerId('User');
	
	//this fields are in the tracker bobshop_order_items
	//fields in orderitems tracker
	$shopConfig['orderItemsFields'] = array(
		'orderItemOrderNumberFieldId'	=> 'bobshopOrderItemOrderNumber', 
		'orderItemProductIdFieldId'		=> 'bobshopOrderItemProductId', 
		'orderItemQuantityFieldId'		=> 'bobshopOrderItemQuantity' 
		);
	
	foreach($shopConfig['orderItemsFields'] as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['orderItemsTrackerId']);
	}
	
	//fields in orders tracker
	$shopConfig['ordersFields'] = array(
		'orderPaymentPayerIdFieldId'		=> 'bobshopOrderPaymentPayerId', 
		'orderPaymentPayeeMerchantIdFieldId'		=> 'bobshopOrderPaymentPayeeMerchantId', 
		'orderPaymentApproveLinkFieldId'	=> 'bobshopOrderPaymentApproveLink', 
		'orderPaymentStatusFieldId'			=> 'bobshopOrderPaymentStatus', 
		'orderPaymentOrderIdFieldId'		=> 'bobshopOrderPaymentOrderId', 
		'orderPaymentFieldId'				=> 'bobshopOrderPayment', 
		'orderSumProductsFieldId'		=> 'bobshopOrderSumProducts', 
		'orderSumTaxrate1FieldId'		=> 'bobshopOrderSumTaxrate1', 
		'orderSumTaxrate2FieldId'		=> 'bobshopOrderSumTaxrate2', 
		'orderSumTaxrate3FieldId'		=> 'bobshopOrderSumTaxrate3', 
		'orderSumTaxratesFieldId'		=> 'bobshopOrderSumTaxrates', 
		'orderSumShippingFieldId'		=> 'bobshopOrderSumShipping', 
		'orderSumPaymentNameFieldId'	=> 'bobshopOrderSumPaymentName', 
		'orderSumPaymentFieldId'	=> 'bobshopOrderSumPayment', 
		'orderSumEndFieldId'		=> 'bobshopOrderSumEnd', 
		'orderOrderNumberFieldId'	=> 'bobshopOrderOrderNumber', 
		'orderagreedTosDateFieldId'			=> 'bobshopOrderagreedTosDate', 
		'orderagreedRevocationDateFieldId'	=> 'bobshopOrderagreedRevocationDate', 
		'orderStatusFieldId'		=> 'bobshopOrderStatus', 
		'orderSessionIdFieldId'		=> 'bobshopOrderSessionId', 
		'orderUserFieldId'			=> 'bobshopOrderUser');
	
	foreach($shopConfig['ordersFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['ordersTrackerId']);
	}
	
	//fields in product tracker
	$shopConfig['productFields'] = array(
		'productMakerFieldId'			=> 'bobshopProductMaker', 
		'productEanFieldId'				=> 'bobshopProductEan', 
		'productDeliveryTimeFieldId'	=> 'bobshopProductDeliveryTime', 
		'productShippingCatFieldId'		=> 'bobshopProductShippingCat', 
		'productTaxrateCatFieldId'		=> 'bobshopProductTaxrateCat', 
		'productProductIdFieldId'		=> 'bobshopProductProductId', 
		'productDescriptionFieldId'		=> 'bobshopProductDescription', 
		'productNameFieldId'			=> 'bobshopProductName', 
		'productWikipageFieldId'		=> 'bobshopProductWikipage',
		'productPic1FieldId'			=> 'bobshopProductPic1',
		'productPriceFieldId'			=> 'bobshopProductPrice');
	
	foreach($shopConfig['productFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['productsTrackerId']);
	}
	
	//fields in payment tracker
	$shopConfig['paymentFields'] = array(
		'paymentFollowUpScriptFieldId'	=> 'bobshopPaymentFollowUpScript', 
		'paymentBuyNowButtonTextExtraTextFieldId'	=> 'bobshopPaymentBuyNowButtonTextExtraText', 
		'paymentMerchantIdFieldId'	=> 'bobshopPaymentMerchantId', 
		'paymentIconFieldId'	=> 'bobshopPaymentIcon', 
		'paymentNameFieldId'	=> 'bobshopPaymentName', 
		'paymentPriceFieldId'	=> 'bobshopPaymentPrice');
	
	foreach($shopConfig['paymentFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['paymentTrackerId']);
	}
	
	//fields in user tracker
	$shopConfig['userFields'] = array(
		'userUserFieldId'	=> 'userUser', 
		'userCityFieldId'	=> 'userCity', 
		'userStreetFieldId'	=> 'userStreet', 
		'userPhoneFieldId'	=> 'userPhone', 
		'userCountryFieldId'	=> 'userCountry', 
		'userZipFieldId'	=> 'userZip', 
		'userNameFieldId'	=> 'userName', 
		'userTitleFieldId'	=> 'userTitle', 
		'userFirstNameFieldId'		=> 'userFirstName');
	
	foreach($shopConfig['userFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['userTrackerId']);
	}
	
	//read the configs
	$result = $tikilib->fetchall(
			"SELECT 
				tiki_tracker_item_fields.itemId, 
				tiki_tracker_fields.name, 
				tiki_tracker_item_fields.value
			FROM 
				tiki_tracker_item_fields
			LEFT JOIN 
				tiki_tracker_fields ON tiki_tracker_fields.fieldId = tiki_tracker_item_fields.fieldId
			LEFT JOIN 
				tiki_trackers ON tiki_trackers.trackerId = tiki_tracker_fields.trackerId
			LEFT JOIN 
				tiki_tracker_items ON tiki_tracker_items.itemId = tiki_tracker_item_fields.itemId
			WHERE 
				tiki_trackers.name = ?
			",
            ["bobshop_config"]
                        
            );

	foreach($result as $row)
    {
		$shopConfig['shopConfigItemId'] = $row['itemId'];
        $shopConfig['shopConfig_'.$row['name']] = $row['value'];
    }
    return $shopConfig;
}

/*
 * get the trackerId by name 
 *
 * return the trackerId
 */
function get_tracker_shop_trackerId($trackerName)
{
	global $tikilib;

    $result = $tikilib->fetchall(
			"SELECT 
				tiki_trackers.trackerId
			FROM 
				tiki_trackers
			WHERE 
				tiki_trackers.name = ?
			",
            [$trackerName]
                        
            );

	$trackerId	= $result[0]['trackerId'];	//the trackerId from the tracker bobshop_orders
 
	if(empty($trackerId))
	{
		showError('trackerId not found - abort: '. $trackerName);
		return false;
	}	
    return $trackerId;	
}

/*
 * get the order_number by itemId 
 *
 * returns the order_number
 */
function get_tracker_shop_orders_order_number_by_itemId($itemId, $fieldId)
{
	global $tikilib;

    $result = $tikilib->fetchall(
			"SELECT 
				tiki_tracker_item_fields.value
			FROM 
				tiki_tracker_item_fields
			WHERE 
				tiki_tracker_item_fields.itemId = ?
			AND
				tiki_tracker_item_fields.fieldId = ?
			",
            [$itemId, $fieldId]
                        
            );
 
	$order_number	= $result[0]['value'];
    return $order_number;	
}

/**
 * get orders by session id, where the status=1 (cart in use)
 * 
 * @global type $tikilib
 * @param type $sessionId
 * @param type $shopConfig
 * @return type
 */
function get_tracker_shop_orders_order_number_by_session_id($sessionId, $shopConfig)
{
	global $tikilib;
	
	$result = $tikilib->query(
		"SELECT
				f3.itemId,
				f3.fieldId,
				f3.value
			FROM
				tiki_tracker_item_fields
			LEFT JOIN
				tiki_tracker_items ON tiki_tracker_items.itemId = tiki_tracker_item_fields.itemId
			LEFT JOIN
				tiki_tracker_item_fields AS f2 ON tiki_tracker_items.itemId = f2.itemId
			LEFT JOIN
				tiki_tracker_item_fields AS f3 ON tiki_tracker_items.itemId = f3.itemId
			WHERE
				tiki_tracker_item_fields.fieldId = ?
			AND
				tiki_tracker_item_fields.value = ?
			AND
				f2.fieldId = ?
			AND
				(f2.value = '1' OR f2.value = '0' OR f2.value = '')
		",
		[
			$shopConfig['orderSessionIdFieldId'], 
			$sessionId,
			$shopConfig['orderStatusFieldId'] 
			]
		);
	
	$res = $result->fetchRow();
	//echo '<hr>'; print_r($res);
	return $res['value'];
}


/*
 * get the tiki_tracker_fields.fieldId by the fieldname
 * returns the fieldId of a fieldname
 */
function get_tracker_shop_fieldId($fieldName, $trackerId)
{
	global $tikilib;

    $result = $tikilib->fetchall(
			"SELECT 
				tiki_tracker_fields.fieldId
			FROM 
				tiki_tracker_fields
			WHERE 
				tiki_tracker_fields.permName = ?
			AND 
				tiki_tracker_fields.trackerId = ?
			",
            [$fieldName, $trackerId]
                        
            );
    $item = $result[0]['fieldId'];
	
 	if(empty($item))
	{
		showError('fieldId not found - abort: '. $fieldName .' in trackerId: '. $trackerId);
		return false;
	}
	return $item;	
}


/**
 * Inserting some new stuff in a tracker
 * 4 tabels:
 * > trackers (trackerId, name)
 * > tracker_fields (fieldId, trackerId, name)
 * > tracker_item_fields (filedId, itemId, value) value=the content to store
 * > tracker_items (itemId, trackerId, status)
 * 
 *
 * 1.1. if the session_id already exist, return order_number
 * 1.2. lade die bisher höchste order_number
 * 3. insert the the new stuff in tracker_items (itemId will be autoincremented)
 * 4. get the last insertId (itemId)
 * 2. get the fieldId by the fieldname (fields 'sid', 'user')
 * 5. insert in tracker_item_fields
 * 6. inc trackers.items
 */
function insert_tracker_shop_order($fieldNames, $shopConfig)
{
	global $tikilib;
	
	//1.1 
	
	
	
	/*
	$result = $tikilib->query(
			"SELECT 
				tiki_tracker_item_fields.itemId
			FROM 
				tiki_tracker_item_fields
			LEFT JOIN
				tiki_tracker_item_fields AS f2 ON f2.itemId = tiki_tracker_item_fields.itemId
			WHERE 
				tiki_tracker_item_fields.value = ?
			AND
				tiki_tracker_item_fields.fieldId = ?
			AND
				f2.fieldId = ?
			AND
				(f2.value = '1' OR f2.value = '0' or f2.value = '')
				
			",[
				$fieldNames['bobshopOrderSessionId'], 
				$shopConfig['orderSessionIdFieldId'],
				$shopConfig['orderStatusFieldId'] 
			]
			);
	
	if($result->numrows > 0) 
	{
		$res = $result->fetchRow();
		$orderItemId = $res['itemId']; //the itemId of the order
		$ret['orderNumber'] = get_tracker_shop_orders_order_number_by_itemId($orderItemId, $shopConfig['orderOrderNumberFieldId']);
		return $ret;
	}
	*/
	
	
	
	
	$orderN = get_tracker_shop_orders_order_number_by_session_id($fieldNames['bobshopOrderSessionId'], $shopConfig);
	
	if($orderN > 0)
	{
		$ret['orderNumber'] = $orderN;
		return $ret;
	}
	
	//1.2.
	//get last (highest) order_number
	$result = $tikilib->query(
			"SELECT 
				MAX(CONVERT(value, SIGNED)) AS lastValue
			FROM 
				tiki_tracker_item_fields  
			LEFT JOIN 
				tiki_tracker_fields ON tiki_tracker_fields.fieldId = tiki_tracker_item_fields.fieldId
			WHERE 
				tiki_tracker_fields.permName = 'bobshopOrderOrderNumber'
			AND 
				tiki_tracker_fields.trackerId = ?
			", [$shopConfig['ordersTrackerId']]
			);
	$res = $result->fetchRow();
	$lastOrderNumber = $res['lastValue'];
	if(empty($lastOrderNumber))
	{
		showError('last_order_number not found - abort');
		return false;
	}	
	$fieldNames['bobshopOrderOrderNumber'] = $lastOrderNumber + 1;
	$ret['orderNumber'] = $fieldNames['bobshopOrderOrderNumber'];
	//print_r($ret);
	//die;
	//3.
	$result = $tikilib->query(
			"INSERT INTO 
				tiki_tracker_items
				(trackerId, status, created, createdBy, lastModif, lastModifBy)
			VALUES
				(?, ?, ?, ?, ?, ?)",
				[$shopConfig['ordersTrackerId'], 'o', time(), 'bobshop', time(), 'bobshop']
			);

	//4.
	$result = $tikilib->query("SELECT LAST_INSERT_ID()");
	$res = $result->fetchRow();
	$lastItemId = $res['LAST_INSERT_ID()'];
	
	if(empty($lastItemId))
	{
		showError('lastItemId not found - abort');
		return false;
	}
	
	foreach($shopConfig['ordersFields'] as $fieldNameId=>$fieldName)
	{
		$fieldId = $shopConfig[$fieldNameId];
		
		if(empty($fieldId))
		{
			showError('Field Name: '. $fieldName .' not found!');
			return false;
		}

		//5.
		$result = $tikilib->query(
			"INSERT INTO 
				tiki_tracker_item_fields
				(fieldId, itemId, value)
			VALUES
				(?, ?, ?)",
				[$fieldId, $lastItemId, $fieldNames[$fieldName]]
			);
	}
	//6.
	$result = $tikilib->query("UPDATE tiki_trackers SET items = items + 1 WHERE trackerId = ?",
			[$shopConfig['ordersTrackerId']]);
		
	$shopConfig['currentOrderNumber'] = $ret['orderNumber'];
	update_tracker_shop_order_status(1, $shopConfig);
	return $ret;
}

/**
 * 
 * 
 */
function insert_tracker_shop_order_items($fieldNames, $shopConfig)
{
	global $tikilib;

	//is the article_number already in the order?
	//if already exist, do not insert
	$itemId = check_is_productId_in_cart($fieldNames['bobshopOrderItemProductId'], $shopConfig);
	//echo '<hr>itemId: '. $itemId;
	if($itemId != false)
	{
		//echo 'scho drin<hr>';
		//update_tracker_shop_order_items_quantity_add($itemId, $fieldNames['bobshopOrderItemProductId'], $shopConfig);
		update_tracker_shop_order_items_quantity_add($fieldNames['bobshopOrderItemProductId'], $shopConfig);
		return;
	}
	
	//insert the new item
	$result = $tikilib->query(
			"INSERT INTO 
				tiki_tracker_items
				(trackerId, status, created, createdBy, lastModif, lastModifBy)
			VALUES
				(?, ?, ?, ?, ?, ?)",
				[$shopConfig['orderItemsTrackerId'], 'o', time(), 'bobshop', time(), 'bobshop']
			);
	
	//4.
	$result = $tikilib->query("SELECT LAST_INSERT_ID()");
	$res = $result->fetchRow();
	$lastItemId = $res['LAST_INSERT_ID()'];
	
	if(empty($lastItemId))
	{
		showError('lastItemId not found - abort');
		return false;
	}


	//this fields will be saved in the tracker
	foreach($shopConfig['orderItemsFields'] as $fieldNameId=>$fieldName)
	{
		$fieldId = $shopConfig[$fieldNameId];
		
		if(empty($fieldId))
		{
			showError('Field Name: '. $fieldName .' not found!');
			return false;
		}

		//5.
		$result = $tikilib->query(
			"INSERT INTO 
				tiki_tracker_item_fields
				(fieldId, itemId, value)
			VALUES
				(?, ?, ?)",
				[$fieldId, $lastItemId, $fieldNames[$fieldName]]
			);
	}
	
	//6.
	$result = $tikilib->query("UPDATE tiki_trackers SET items = items + 1 WHERE trackerId = ?",
			[$shopConfig['orderItemsTrackerId']]);
		
	return true;
}


/**
 * check if a articleNumber is already in the cart
 * 
 */
function check_is_productId_in_cart($productId, $shopConfig)
{
	$orderItems =  get_tracker_shop_order_items($shopConfig);
	
	if(empty($orderItems)) return false;
	foreach($orderItems as $itemId => $item)
	{
		if($item[$shopConfig['productProductIdFieldId']] == $productId)
		{
			return $itemId;
		}
	}
	return false;
}

/**
 * update cart
 * 
 * @global type $tikilib
 * @param type $articleNumber
 * @param type $shopConfig
 */
function update_tracker_shop_order_items_quantity_add($productId, $shopConfig)
{
	global $tikilib;
	//echo 'productId<hr>'. $productId;
	$product = get_tracker_shop_order_items($shopConfig, $productId);
	$itemId = array_key_first($product);
	//echo 'itemId: '. $itemId;
	//return;
	
	$result = $tikilib->query(
			"UPDATE 
				tiki_tracker_item_fields
			SET
				value = value + 1
			WHERE
				itemId = ?
			AND
				fieldId = ?
			", [$itemId, $shopConfig['orderItemQuantityFieldId']]
			);
	return;
}

/**
 * update cart
 * 
 * @global type $tikilib
 * @param type $articleNumber
 * @param type $shopConfig
 */
function update_tracker_shop_order_items_quantity_sub($productId, $shopConfig)
{
	global $tikilib;
	//echo 'productId<hr>'. $productId;
	$product = get_tracker_shop_order_items($shopConfig, $productId);
	$itemId = array_key_first($product);
	//echo 'itemId: '. $itemId;
	//return;
	
	$result = $tikilib->query(
			"UPDATE 
				tiki_tracker_item_fields
			SET
				value = value - 1
			WHERE
				value > 0
			AND
				itemId = ?
			AND
				fieldId = ?
			", [$itemId, $shopConfig['orderItemQuantityFieldId']]
			);
	return;
}


/**
 * get all products for one order
 * 
 * @return array with all products in that order
 */
function get_tracker_shop_order_items($shopConfig, $productId = false)
{
	global $tikilib;
	//first all products
	$result = $tikilib->query(
				"
				SELECT
					f3.fieldId AS quantityFieldId,
					f3.value AS quantitiy,
					f2.itemId,
					f2.fieldId,
					f2.value,
					product.value AS productName,
					product.fieldId AS productIdFieldId
				FROM
					tiki_tracker_item_fields
				LEFT JOIN
					tiki_tracker_item_fields AS f2 USING (itemId)
		
				LEFT JOIN
					tiki_tracker_item_fields AS p1 ON f2.value = p1.value
					AND
					f2.fieldId = ?
					AND
					p1.fieldId = ?
				LEFT JOIN
					tiki_tracker_item_fields AS product ON p1.itemId = product.itemId
				LEFT JOIN
					tiki_tracker_items ON product.itemId = tiki_tracker_items.itemId
					
				LEFT JOIN
					tiki_tracker_item_fields AS f3 ON f3.itemId = tiki_tracker_item_fields.itemId
					AND
					f3.fieldId = ?
				
				WHERE
					tiki_tracker_item_fields.fieldId = ?
				AND
					tiki_tracker_item_fields.value = ?
				AND	
					tiki_tracker_items.trackerId = ?
					
				ORDER BY f2.itemId ASC
			",[
				$shopConfig['orderItemProductIdFieldId'],
				$shopConfig['productProductIdFieldId'], 
				$shopConfig['orderItemQuantityFieldId'], 
				$shopConfig['orderItemOrderNumberFieldId'], 
				$shopConfig['currentOrderNumber'],
				$shopConfig['productsTrackerId']
			]);
	
	$orderItems = [];
	
	if($result->numrows > 0) 
	{
		while($row = $result->fetchRow())
		{
			//product params
			$orderItems[$row['itemId']][$row['productIdFieldId']] = $row['productName'];
			//quantity
			$orderItems[$row['itemId']][$row['quantityFieldId']] = $row['quantitiy'];
		}
		
		//only 1 product should be returned
		if($productId != false)
		{
			foreach($orderItems AS $item => $product)
			{
				if($product[$shopConfig['productProductIdFieldId']] == $productId)
				{
					$ret[$item] = $product;
					return $ret;
				}
			}
		}
		
		return $orderItems;
	}
}


/**
 * 
 * @global type $smarty
 * @param type $text
 * @return type
 */
function message($title, $text, $type = 'confirm')
{
	global $smarty;
	$smarty->assign('text', $text);
	$smarty->assign('title', $title);
	$smarty->assign('type', $type);
	return $smarty->fetch('wiki-plugins/wikiplugin_bobshop_message.tpl');
}

/*
 * print some errortext
 */
function showError($text)
{
	echo '<hr>';
		echo '<b>'. $text .'</b>';
	echo '<hr>';
	
}

/**
 * 
 */
function get_tracker_shop_products_by_trackerID($trackerId)
{
	global $tikilib;
	//first all products
	$result = $tikilib->query(
				"SELECT
					itemId,
					fieldId,
					value,
					name AS fieldName,
					permName
				FROM
					tiki_tracker_items
				LEFT JOIN
					tiki_tracker_item_fields USING (itemId)
				LEFT JOIN
					tiki_tracker_fields using (fieldId)
				WHERE
					tiki_tracker_items.trackerId = ?

				ORDER BY itemId ASC
			",
			[$trackerId]
			);
	
	$products = [];
	
	if($result->numrows > 0) 
	{
		while($row = $result->fetchRow())
		{
			$products[$row['itemId']][$row['permName']] = $row['value'];
		}
		return $products;
	}

}
/**
 * 
 */
function get_tracker_shop_user_by_user($user, $shopConfig)
{
	global $tikilib;

	$result = $tikilib->query(
				"SELECT
					f2.itemId,
					f2.fieldId,
					f2.value,
					name AS fieldName,
					permName
				FROM
					tiki_tracker_item_fields
				LEFT JOIN
					tiki_tracker_item_fields AS f2 ON f2.itemId = tiki_tracker_item_fields.itemId
				LEFT JOIN
					tiki_tracker_fields ON tiki_tracker_fields.fieldId = f2.fieldId
				WHERE
					tiki_tracker_item_fields.value = ?
				AND
					tiki_tracker_item_fields.fieldId = ?

				ORDER BY itemId ASC
			",
			[
				$user,
				$shopConfig['userUserFieldId']
				]
			);
	
	$userBobshop = [];
	
	if($result->numrows > 0) 
	{
		//echo 'show<hr>';
		while($row = $result->fetchRow())
		{
			//print_r($row); echo '<br>';
			$userBobshop[$row['permName']] = $row['value'];
			//$userBobshop[$row['itemId']][$row['permName']] = $row['value'];
		}
		$userBobshop[$row['itemId']] = $row['itemId'];

		return $userBobshop;
	}

}

/**
 * 
 */
function get_tracker_shop_order_by_orderNumber($shopConfig)
{
	global $tikilib;

	$result = $tikilib->query("
				SELECT
					f1.itemId,
					f1.fieldId,
					f1.value,
					permName
				FROM
					tiki_tracker_item_fields
				LEFT JOIN
					tiki_tracker_item_fields AS f1 ON f1.itemId = tiki_tracker_item_fields.itemId
				LEFT JOIN
					tiki_tracker_fields ON f1.fieldId = tiki_tracker_fields.fieldId
				WHERE
					tiki_tracker_item_fields.fieldId = ?
				AND
					tiki_tracker_item_fields.value = ?
					
			",
			[
			$shopConfig['orderOrderNumberFieldId'],
			$shopConfig['currentOrderNumber']
			]
			
			);

	while($row = $result->fetchRow())
	{
		$order[$row['permName']] = $row['value'];
		$order['itemId'] = $row['itemId'];
	}
	return $order;
}


/**
 * 
 */
function get_tracker_shop_payment($shopConfig)
{
	global $tikilib;
	
	$result = $tikilib->query("
				SELECT
					tiki_tracker_item_fields.itemId,
					tiki_tracker_item_fields.fieldId,
					tiki_tracker_item_fields.value,
					tiki_tracker_fields.permName,
					tiki_tracker_fields.fieldId
				FROM
					tiki_tracker_fields
				LEFT JOIN
					tiki_tracker_item_fields ON tiki_tracker_fields.fieldId = tiki_tracker_item_fields.fieldId
				
				WHERE
					tiki_tracker_fields.trackerId = ?
				ORDER BY itemId
			", [$shopConfig['paymentTrackerId']]
			);

	while($row = $result->fetchRow())
	{
		//echo '<hr>'. $row["permName"];print_r($row); echo '<br>';
		$payment[$row['itemId']][$row['fieldId']] = $row['value'];
	}
	
	return $payment;
}

function update_tracker_shop_payment($order, $payment, $shopConfig)
{
	global $tikilib;
	
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
				$payment,
				$order['itemId'], 
				$shopConfig['orderPaymentFieldId']]
			);
	return;	
	
}

function update_tracker_shop_order_submitted($sums, $order, $shopConfig)
{
	global $tikilib;
	
	foreach($sums as $key=>$value)
	{
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
					$value,
					$order['itemId'], 
					$shopConfig['order'.ucfirst($key).'FieldId']
					]
				);
	}
	//update_tracker_shop_order_status(2, $shopConfig);
	return;	
}

function update_tracker_shop_order_revocation($value, $order, $shopConfig)
{
	global $tikilib;
	
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
					$value,
					$order['itemId'], 
					$shopConfig['orderagreedRevocationDateFieldId']
					]
				);
	return;	
}

function update_tracker_shop_order_tos($value, $order, $shopConfig)
{
	global $tikilib;
	
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
					$value,
					$order['itemId'], 
					$shopConfig['orderagreedTosDateFieldId']
					]
				);
	return;	
}


/**
 * Update the status of the current order
 * 0 = not set; 
 * 1 = cart in use; 
 * 2 = order submitted; 
 * 3 = order confirmed; 
 * 4 = order payed; 
 * 5 = payed and shipped; 
 * 6 = not payed and shipped
 * 
 * @global type $tikilib
 * @param type $status
 * @param type $shopConfig
 * @return type
 */
function update_tracker_shop_order_status($status, $shopConfig)
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
				$status,
				$order['itemId'], 
				$shopConfig['orderStatusFieldId']
				]
			);
	return;	
}

function update_tracker_shop_order_username($user, $shopConfig)
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
				$user,
				$order['itemId'], 
				$shopConfig['orderUserFieldId']
				]
			);
	return;	
}

function send_order_received($sums, $userDataDetail, $shopConfig)
{
	//ToDo: check $sums
	global $tikilib;
	
	$smartmail = new Smarty;
	
	$mailReceiver = $userDataDetail['email'];
	
	$mailSender = $shopConfig['shopConfig_emailSender'];
	$shopname = $shopConfig['shopConfig_shopName'];
	$subject = $shopname .' Bestellbestätigung';
	

/*	//attachments?
	$doc = 'doccc';
	$id = md5(uniqid(time()));
	$doc = html_entity_decode($doc, ENT_COMPAT, 'UTF-8');
	$doc = utf8_encode($doc); 
	$dateiinhalt = $doc;
*/
	
	$smartmail->assign('userDataDetail', $userDataDetail);
	
	$userData = get_tracker_shop_user_by_user($userDataDetail['login'], $shopConfig);
	$orderItems = get_tracker_shop_order_items($shopConfig);
	$order = get_tracker_shop_order_by_orderNumber($shopConfig);
	$payment = get_tracker_shop_payment($shopConfig);

	$smartmail->assign('userBobshop', $userBobshop);
	$smartmail->assign('payment', $payment);
	$smartmail->assign('showPayment', 1);
	$smartmail->assign('orderItems', $orderItems);
	$smartmail->assign('userBobshop', $userData);
	$smartmail->assign('user', $user);
	$smartmail->assign('order', $order);
	$smartmail->assign('shopConfig', $shopConfig);
	$smartmail->assign('fieldNamesById', array_flip($shopConfig));
	
	$mailText = $smartmail->fetch('wiki-plugins/wikiplugin_bobshop_mail_order_received.tpl');
	 
	
	$header = "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: text/html; charset=utf-8\r\n";
	$header .= "To: ". $userDataDetail['login'] ."<". $mailSender . ">\r\n";
	$header .= "From: ". $shopname ."<". $mailSender . ">\r\n";
	$header .= "Bcc: ". $shopname ."<". $mailBcc . ">\r\n";
	$header .= "Reply-To: ". $shopname ."<". $mailSender . ">\r\n";
	
	//$anhang = chunk_split(base64_encode($dateiinhalt)); 
	//$header .= "Content-Type: multipart/mixed; boundary=".$id."\n";
	//$header .= "This is a multi-part message in MIME format\n";
	//$header .= "--".$id."\n";
	//$header .= "Content-Transfer-Encoding: 8bit\n";
	//$header .= $mailText."\n";
	//$header .= "--".$id."\n";
	//$kopf .= "Content-Type: text/txt; name=\"".$dateiname_mail."\"\n";
	//$header .= "Content-Transfer-Encoding: base64\n";
	//$kopf .= "Content-Disposition: attachment; filename=\"".$dateiname_mail."\"\n\n";
	//$kopf .= $anhang."\n";
	//$header .= "--".$id."--\r\n\n";

	//echo $header;
	
	$mail_send = mail($mailReceiver, $subject, $mailText, $header);

	if ($mail_send)
	{
		$output .= message('Info', 'Bestätigungsmail wurde versendet.');
		
		//send mail to company
		$header = "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: text/html; charset=utf-8\r\n";
		$header .= "From: ". $shopname ."<". $mailSender . ">\r\n";
		$header .= "To: ". $shopname ." - Info Bestelleingang<". $shopConfig['shopConfig_emailNotifications'] . ">\r\n";
		$mail_send = mail($mailReceiver, $subject, $mailText, $header);
		
	}
	else
	{
		$output .= message('Error', 'Mail not sent!', 'errors');
	}	
	
	return $output;
}