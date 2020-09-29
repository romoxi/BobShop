<?php
function wikiplugin_bobshop_info()
{
    return array(
        'name' => tra('BobShop'),
        'description' => tra('Create a Shop.'),
        'prefs' => array( 'wikiplugin_bobshop' ),                        
        'params' => array(/*
                'shop_name' => array(
                    'required' => true,
                    'name' => tra('BobShop'),
                    'description' => tra('Choose a name'),
                    'filter' => 'text',
                    'since' => '1.0',
                ),
                'config' => array(
                    'required' => false,
                    'name' => tra('Configuration'),
                    'description' => tra('Loads the configration site'),
                    'filter' => 'text',
                    'since' => '1.0',
                ),*/
                'type' => array(
                    'required' => false,
                    'name' => tra('Type'),
                    'description' => tra('What to do'),
                    'filter' => 'text',
                    'since' => '1.0',
                )
			,
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
    //print_r($params);    print_r($data);
	
	$shopConfig = get_tracker_shop_config();
	//print_r($shopConfig);
	
	$fieldNames['bobshopOrderSessionId'] = $_SESSION['__Laminas']['_VALID']['Laminas\Session\Validator\Id'];
	$shopConfig['currentOrderNumber'] = get_tracker_shop_orders_order_number_by_session_id($fieldNames['bobshopOrderSessionId'], $shopConfig);
	echo '<hr>request: '. $fieldNames['bobshopOrderSessionId']; print_r($_REQUEST);
		
	
	global $tikilib;
		
	global $userlib;
		
	/**
	 * Returns some detailed user info
	 * userId, email, login ...
	 */
	$userDataDetail = $userlib->get_user_info('admin');
	//print_r($userData); echo '<hr>';

	/**
	 * Returns
	 * Array ( [usersTrackerId] => 11 [usersFieldId] => 31 [group] => Registered [user] => admin ) 
	 */
	$userData = $userlib->get_usertracker(1);
	//echo 'uid: '. $uid .'<br>';	print_r($userData);
	
	//print_r($shopConfig);
	
		
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
		$action = $jitRequest->action->text();
		//print_r($jitPost); echo '<hr>';
		
	
		switch($action)
		{
			case 'add_to_cart':
				$fieldNames['bobshopOrderUser'] = 'bobshop';
				//$fieldNames['bobshopOrderOrderNumber'] = 0;
				$fieldNames['bobshopOrderItemQuantity'] = 1;

				//insert a new order, if there isnt one
				if(isset($_POST['productId']))
				{
					$fieldNames['bobshopOrderItemProductId'] = $jitPost->productId->text();
					$orderVars = insert_tracker_shop_order($fieldNames, $shopConfig);

					//print_r($orderVars);
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
					'sumEnd'
				);
				
				$sums = array();
				
				foreach($sumFields AS $value)
				{
					$sums[$value] = $jitPost->$value->text();
				}
				
				$order = get_tracker_shop_order_by_orderNumber($shopConfig);
				update_tracker_shop_order_submitted($sums, $order, $shopConfig);
				
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
				$userBobshop = get_tracker_shop_user_by_userId($user, $shopConfig);
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
			if(isset($user))
			{
				$userBobshop = get_tracker_shop_user_by_userId($user, $shopConfig);
				$orderItems = get_tracker_shop_order_items($shopConfig);
				//print_r($orderItems);
				$order = get_tracker_shop_order_by_orderNumber($shopConfig);
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
				$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_checkout.tpl');
			}
			break;
			
		case 'order_submitted':

			break;
	}
	
	return '~np~' . $output .'~/np~';
}


/**
 * read the configuration from the tracker
 * 
 * returns
 * - trackerId bobshop_orders [ordersTrackerId]
 * - trackerId bobshop_order_items [orderItemsTrackerId]
 * - fieldId
 * -- order_number (bobshop_orders) [ordersOrder_number]
 * -- session_id [session_id]
 * -- user [ordersUser]
 * -- order_number (bobshop_order_items) [orderItemsOrder_number]
 * -- article_number [article_number]
 * -- quantity (bobshop_order_items) [quantity]
 * 
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
		'orderItemProductIdFieldId'	=> 'bobshopOrderItemProductId', 
		'orderItemQuantityFieldId'		=> 'bobshopOrderItemQuantity' 
		);
	
	foreach($shopConfig['orderItemsFields'] as $key => $name)
	{
		//echo 'name: '. $name .' tracker: '. $trackerId .'<hr>';
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['orderItemsTrackerId']);
	}
	
	//fields in orders tracker
	$shopConfig['ordersFields'] = array(
		'orderSumProductsFieldId'		=> 'bobshopOrderSumProducts', 
		'orderSumTaxrate1FieldId'	=> 'bobshopOrderSumTaxrate1', 
		'orderSumTaxrate2FieldId'	=> 'bobshopOrderSumTaxrate2', 
		'orderSumTaxrate3FieldId'	=> 'bobshopOrderSumTaxrate3', 
		'orderSumTaxratesFieldId'	=> 'bobshopOrderSumTaxrates', 
		'orderSumShippingFieldId'	=> 'bobshopOrderSumShipping', 
		'orderSumPaymentFieldId'	=> 'bobshopOrderSumPayment', 
		'orderSumEndFieldId'		=> 'bobshopOrderSumEnd', 
		'orderPaymentFieldId'		=> 'bobshopOrderPayment', 
		'orderOrderNumberFieldId'	=> 'bobshopOrderOrderNumber', 
		'orderStatusFieldId'		=> 'bobshopOrderStatus', 
		'orderSessionIdFieldId'		=> 'bobshopOrderSessionId', 
		'orderUserFieldId'			=> 'bobshopOrderUser');
	
	foreach($shopConfig['ordersFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['ordersTrackerId']);
	}
	
	//fields in product tracker
	$shopConfig['productFields'] = array(
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
		'userStreetNumberFieldId'	=> 'userStreetNumber', 
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
        //print_r($row); echo '<hr>';
		$shopConfig['shopConfigItemId'] = $row['itemId'];
        $shopConfig['shopConfig_'.$row['name']] = $row['value'];
    }
	//print_r($shopConfig);    
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
				(f2.value = '1' OR f2.value = '0')
		",
		[
			$shopConfig['orderSessionIdFieldId'], 
			$sessionId,
			$shopConfig['orderStatusFieldId'] 
			]
		);
	
	$res = $result->fetchRow();
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
    //print_r($result);
    $item = $result[0]['fieldId'];
	
 	if(empty($item))
	{
		showError('fieldId not found - abort: '. $fieldName .' in trackerId: '. $trackerId);
		return false;
	}
	return $item;	
}


/**
 * write 
 * update_tracker_shop_config("shop_name", 5, "Shop 24");
 */
/*
function update_tracker_shop_config($fieldName = "shop_name", $itemId = 5, $value = "Shop 200")
{
    global $tikilib;
 
    $result = $tikilib->query(
			"UPDATE tiki_tracker_item_fields
			LEFT JOIN tiki_tracker_fields ON tiki_tracker_fields.fieldId = tiki_tracker_item_fields.fieldId
			LEFT JOIN tiki_trackers ON tiki_trackers.trackerId = tiki_tracker_fields.trackerId
			LEFT JOIN tiki_tracker_items ON tiki_tracker_items.itemId = tiki_tracker_item_fields.itemId

			SET tiki_tracker_item_fields.value = ?

			WHERE tiki_trackers.name = ? AND
			tiki_tracker_fields.name = ? AND
			tiki_tracker_item_fields.itemId = ?",
			[$value, "bobshop_config", $fieldName, $itemId]
		);
}
*/

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
	//shopConfig['ordersTrackerId']
	$result = $tikilib->query(
			"SELECT 
				tiki_tracker_item_fields.itemId
			FROM 
				tiki_tracker_item_fields
			WHERE 
				tiki_tracker_item_fields.value = ?
			AND
				tiki_tracker_item_fields.fieldId = ?
			",[$fieldNames['bobshopOrderSessionId'], $shopConfig['orderSessionIdFieldId']]
			);
	
	//echo '<hr>rows: '. $result->numrows;
	if($result->numrows > 0) 
	{
		$res = $result->fetchRow();
		$orderItemId = $res['itemId']; //the itemId of the order
		$ret['orderNumber'] = get_tracker_shop_orders_order_number_by_itemId($orderItemId, $shopConfig['orderOrderNumberFieldId']);
		return $ret;
	}
	
	//1.2.
	//get last (highest) order_number
	$result = $tikilib->query(
			"SELECT 
				MAX(value) 
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
	//print_r($res);
	$lastOrderNumber = $res['MAX(value)'];
	if(empty($lastOrderNumber))
	{
		showError('last_order_number not found - abort');
		return false;
	}	
	$fieldNames['bobshopOrderOrderNumber'] = $lastOrderNumber + 1;
	$ret['orderNumber'] = $fieldNames['bobshopOrderOrderNumber'];
	
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
	//echo 'last: ';print_r($row);
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
		echo 'scho drin<hr>';
		update_tracker_shop_order_items_quantity($itemId, $fieldNames['bobshopOrderItemProductId'], $shopConfig);
		return;
	}
	
	//echo '<hr>ff'; print_r($fieldNames);
	//echo '<hr>sc'; print_r($shopConfig);
	//return;	
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
	//echo '<hr>oi: ';print_r($orderItems);
	//echo '<hr>sc: ';print_r($shopConfig);
	
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
function update_tracker_shop_order_items_quantity($itemId, $productId, $shopConfig)
{
	global $tikilib;
	
	//print_r($item);
	echo 'itemId: '. $itemId . ' - '. $productId .' > ';
	
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
 * get all products for one order
 * 
 * @return array with all products in that order
 */
function get_tracker_shop_order_items($shopConfig)
{
	global $tikilib;
	//print_r($shopConfig);

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
	
	//echo '<hr> number rows: '. $result->numrows .'<hr>';
	//echo '<hr> orders rows: '. print_r($result);
	
	$orderItems = [];
	
	if($result->numrows > 0) 
	{
		//echo 'show<hr>';
		while($row = $result->fetchRow())
		{
			//$res = $result->fetchRow();
			//print_r($row); echo '<br>';
			
			//product params
			$orderItems[$row['itemId']][$row['productIdFieldId']] = $row['productName'];
			//quantity
			$orderItems[$row['itemId']][$row['quantityFieldId']] = $row['quantitiy'];
		}
		//echo '<hr><hr>best:';print_r($orderItems);echo '<hr>';
		return $orderItems;
	}
}


/**
 * 
 * @global type $smarty
 * @param type $text
 * @return type
 */
function message($title, $text)
{
	//$smarty = TikiLib::lib('smarty');
	global $smarty;
	$smarty->assign('text', $text);
	$smarty->assign('title', $title);
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
	//print_r($shopConfig);

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
		//echo 'show<hr>';
		while($row = $result->fetchRow())
		{
			//$res = $result->fetchRow();
			//print_r($row); echo '<br>';
			$products[$row['itemId']][$row['permName']] = $row['value'];
		}
		
		//print_r($products);
		return $products;
	}

}
/**
 * 
 */
function get_tracker_shop_user_by_userId($user, $shopConfig)
{
	global $tikilib;
	//print_r($shopConfig);
	//return;
	//first all products
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
			//$res = $result->fetchRow();
			//print_r($row); echo '<br>';
			$userBobshop[$row['itemId']][$row['permName']] = $row['value'];
		}
		
		//print_r($products);
		return $userBobshop;
	}

}

/**
 * 
 */
function get_tracker_shop_order_by_orderNumber($shopConfig)
{
	global $tikilib;
	//echo '<h1>get_orderInfo';
	//$order = '';
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
		//echo '<hr>'. $row["permName"];print_r($row); echo '<br>';
		$order[$row['permName']] = $row['value'];
		$order['itemId'] = $row['itemId'];
	}
	//print_r($order);
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
	
	$itemId = $order['itemId'];
	
	//print_r($item);
	echo 'itemId: '. $itemId . ' - '. $payment .' > ';
	
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
				$itemId, 
				$shopConfig['orderPaymentFieldId']]
			);
	return;	
	
}

function update_tracker_shop_order_submitted($sums, $order, $shopConfig)
{
	global $tikilib;
	
	$itemId = $order['itemId'];
	
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
					$itemId, 
					$shopConfig['order'.ucfirst($key).'FieldId']
					]
				);
	}
	
	update_tracker_shop_order_status(2, $shopConfig);
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