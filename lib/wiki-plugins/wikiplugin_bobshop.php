<?php
/**
 * BobShop
 * Version: 1_91_0
 * This Plugin is for CMS TikiWiki
 * 
 * BobShop is a shopping cart system for TikiWiki. 
 * 
 * Copyright (c) 2021 by Robert Hartmann
 * 
 * Install:
 * see https://github.com/romoxi/bobshop
 * 
 * Demo:
 * https://bobshopdemo.bob360.de
 * 
 * THE SOFTWARE IS PROVIDED �AS IS�, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

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
                    'description' => '
						show_shop, 
						add_to_cart_button, 
						show_cart, 
						show_cashier, 
						checkout, 
						order_submitted, 
						paypal_after_transaction,
						show_memory_code_button,
						admin
						',
                    'filter' => 'text',
                    'since' => '1.0',
                ),
                'productId' => array(
                    'required' => false,
                    'name' => tra('ProductId'),
                    'description' => tra('ProductId from tracker bobshop_products to be stored in the order by clicking on the add_to_cart_button. Only used with type = add_to_cart_button'),
                    'filter' => 'text',
                    'since' => '1.0',
                ),
                'showdetails' => array(
                    'required' => false,
                    'name' => tra('Show details'),
                    'description' => tra('1 = after clicking the "Add to Cart button", the '),
                    'filter' => 'number',
                    'since' => '1.7.2',
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
	
	setlocale(LC_NUMERIC, 'de_DE@euro', 'de_DE', 'de-DE', 'de', 'ge', 'de_DE.ISO_8859-1', 'German');
	
	$output = '';
	$showPrices = false;
	$shopConfig['currentOrderNumber'] = 0;
      
    extract($params, EXTR_SKIP);
	
	$shopConfig = get_tracker_shop_config();
	
	//if the shop is closed then exit
	if($shopConfig['bobshopConfigActive'] == 'n')
	{
		return;
	}
	
	global $tikilib;
	global $userlib;
	
		
	$fieldNames['bobshopOrderSessionId'] = $_SESSION['__Laminas']['_VALID']['Laminas\Session\Validator\Id'];
	
	//echo $fieldNames['bobshopOrderSessionId']."<br>";

	//is there a open order for the current session?
	$shopConfig['currentOrderNumber'] = get_tracker_shop_orders_order_number_by_session_id($fieldNames['bobshopOrderSessionId'], $shopConfig);
	
	//if there is no open order for the current session, is there a open order for the current user?
	if($shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
	{
		/**
		 * Returns some detailed user info
		 * userId, email, login ...
		 */
		$userDataDetail = $userlib->get_user_info($user);

		/**
		 * Returns
		 * Array ( [usersTrackerId] => 11 [usersFieldId] => 31 [group] => Registered [user] => admin ) 
		 */
		$userData = $userlib->get_usertracker($userDataDetail["userId"]);
		
		if(isset($userData['user']) && $userData['user'] != '')
		{
			$orderNumberByUser = get_tracker_shop_orders_order_number_by_username($userData['user'], $shopConfig);
			// after login the user should have his old order (cart)
			if($shopConfig['currentOrderNumber'] == 0 && $orderNumberByUser != 0)
			{
				$shopConfig['currentOrderNumber'] = $orderNumberByUser;
				//echo 'currentOrderNumber: '. $shopConfig['currentOrderNumber'];
			}
			//what is, if there are two orders? a new one by sessionId and an old one
			//elseif($shopConfig['currentOrderNumber'] != 0 && $orderNumberByUser != 0)
			elseif(($shopConfig['currentOrderNumber'] != $orderNumberByUser)
					&& $shopConfig['currentOrderNumber'] != 0 
					&& $orderNumberByUser != 0
					)
			{
				//join this two orders to one	
				//to join two orders update all orderNumbers in order_items
				$newNumber = $orderNumberByUser;
				$oldNumber = $shopConfig['currentOrderNumber'];
				//disable the old order per status 13
				update_tracker_shop_order_status(13, $shopConfig);
				$shopConfig['currentOrderNumber'] = $newNumber;
				//join the two orders
				update_tracker_shop_orders_join($oldNumber, $newNumber, $shopConfig);
			}
		}
	}
	elseif($shopConfig['bobshopConfigTikiUserRegistration'] == 'n')
	{
		//echo "bobshop own user system active<br>";
		//get the userdata
		//$bobshopOrderBobshopUser = get_tracker_shop_orders_order_by_orderNumber($shopConfig)['bobshopOrderBobshopUser'];
		$bobshopOrderBobshopUser = bobshop_user_data_decode($shopConfig);
	}
	//echo 'orderNumber: '. $shopConfig['currentOrderNumber'];
	//print_r($_REQUEST);
	//print_r($params);

	/**
	 * operation mode
	 * 
	 * paypal sandbox is configured in wikiplugin_bobshop_paypal_inc.php
	 */
	switch ($shopConfig['bobshopConfigOpMode'])
	{
		case 'default':
			$showPrices = true;
			$buying = true;
			$cart = true;
			break;
		
		case 'sandbox':
			$showPrices = true;
			$buying = true;
			$cart = true;
			break;
		
		//if showprices = false and buying = true > invite offer is active
		case 'offer':
			$showPrices = false;
			$buying = true;
			$cart = true;
			break;
		
		case 'presentation':
			$showPrices = false;
			$buying = false;
			$cart = false;
			break;
		
		case 'info':
			$showPrices = true;
			$buying = false;
			$cart = false;
			break;
	}
	
	global $jitRequest;
	global $jitPost;
	global $jitGet;
	$action = $jitRequest->action->text();
	
	//only show the Add to Cart button
	if($params['type'] == 'add_to_cart_button' && $action == 'shop_article_detail')
	{
		$action = '';
	}

	/*
	 * checks if the "add to cart" button is pressed
	 * and if yes, then store the action a tracker
	 * 
	 */
	//print_r($jitRequest); echo '<hr>';
	//print_r($_REQUEST); echo '<hr>';
	
	if (!empty($action)) 
	{
		// show the product details-page > wikiplugin_bobshop_shop_product_detail.tpl
		$showdetails = false;
	
		switch($action)
		{
			default:
				break;
			
			case 'quantityAdd':
				update_tracker_shop_order_items_quantity_add($jitGet->productId->text(), $shopConfig);
				break;
			
			case 'quantitySub':
				update_tracker_shop_order_items_quantity_sub($jitGet->productId->text(), $shopConfig);
				break;
			
			case 'modify_quantity':
				$orderItems = $orderItems = get_tracker_shop_order_items($shopConfig);
				foreach($orderItems as $itemId => $item)
				{
					$var = 'quantity'. $item[$shopConfig['productProductIdFieldId']];
					$productNewQuantity = $jitRequest->$var->text();
					update_tracker_shop_order_item_quantity_mod($itemId, $productNewQuantity, $shopConfig);
				}
				break;
			
			case 'add_to_cart':
				//insert a new order, if there isnt one
				if(!empty($jitRequest->productId->text()))
				{
					$fieldNames['bobshopOrderUser'] = 'no_login';
					$fieldNames['bobshopOrderItemQuantity'] = 1;
					$fieldNames['bobshopOrderItemProductId'] = $jitPost->productId->text();
					$fieldNames['bobshopOrderCreated'] = date_time();
					$fieldNames['bobshopOrderModified'] = date_time();
					$fieldNames['bobshopOrderIp'] = $_SERVER['REMOTE_ADDR'];
					$fieldNames['bobshopOrderBrowser'] = $_SERVER['HTTP_USER_AGENT'];
					
					$orderVars = insert_tracker_shop_order($fieldNames, $shopConfig, $userData['user'], false);

					//insert the articel in the bobshop_order_items tracker
					if($orderVars != false)
					{
						$fieldNames['bobshopOrderItemOrderNumber'] = $orderVars['orderNumber'];
						//$shopConfig['currentOrderNumber'] = $orderVars['orderNumber'];
						insert_tracker_shop_order_items($fieldNames, $shopConfig);
						$output .= message('The product was added to the cart', '((bobshop_cart|Zum Warenkorb))');
					}
					else
					{
						$output .= message('Last order number error.', 'error');
					}
				}
				else
				{
					showError('productId not set');
				}

				if($params['type'] != 'add_to_cart_button' && $jitRequest->showdetails->text() == 1)
				{
					$showdetails = true;
				}
				$_REQUEST['action'] = 'add_to_cart_allread_done';
				$_GET['action'] = 'add_to_cart_allread_done';
				$jitRequest['action'] = 'add_to_cart_allread_done';
				break;

			case 'shop_article_detail':
				$showdetails = true;
				break;
				
			//shows the login oder register pages with payment and shipping choice
			case 'cashierbutton':
				if($shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
				{
					if($user)
					{
						$action = 'cashierpage';
					}
					else
					{
						//shows the Login Form and link to register
						$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_login.tpl');
					}
				}
				//if the bobshop own user system is used
				elseif($shopConfig['bobshopConfigTikiUserRegistration'] == 'n')
				{
					$action = 'cashierpage';
				}
				break;
				
			//displays a last page with button to buy now
			case 'checkout':
				if($shopConfig['bobshopConfigTikiUserRegistration'] == 'n')
				{
					//are there any data submitted?
					$userDataArray = [];
					//$bobshopUserFields = explode('|', $shopConfig['bobshopConfigUserFields']);
					$bobshopUserFields = bobshop_user_fields($shopConfig);
					foreach($bobshopUserFields as $key => $field)
					{
						$userDataArray[$field] = $jitRequest->$field->text();
					}
					bobshop_user_data_encode($userDataArray, $shopConfig);
					$bobshopOrderBobshopUser = bobshop_user_data_decode($shopConfig);
					$bobshopUserEmail = bobshop_user_data_get_mail($bobshopOrderBobshopUser, $bobshopUserFields);
					//check the mail
					//echo($bobshopOrderBobshopUser['Email']);
					$mailInvalid = false;
					if(!preg_match("/^([a-zA-Z0-9]+([-_\.]?[a-zA-Z0-9])+@[a-zA-Z0-9]+([-_\.]?[a-zA-Z0-9])+\.[a-z]{2,4}){0,}$/", $bobshopUserEmail))
					{
						//mail invalid
						$mailInvalid = true;
					}
				}
				
				if(!$user && $shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
				{
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_login.tpl');
				}
				elseif(empty($bobshopOrderBobshopUser) && $shopConfig['bobshopConfigTikiUserRegistration'] == 'n')
				{
					$action = 'cashierpage';
				}
				else
				{
					$orderItems = get_tracker_shop_order_items($shopConfig);
					$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);
					$payment = get_tracker_shop_payment($shopConfig);
					update_tracker_shop_payment($order, $jitRequest->payment->text(), $shopConfig);
					$params['type'] = 'checkout';
					//revocation
					if($jitPost->revocation->text() != '')
					{
						update_tracker_shop_order_revocation(date_time(), $order, $shopConfig);
					}
					elseif($shopConfig['bobshopConfigRevocationNotice'] != '')
					{
						//echo 'Widerruf nicht zugestimmt';
						header("location: tiki-index.php?page=bobshop_cashierpage&message=missing_rev");
					}
					if($jitPost->tos->text() != '')
					{
						update_tracker_shop_order_tos(date_time(), $order, $shopConfig);
						update_tracker_shop_order_username($user, $shopConfig);
					}
					elseif($shopConfig['bobshopConfigTermsOfServicePage'] != '')
					{
						//echo 'AGB nicht zugestimmt';
						header("location: tiki-index.php?page=bobshop_cashierpage&message=missing_tos");
					}
					if($mailInvalid)
					{
						header("location: tiki-index.php?page=bobshop_cashierpage&message=mail_invalid");
					}
				}
				
				break;
			
			//the buy now button was clicked
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
				
				$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);

				update_tracker_shop_order_submitted($sums, $order, $shopConfig);
				update_tracker_shop_order_number_formated(orderNumber_format($order, $shopConfig), $order, $shopConfig);
				
				//mark the order as submitted - not for sandbox
				if($shopConfig['bobshopConfigOpMode'] != 'sandbox')
				{
					update_tracker_shop_order_status(2, $shopConfig);
				}
				
				//stock control
				if($shopConfig['bobshopConfigStockControl'] == 'y')
				{
					//load the ordered items
					$orderItems = get_tracker_shop_order_items($shopConfig);
					foreach($orderItems as $key => $value)
					{
						//dec the stock quantity
						update_tracker_shop_product_quantity($value[$shopConfig['productProductIdFieldId']], $shopConfig, $value[$shopConfig['orderItemQuantityFieldId']] );
					}
				}
				
				//send a order-received mail
				$output .= send_order_received(true, $sums, $shopConfig);
				
				$payment = get_tracker_shop_payment($shopConfig);

				/*
				 * if paypal is selected *********************************************************
				 */
				if($payment[$order['bobshopOrderPayment']][$shopConfig['paymentFollowUpScriptFieldId']] == 'PAYPAL')
				{
					include_once('wikiplugin_bobshop_paypal_inc.php');

					$token = getTokenPayPal($clientId, $secret, $paypalURL);
					if(empty($token))
					{
						echo '<hr>paypal error 100 - invalid token<hr>';
						exit;
					}
					elseif(isset($sums['sumEnd']) && !empty($sums['sumEnd']))
					{
						//echo 'mer'. $payment[$order['bobshopOrderPayment']][$shopConfig['paymentMerchantNameFieldId']];
						$paypalOrder = getRequestStringPayPal($sums, $payment[$order['bobshopOrderPayment']][$shopConfig['paymentMerchantNameFieldId']], $shopConfig);
						$orderPayPal = createOrderPayPal($paypalOrder, $token, $paypalURL);
					}
					else
					{
						$output .= message('Error', 'paypal error 201 - no endsum', 'errors');	
					}

					//is the order created?
					if($orderPayPal['status'] != 'CREATED')
					{
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 101: '. $orderPayPal['status'], $shopConfig);
						$output .= message('Error', 'paypal error 101 - order not created', 'errors');
						update_tracker_shop_order_status(1, $shopConfig);
					}
					else
					{
						//storeOrderDataPayPal('orderPaymentOrderIdFieldId', $orderPayPal['id'], $shopConfig);
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'CREATED', $shopConfig);
						$approveLink = getApproveLinkPayPal($orderPayPal);
						if($approveLink != false)
						{
							storeOrderDataPayPal('orderPaymenApproveLinkFieldId', $approveLink, $shopConfig);
							header("location: ". $approveLink);
						}
					}
				}
				else
				{				
					//reading the message in the followUpScript field
					//[tpl] marks a template file
					if(substr($payment[$order['bobshopOrderPayment']][$shopConfig['paymentFollowUpScriptFieldId']], 0, 5) == '[tpl]')
					{
						$tplFile = 'wiki-plugins/wikiplugin_bobshop_'. substr($payment[$order['bobshopOrderPayment']][$shopConfig['paymentFollowUpScriptFieldId']], 5) .'.tpl';
						if(file_exists('./templates/'. $tplFile))
						{
							$smarty->assign('payment', $payment);
							$smarty->assign('order', $order);
							$smarty->assign('shopConfig', $shopConfig);
							$smarty->assign('fieldNamesById', array_flip($shopConfig));
							$output .= $smarty->fetch($tplFile);
						}
						else
						{
							$output .= message('Error!', 'Templatefile not found!');
						}
					}
					else
					{
						$output .= message('Thank you!', $payment[$order['bobshopOrderPayment']][$shopConfig['paymentFollowUpScriptFieldId']]);
					}
				}
				$params['type'] = 'order_submitted';
				
				break;
				
			//the invite offer button was clicked
			case 'invite_offer':
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
				
				$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);

				update_tracker_shop_order_submitted($sums, $order, $shopConfig);
				update_tracker_shop_order_status(10, $shopConfig);
				
				//send a order-received mail
				$output .= send_order_received(false, $sums, $shopConfig);	
				
				$params['type'] = 'order_submitted';
				
				break;
				
			//save order
			case 'save_order':
				if($shopConfig['bobshopConfigMemoryOrders'] == 'y')
				{
					$memoryCode = save_order($shopConfig);
					$output .= message('Info', 'The cart was saved with the following code:'. $memoryCode);
				}
				break;
			
			//load the order
			case 'load_order':
				if($shopConfig['bobshopConfigMemoryOrders'] == 'y')
				{
					$newCurrentOrderNumber = load_order_by_memoryCode($jitRequest->memory_code->text(), $userData['user'], $shopConfig);
					if($newCurrentOrderNumber != false)
					{
						$shopConfig['currentOrderNumber'] = $newCurrentOrderNumber;
					}
				}
				break;
				
			/*
			 * admin bobshop
			 */
			case 'admin_show_orders':
				//print_r($jitRequest);
				
				$ordersAdmin = get_tracker_shop_orders_by_trackerID($shopConfig['ordersTrackerId']);
				
				foreach($ordersAdmin as $key => $value)
				{
					$ordersAdmin[$key]['bobshopOrderBobshopUser'] = decode_this(base64_decode($value['bobshopOrderBobshopUser']));
				}
				
				//show specific status
				switch($jitRequest->status->text())
				{
					case 'submitted':
						foreach($ordersAdmin as $key => $value)
						{
							if
							(
								$value['bobshopOrderStatus'] >= 2
								&&
								$value['bobshopOrderStatus'] < 13
								&&
								$value['bobshopOrderStatus'] != 7									
							)
							{
								$ordersNew[$key] = $value;
							}
						}
						break;
					
					case 'all':
						$ordersNew = $ordersAdmin;
						break;
					
				}
				
				//print_r($orders);
				$smarty->assign('orders', $ordersNew);
				$tableFields = 'orderNumber|created|sumEnd|status|paymentOrderId|orderNumberFormated|bobshopUser';
				$smarty->assign('tableHead', $tableFields);
				$smarty->assign('tableFields', array_map('ucfirst', (explode('|', $tableFields))));
				$smarty->assign('action', 'admin_show_orders');
				
				break;
				
			case 'admin_show_order':
				//print_r($jitRequest);
	
				$orderAdmin = get_tracker_shop_orders_order_by_orderNumber($shopConfig, $jitRequest->orderNumber->text());
				$orderItems = get_tracker_shop_order_items($shopConfig, false, $jitRequest->orderNumber->text());

				if(!empty($orderAdmin['bobshopOrderBobshopUser']))
				{
					$orderAdmin['bobshopOrderBobshopUser'] = decode_this(base64_decode($orderAdmin['bobshopOrderBobshopUser']));
				}
				if(!empty($orderAdmin['bobshopOrderPaymentResponse']))
				{
					$orderAdmin['bobshopOrderPaymentResponse'] = decode_this(base64_decode($orderAdmin['bobshopOrderPaymentResponse']));
				}

				$smarty->assign('mailer', 1);
				$smarty->assign('order', $orderAdmin);
				$smarty->assign('orderItems', $orderItems);
				$smarty->assign('action', 'admin_show_order');
				
				break;
		}
		
		//show the product details page
		if($showdetails)
		{
			$product = get_tracker_shop_product_by_productId($jitRequest->productId->text(), $shopConfig);

			//variants
			$variants = get_product_variants($product, $shopConfig);
			$smarty->assign('variants', $variants);
			
			//if there is a superset, get some info from it
			if(!empty($product['bobshopProductVariantSuperset']))
			{
				$productSuperset = get_tracker_shop_product_by_productId($product['bobshopProductVariantSuperset'], $shopConfig);
				
				//set all empty fields with the superset values
				foreach($product as $field => $value)
				{
					if(empty($value))
					{
						$product[$field] = $productSuperset[$field];
					}
				}
			}
	
			$smarty->assign('product', $product);
			$smarty->assign('productId', $jitRequest->productId->text());
			$smarty->assign('showPrices', $showPrices);
			$smarty->assign('buying', $buying);
			$smarty->assign('cart', $cart);
			$smarty->assign('shopConfig', $shopConfig);
			$smarty->assign('showdetails', $showdetails);
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_shop_product_detail.tpl');
			$params['type'] = '';
		}
	}

	/**
	 * plugin params ***************************************************************************
	 */
 
    switch($params['type'])
	{
		//shows the product list
		//wiki syntax {bobshop type="show_shop"}
		case 'show_shop':
			$products = get_tracker_shop_products_by_trackerID($shopConfig['productsTrackerId']);

			// sort the array
			//get the GET-parameter
			$sortOrderSubmitted = $jitRequest->sort_order->text();
			
			switch($sortOrderSubmitted)
			{
				case 'sort_sort_order':
					$sortOrder = array_column($products, 'bobshopProductSortOrder');
					$sorting = SORT_ASC;
					break;
			
				case 'sort_price_up':
					$sortOrder = array_column($products, 'bobshopProductPrice');
					$sorting = SORT_ASC;
					break;
				
				case 'sort_price_down':
					$sortOrder = array_column($products, 'bobshopProductPrice');
					$sorting = SORT_DESC;
					break;
				
				case 'sort_name':
					$sortOrder = array_column($products, 'bobshopProductName');
					$sorting = SORT_ASC;
					break;
				
				default:
					$sortOrder = array_column($products, 'bobshopProductSortOrder');
					$sorting = SORT_ASC;
					break;
			}

			array_multisort($sortOrder, $sorting, $products);
			$smarty->assign('lastSort', $sortOrderSubmitted);
			$smarty->assign('showPrices', $showPrices);
			$smarty->assign('buying', $buying);
			$smarty->assign('cart', $cart);			
			$smarty->assign('page', $jitRequest->page->text());
			$smarty->assign('products', $products);
			$smarty->assign('shopConfig', $shopConfig);
			
			// it the 'add to cart button' is set to showdetails, make sure $showdetails is set to 1
			if(!$showdetails)
			{
				$showdetails = $params['showdetails'];
			}
			$smarty->assign('showdetails', $showdetails);
						
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_shop.tpl');
			break;

		//print the add to cart button
		//{bobshop type="add_to_cart_button" productId="1004"}
		case 'add_to_cart_button':
			if($cart)
			{
				//is productId in tracker products
				$product = get_tracker_shop_product_by_productId($params['productId'], $shopConfig);
				if($product['bobshopProductProductId'] == $params['productId'])
				{
					$smarty->assign('productId', $params['productId']);
					$smarty->assign('shopConfig', $shopConfig);
					$smarty->assign('showdetails', $showdetails);
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_button_add.tpl');
				}
				else
				{
					$output .= message('Warning', 'Add to cart button can not be displayed.<br>productId ('.$params["productId"].') not in tracker bobshop_products', 'warning');
				}
			}
			break;
		
		//show the cart
		case 'show_cart':
			if($cart)
			{
				check_duplicate_order_items_by_current_order_number($shopConfig);
				$orderItems = get_tracker_shop_order_items($shopConfig);
				$smarty->assign('shopConfig', $shopConfig);
				if(!empty($orderItems))
				{
					$smarty->assign('showPrices', $showPrices);
					$smarty->assign('buying', $buying);
					$smarty->assign('cart', $cart);		
					$smarty->assign('showQuantityModify', 1);
					$smarty->assign('status', 1);
					$smarty->assign('showPayment', 0);
					$smarty->assign('orderItems', $orderItems);
					$smarty->assign('fieldNamesById', array_flip($shopConfig));
				}
				else
				{
					$smarty->assign('status', 0);
				}
			}
			else
			{
				$smarty->assign('status', 0);
			}
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_cart.tpl');
			break;
		
		case 'show_cashier':
			if($buying)
			{
				if(isset($user) || $shopConfig['bobshopConfigTikiUserRegistration'] == 'n')
				//if(isset($user) )
				{
					if($jitGet->message->text() == 'missing_tos')
					{
						$output .= message('Consent is required', 'Terms of Service rejected', 'warning');
					}
					if($jitGet->message->text() == 'missing_rev')
					{
						$output .= message('Consent is required', 'Revocation instruction rejected', 'warning');
					}
					if($jitGet->message->text() == 'mail_invalid')
					{
						$output .= message('Wrong E-Mail', 'Insert you correct e-mail', 'warning');
					}
					update_tracker_shop_order_username($user, $shopConfig);
					$userBobshop = get_tracker_shop_user_by_user($user, $shopConfig);
					$orderItems = get_tracker_shop_order_items($shopConfig);
					$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);
					//echo orderNumber_format($order, $shopConfig);
					//if there ist no payment set, use the default
					if($order['bobshopOrderPayment'] == 0)
					{
						$order['bobshopOrderPayment'] = $shopConfig['bobshopConfigPaymentDefault'];
					}
					$payment = get_tracker_shop_payment($shopConfig);
					$smarty->assign('showPrices', $showPrices);
					$smarty->assign('buying', $buying);
					$smarty->assign('cart', $cart);		
					if($shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
					{
						$smarty->assign('userBobshop', $userBobshop);
					}
					else
					{
						$smarty->assign('bobshopUserData', bobshop_user_data_decode($shopConfig));
						$smarty->assign('bobshopUserFields', bobshop_user_fields($shopConfig));						
						$smarty->assign('bobshopOrderBobshopUser', $bobshopOrderBobshopUser);
					}
					$smarty->assign('showPayment', 1);
					$smarty->assign('payment', $payment);
					$smarty->assign('order', $order);
					$smarty->assign('user', $user);
					$smarty->assign('orderItems', $orderItems);
					$smarty->assign('shopConfig', $shopConfig);
					$smarty->assign('fieldNamesById', array_flip($shopConfig));
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_cashierpage.tpl');
				}
			}
			break;
			
		case 'checkout':
			//print_r($shopConfig);
			if($buying)
			{
				if(isset($user) || !empty($bobshopOrderBobshopUser))
				{
					$userBobshop = get_tracker_shop_user_by_user($user, $shopConfig);
					$orderItems = get_tracker_shop_order_items($shopConfig);
					$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);
					$payment = get_tracker_shop_payment($shopConfig);
					if($shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
					{
						$smarty->assign('userBobshop', $userBobshop);
						$smarty->assign('user', $user);
					}
					else
					{
						$smarty->assign('bobshopOrderBobshopUser', $bobshopOrderBobshopUser);
					}
					$smarty->assign('showPrices', $showPrices);
					$smarty->assign('payment', $payment);
					$smarty->assign('showPayment', 1);
					$smarty->assign('order', $order);
					$smarty->assign('orderItems', $orderItems);
					$smarty->assign('shopConfig', $shopConfig);
					$smarty->assign('fieldNamesById', array_flip($shopConfig));
					$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_checkout.tpl');
				}
				else
				{
					header("location: tiki-index.php?page=bobshop_cashierpage");
				}
			}
			break;
			
		case 'order_submitted':
			//nothing to do
			break;

		/*
		 * PayPal **********************************************************
		 */
		case 'paypal_after_transaction':
			if($buying)
			{
				include_once('wikiplugin_bobshop_paypal_inc.php');
				//GET vars: token => orderId; PayerId => the id from paypal (not used in our shop
				//print_r($_GET);
				//array ( [page] => bobshop_paypalAfterTransaction [token] => 3AP975098W179773U [PayerID] => HKZWXU9WRX7E8 ) 
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
					if($response['status'] == 'CREATED')
					{
						$output .= message('Cancel', 'The payment was canceled', 'errors');
						update_tracker_shop_order_status(1, $shopConfig);
					}
					else
					{
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 102: '. $response['status'], $shopConfig);
						$output .= message('Error', 'paypal error 102 - order not created. Status = '. $response['status'] .' orderId = '. $orderIdResponse, 'errors');
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
						storeOrderDataPayPal('orderPaymentResponseFieldId', base64_encode(encode_this(json_encode($response))), $shopConfig);
						$transactionId = $response['purchase_units'][0]['payments']['captures'][0]['id'];
						storeOrderDataPayPal('orderPaymentOrderIdFieldId', $transactionId , $shopConfig);
					}
					else
					{
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 104: '. $response['status'], $shopConfig);
						$output .= message('Error', 'paypal error 104 - order not completed. Status = '. $response['status'], 'errors');
						
						send_order_received(false, $sums, $shopConfig, 'paypal', 'error');
					}
					
					if($response['status'] != 'COMPLETED')
					{
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'error 103: '. $response['status'], $shopConfig);
						update_tracker_shop_order_status(1, $shopConfig);
						//send a mail
						send_order_received(false, $sums, $shopConfig, 'paypal', 'error');
						$output .= message('Error', 'paypal error 103 - order not completed. Status = '. $response['status'], 'errors');
					}
					else
					{
						storeOrderDataPayPal('orderPaymentPayeeMerchantIdFieldId', getMerchantIdPayPal($response), $shopConfig);
						storeOrderDataPayPal('orderPaymentStatusFieldId', 'COMPLETED', $shopConfig);
						//send infos about the payment
						send_order_received(false, $sums, $shopConfig, 'paypal');
						
						$smarty->assign('orderIdResponse', $orderIdResponse);
						$smarty->assign('transactionIdResponse', $transactionId);
						$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_paypal_completed.tpl');
					}
				}
			}
			break;	
			
		case 'show_memory_code_button':
			if($cart)
			{
				$smarty->assign('shopConfig', $shopConfig);
				$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_memory_code_button.tpl');
			}
			break;
			
		//admin bobshop
		case 'admin':
			$smarty->assign('shopConfig', $shopConfig);
			$output .= $smarty->fetch('wiki-plugins/wikiplugin_bobshop_admin.tpl');
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
		'orderItemQuantityFieldId'		=> 'bobshopOrderItemQuantity', 
		);
	
	foreach($shopConfig['orderItemsFields'] as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['orderItemsTrackerId']);
	}
	
	//fields in orders tracker
	$shopConfig['ordersFields'] = array(
		'orderPaymentPayerIdFieldId'		=> 'bobshopOrderPaymentPayerId', 
		'orderPaymentPayeeMerchantIdFieldId'=> 'bobshopOrderPaymentPayeeMerchantId', 
		'orderPaymentApproveLinkFieldId'	=> 'bobshopOrderPaymentApproveLink', 
		'orderPaymentStatusFieldId'			=> 'bobshopOrderPaymentStatus', 
		'orderPaymentOrderIdFieldId'		=> 'bobshopOrderPaymentOrderId', 
		'orderPaymentResponseFieldId'		=> 'bobshopOrderPaymentResponse', 
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
		'orderAgreedTosDateFieldId'			=> 'bobshopOrderAgreedTosDate', 
		'orderAgreedRevocationDateFieldId'	=> 'bobshopOrderAgreedRevocationDate', 
		'orderStatusFieldId'		=> 'bobshopOrderStatus', 
		'orderBrowserFieldId'		=> 'bobshopOrderBrowser', 
		'orderNoteUserFieldId'		=> 'bobshopOrderNoteUser', 
		'orderNoteInternalFieldId'	=> 'bobshopOrderNoteInternal', 
		'orderIpFieldId'			=> 'bobshopOrderIp', 
		'orderModifiedFieldId'		=> 'bobshopOrderModified', 
		'orderCreatedFieldId'		=> 'bobshopOrderCreated', 
		'orderSessionIdFieldId'		=> 'bobshopOrderSessionId', 
		'orderMemoryCodeFieldId'	=> 'bobshopOrderMemoryCode', 
		'orderBobshopUserFieldId'	=> 'bobshopOrderBobshopUser', 
		'orderUserFieldId'			=> 'bobshopOrderUser',
		'orderNumberFormatedFieldId'	=> 'bobshopOrderOrderNumberFormated',
		);
	
	foreach($shopConfig['ordersFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['ordersTrackerId']);
	}
	
	//fields in product tracker
	//intern var => permanent tracker field
	$shopConfig['productFields'] = array(
		'productDetailPageFieldId'		=> 'bobshopProductDetailPage', 
		'productSortOrderFieldId'		=> 'bobshopProductSortOrder', 
		'productActiveFieldId'			=> 'bobshopProductActive', 
		'productMakerFieldId'			=> 'bobshopProductMaker', 
		'productEanFieldId'				=> 'bobshopProductEan', 
		'productDeliveryTimeFieldId'	=> 'bobshopProductDeliveryTime', 
		'productShippingCatFieldId'		=> 'bobshopProductShippingCat', 
		'productTaxrateCatFieldId'		=> 'bobshopProductTaxrateCat', 
		'productProductIdFieldId'		=> 'bobshopProductProductId', 
		'productDescriptionFieldId'		=> 'bobshopProductDescription', 
		'productNameFieldId'			=> 'bobshopProductName', 
		'productWikipageNameFieldId'	=> 'bobshopProductWikipageName',
		'productWikipageFieldId'		=> 'bobshopProductWikipage',
		'productPic1FieldId'			=> 'bobshopProductPic1',
		'productStockQuantityFieldId'	=> 'bobshopProductStockQuantity',
		'productStockWarningFieldId'	=> 'bobshopProductStockWarning',
		'productPriceFieldId'			=> 'bobshopProductPrice',
		'productVariantNameFieldId'		=> 'bobshopProductVariantName',
		'productVariantProductIdsFieldId'=> 'bobshopProductVariantProductIds',
		'productVariantSupersetFieldId'	=> 'bobshopProductVariantSuperset',
		);
	
	foreach($shopConfig['productFields']  as $key => $name)
	{
		$shopConfig[$key] = get_tracker_shop_fieldId($name, $shopConfig['productsTrackerId']);
	}
		
	//fields in payment tracker
	$shopConfig['paymentFields'] = array(
		'paymentMerchantNameFieldId'	=> 'bobshopPaymentMerchantName', 
		'paymentActiveFieldId'	=> 'bobshopPaymentActive', 
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
				tiki_tracker_fields.permName, 
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

	
	//default values for text fields
	//there are some translations in /lang/de/custom.php
	$defaultFields = array(
		"bobshopConfigShopName"=>"Bobshop",
		"bobshopConfigAddToCartButtonText"=>"Add to Cart",
		"bobshopConfigAddToWatchlistButtonText"=>"Add to Watchlist",
		"bobshopConfigCashierButtonText"=>"Proceed to Checkout",
		"bobshopConfigCheckoutButtonText"=>"Order Summary",
		"bobshopConfigBuyNowButtonText"=>"Buy now with charge",
		"bobshopConfigSortingLabelText"=>"Sort",
		"bobshopConfigSortingDefaultText"=>"Relevance",
		"bobshopConfigSortingPriceUpText"=>"Price up",
		"bobshopConfigSortingPriceDownText"=>"Price down",
		"bobshopConfigSortingNameText"=>"Name",
	);

	foreach($result as $row)
    {
		$shopConfig['shopConfigItemId'] = $row['itemId'];
        //$shopConfig['shopConfig_'.$row['name']] = $row['value'];
        if(!empty($row['value']))
		{
			$shopConfig[$row['permName']] = $row['value'];
		}
		else
		{
			$shopConfig[$row['permName']] = $defaultFields[$row['permName']];
		}
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
	return $res['value'];
}


/**
 * 
 * @returns the orderNumber
 */
function get_tracker_shop_orders_order_number_by_username($username, $shopConfig)
{
	global $tikilib;
	
	if(empty($username)){ return false;}
	
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
			$shopConfig['orderUserFieldId'], 
			$username,
			$shopConfig['orderStatusFieldId'] 
			]
		);
	
	$res = $result->fetchRow();
	return $res['value'];
}

/**
 * 
 * @returns the orderNumber
 * f2.value = '14' > a saved order status
 */
function get_tracker_shop_orders_order_number_by_memoryCode($memoryCode, $shopConfig)
{
	global $tikilib;
	
	if(empty($memoryCode)){ return false;}
	
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
				(f2.value = '14')
		",
		[
			$shopConfig['orderMemoryCodeFieldId'], 
			$memoryCode,
			$shopConfig['orderStatusFieldId'] 
			]
		);
	
	$res = $result->fetchRow();
	//echo '<hr>by_memoryCode: ' . $username .':'; print_r($res);
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
 * 1.1. if the order already exist, return order_number
 * 1.2. lade die bisher h�chste order_number
 * 3. insert the the new stuff in tracker_items (itemId will be autoincremented)
 * 4. get the last insertId (itemId)
 * 2. get the fieldId by the fieldname (fields 'sid', 'user')
 * 5. insert in tracker_item_fields
 * 6. inc trackers.items
 * 
 * $makeCopy = true to save a copy of the current order and create a memoryCode
 */
function insert_tracker_shop_order($fieldNames, $shopConfig, $username, $makeCopy = false)
{
	global $tikilib;
		
	//if makeCopy then insert a new order
	if($makeCopy == false)
	{
		//1.1 
		$orderN = get_tracker_shop_orders_order_number_by_session_id($fieldNames['bobshopOrderSessionId'], $shopConfig);

		if($orderN > 0)
		{
			$ret['orderNumber'] = $orderN;
			return $ret;
		}

		$orderN = get_tracker_shop_orders_order_number_by_username($username, $shopConfig);
		if($orderN > 0)
		{
			$ret['orderNumber'] = $orderN;
			return $ret;
		}
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
	if(empty($res))
	{
		showError('last_order_number not found - abort');
		return false;
	}	
	$lastOrderNumber = $res['lastValue'];
	$fieldNames['bobshopOrderOrderNumber'] = $lastOrderNumber + 1;
	$ret['orderNumber'] = $fieldNames['bobshopOrderOrderNumber'];
	//die;
	//3.
	$result = $tikilib->query(
			"INSERT INTO 
				tiki_tracker_items
				(trackerId, status, created, createdBy, lastModif, lastModifBy)
			VALUES
				(?, ?, ?, ?, ?, ?)",
				[$shopConfig['ordersTrackerId'], 'o', date_time(), $username, date_time(), $username]
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
	if($makeCopy == false)
	{
		update_tracker_shop_order_status(1, $shopConfig);
	}
	else
	{
		//mark it as a saved order
		update_tracker_shop_order_status(14, $shopConfig);
	}
		
	return $ret;
}

/**
 * 
 * needs array $fieldNames
 * [bobshopOrderItemProductId] > productId
 * [bobshopOrderItemOrderNumber] > current Order Number
 * [bobshopOrderItemQuantity] > Quantity to be inserted (not added)
 */
function insert_tracker_shop_order_items($fieldNames, $shopConfig)
{
	global $tikilib;

	//is the article_number already in the order?
	//if already exist, do not insert
	//returns the itemId from the productId
	$itemId = check_is_productId_in_cart($fieldNames['bobshopOrderItemProductId'], $shopConfig);

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
				[$shopConfig['orderItemsTrackerId'], 'o', date_time(), 'bobshop', date_time(), 'bobshop']
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
 * check if a productId is already in the cart
 * returns itemId for the productId
 */
function check_is_productId_in_cart($productId, $shopConfig)
{
	$orderItems =  get_tracker_shop_order_items($shopConfig);
	
	if(empty($orderItems)) return false;
	foreach($orderItems as $itemId => $item)
	{
		if($item[$shopConfig['productProductIdFieldId']] == $productId)
		{
			//update_tracker_shop_order_items_delete($itemId, $shopConfig);
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
	$product = get_tracker_shop_order_items($shopConfig, $productId);
	$itemId = array_key_first($product);
	
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
	$product = get_tracker_shop_order_items($shopConfig, $productId);
	$itemId = array_key_first($product);
	
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


/*
 * new quantity for productId
 */
function update_tracker_shop_order_item_quantity_mod($itemId, $quantity, $shopConfig)
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
			", [$quantity, $itemId, $shopConfig['orderItemQuantityFieldId']]
			);
	return;
}


/*
 * delete a productId from the bobshop_order_items tracker
 * do not really delete 
 * assign the orderNumber 0
 */
function update_tracker_shop_order_items_delete($itemId, $shopConfig)
{
	global $tikilib;
	
	$result = $tikilib->query(
			"UPDATE 
				tiki_tracker_item_fields
			SET
				value = 0
			WHERE
				itemId = ?
			AND
				fieldId = ?
			", [$itemId, $shopConfig['orderItemOrderNumberFieldId']]
			);
	return;
}

/*
 * check for duplicate products in the cart
 * 
 */
function check_duplicate_order_items_by_current_order_number($shopConfig)
{
	//return;
	$orderItems =  get_tracker_shop_order_items($shopConfig);
	//print_r($orderItems);

	//return if empty
	if(empty($orderItems)) return;
	
	$itemIdPositive = [];	//all itemId with positive quantity
	foreach($orderItems as $itemId => $item)
	{
		//if  the quantity is 0 delete the item
		if
		(
			$item[$shopConfig['orderItemQuantityFieldId']] == 0
			
		)
		{
			update_tracker_shop_order_items_delete($itemId, $shopConfig);
		}
		
		foreach($orderItems as $key => $value)
		{
			//are the productIds the same?
			if(
				$item[$shopConfig['productProductIdFieldId']] == $value[$shopConfig['productProductIdFieldId']]
				&&
				$item[$shopConfig['orderItemQuantityFieldId']] > 0
				&&
				$value[$shopConfig['orderItemQuantityFieldId']] > 0
				&&
				$itemId != $key
				&&
				!in_array($itemId, $itemIdPositive)
			)
			{
				$itemIdPositive[] .= $key;
			}
		}
	}
		
	foreach($itemIdPositive as $itemIdDel)
	{
		update_tracker_shop_order_items_delete($itemIdDel, $shopConfig);
	}

	return;	
}


/**
 * get all products for one order
 * 
 * @return array with all products in that order
 * 
 */
function get_tracker_shop_order_items($shopConfig, $productId = false, $orderNumber = false)
{
	global $tikilib;
	if($orderNumber != false)
	{
		$shopConfig['currentOrderNumber'] = $orderNumber;
	}
	
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
 * shows a messagebox
 * $type
 * - confirm
 * - comment
 * - errors
 * - information
 * - note
 * - tip
 * - warning
 * see: https://doc.tiki.org/PluginRemarksbox
 * 
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
 * returns all orders
 * for admin
 */
function get_tracker_shop_orders_by_trackerID($trackerId)
{
	global $tikilib;
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
	
	$orders = [];
	
	if($result->numrows > 0) 
	{
		while($row = $result->fetchRow())
		{
			$orders[$row['itemId']][$row['permName']] = $row['value'];
		}
		return $orders;
	}
}



function get_tracker_shop_product_by_productId($productId, $shopConfig)
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
			$shopConfig['productProductIdFieldId'],
			$productId
			]
			);

	while($row = $result->fetchRow())
	{
		$product[$row['permName']] = $row['value'];
		$product['itemId'] = $row['itemId'];
	}
	return $product;
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
		while($row = $result->fetchRow())
		{
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
function get_tracker_shop_orders_order_by_orderNumber($shopConfig, $orderNumber = false)
{
	global $tikilib;
	if($orderNumber != false)
	{
		$shopConfig['currentOrderNumber'] = $orderNumber;
	}

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

//Update the bobshop own user data
function update_tracker_shop_order_bobshop_user($order, $userData, $shopConfig)
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
				$userData,
				$order['itemId'], 
				$shopConfig['orderBobshopUserFieldId']]
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
					$shopConfig['orderAgreedRevocationDateFieldId']
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
					$shopConfig['orderAgreedTosDateFieldId']
					]
				);
	return;	
}

/*
 * Update the formated order number
 */
function update_tracker_shop_order_number_formated($value, $order, $shopConfig)
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
					$shopConfig['orderOrderNumberFormatedFieldId']
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
 * 7 = deleted
 * 
 * 10 = invited offer submitted
 * 11 = offer under progress
 * 12 = offer sent
 * 13 = order joined with another
 * 14 = a saved order
 * 
 * @global type $tikilib
 * @param type $status
 * @param type $shopConfig
 * @return type
 */
function update_tracker_shop_order_status($status, $shopConfig)
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
				$status,
				$order['itemId'], 
				$shopConfig['orderStatusFieldId']
				]
			);
	return;	
}


/**
 * 1. get all products from the oldNumber
 * 2. foreach products > if product exists in new order > add qty
 *                     > else insert
 */
function update_tracker_shop_orders_join($oldNumber, $newNumber, $shopConfig)
{
	global $tikilib;
	$shopConfig['currentOrderNumber'] = $oldNumber;
	$oldProducts = get_tracker_shop_order_items($shopConfig);
	$shopConfig['currentOrderNumber'] = $newNumber;
	
	if(count($oldProducts) == 0) return;
	
	foreach($oldProducts as $item => $product)
	{
		//echo '<br>produktID: '. $product[$shopConfig['productProductIdFieldId']];
		//echo '<br>produktID: '. $product[$shopConfig['orderItemQuantityFieldId']];
		while($product[$shopConfig['orderItemQuantityFieldId']] > 0)
		{
			$fieldNames['bobshopOrderItemProductId'] = $product[$shopConfig['productProductIdFieldId']];
			$fieldNames['bobshopOrderItemOrderNumber'] = $newNumber;
			$fieldNames['bobshopOrderItemQuantity'] = 1;
			insert_tracker_shop_order_items($fieldNames, $shopConfig);
			//the insert function calls the add function if needed
			$product[$shopConfig['orderItemQuantityFieldId']] --;
		}
	}
}


/**
 * when the user login, the order gets the username
 * 
 * @global type $tikilib
 * @param type $user
 * @param type $shopConfig
 * @return type
 */
function update_tracker_shop_order_username($user, $shopConfig)
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
				$user,
				$order['itemId'], 
				$shopConfig['orderUserFieldId']
				]
			);
	return;	
}


/**
 * update productStockQuantiy
 * 
 *
 */
function update_tracker_shop_product_quantity($productId, $shopConfig, $quantity)
{
	global $tikilib;
	//echo 'productId: '. $productId .'<br>';
	//echo 'quantity: '. $quantity .'<br>';
	//get the itemId
	$product = get_tracker_shop_product_by_productId($productId, $shopConfig);
	
	$result = $tikilib->query(
			"UPDATE 
				tiki_tracker_item_fields
			SET
				value = value - ?
			WHERE
				itemId = ?
			AND
				fieldId = ?
			", [$quantity,
				$product['itemId'], 
				$shopConfig['productStockQuantityFieldId']]
			);
	return;
}



/**
 * save the order
 * make a copy of the complete order
 * create a memoryCode
 */
function save_order($shopConfig)
{
	global $tikilib;
	$products = get_tracker_shop_order_items($shopConfig);
	
	if(count($products) == 0) return;
	
	//save the current order Number
	//$currentOrderNumber = $shopConfig['currentOrderNumber'];
	
	//insert a new order
	$fieldNames['bobshopOrderCreated'] = date_time();
	$fieldNames['bobshopOrderIp'] = $_SERVER['REMOTE_ADDR'];
	$fieldNames['bobshopOrderBrowser'] = $_SERVER['HTTP_USER_AGENT'];
	$fieldNames['bobshopOrderUser'] = 'copyuser';
	$fieldNames['bobshopOrderItemQuantity'] = 1;
	$orderVars = insert_tracker_shop_order($fieldNames, $shopConfig, 'copyuser', true);

	//insert the product in the bobshop_order_items tracker
	if($orderVars != false)
	{
		$fieldNames['bobshopOrderItemOrderNumber'] = $orderVars['orderNumber'];
		$shopConfig['currentOrderNumber'] = $orderVars['orderNumber'];

		//insert all products
		foreach($products as $item => $product)
		{
			while($product[$shopConfig['orderItemQuantityFieldId']] > 0)
			{
				$fieldNames['bobshopOrderItemProductId'] = $product[$shopConfig['productProductIdFieldId']];
				$fieldNames['bobshopOrderItemQuantity'] = 1;
				insert_tracker_shop_order_items($fieldNames, $shopConfig);
				//the insert function calls the add function if needed
				$product[$shopConfig['orderItemQuantityFieldId']] --;
			}
		}

		$output .= message('Cart was saved', '');
	}
	else
	{
		$output .= message('Last order number error. Copy failed', 'error');
	}
	
	//restore the order number
	//$shopConfig['currentOrderNumber'] = $currentOrderNumber;
	
	//create memoryCode
	$memoryCode = create_memoryCode($shopConfig);
	
	return $memoryCode;
}

/**
 * load the order
 * make a copy of the saved order
 * and make it to the current order
 */
function load_order_by_memoryCode($memoryCode, $username, $shopConfig)
{
	if(empty($memoryCode)) return;
	
	global $tikilib;
	// 5f9da3153317d
	// 
	//save the current order Number
	$currentOrderNumber = $shopConfig['currentOrderNumber'];
	//load the products
	$savedOrderNumber = get_tracker_shop_orders_order_number_by_memoryCode($memoryCode, $shopConfig);
	$shopConfig['currentOrderNumber'] = $savedOrderNumber;
	$products = get_tracker_shop_order_items($shopConfig);
	
	if(count($products) == 0) return;
		
	//insert a new order
	$fieldNames['bobshopOrderCreated'] = date_time();
	$fieldNames['bobshopOrderIp'] = $_SERVER['REMOTE_ADDR'];
	$fieldNames['bobshopOrderBrowser'] = $_SERVER['HTTP_USER_AGENT'];
	$fieldNames['bobshopOrderUser'] = $username;
	$fieldNames['bobshopOrderItemQuantity'] = 1;
	$fieldNames['bobshopOrderSessionId'] = $_SESSION['__Laminas']['_VALID']['Laminas\Session\Validator\Id'];
	$orderVars = insert_tracker_shop_order($fieldNames, $shopConfig, 'loaduser', true);
	echo '<hr>orderVars: '; print_r($orderVars);
	//insert the product in the bobshop_order_items tracker
	if($orderVars != false)
	{
		$fieldNames['bobshopOrderItemOrderNumber'] = $orderVars['orderNumber'];
		$shopConfig['currentOrderNumber'] = $orderVars['orderNumber'];

		//insert all products
		foreach($products as $item => $product)
		{
			while($product[$shopConfig['orderItemQuantityFieldId']] > 0)
			{
				$fieldNames['bobshopOrderItemProductId'] = $product[$shopConfig['productProductIdFieldId']];
				$fieldNames['bobshopOrderItemQuantity'] = 1;
				insert_tracker_shop_order_items($fieldNames, $shopConfig);
				//the insert function calls the add function if needed
				$product[$shopConfig['orderItemQuantityFieldId']] --;
			}
		}
		update_tracker_shop_order_status(1, $shopConfig);

		$output .= message('Cart was loaded', '');
		//restore the order number
		$shopConfig['currentOrderNumber'] = $currentOrderNumber;
		//delete the current order
		update_tracker_shop_order_status(7, $shopConfig);	

		return $orderVars['orderNumber'];
	}
	else
	{
		$output .= message('Last order number error. Load failed', 'error');
		return false;
	}
	
}






/**
 * 
 */
function create_memoryCode($shopConfig)
{
	global $tikilib;
	
	$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);
	
	$memoryCode = uniqid();

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
				$memoryCode,
				$order['itemId'], 
				$shopConfig['orderMemoryCodeFieldId']
				]
			);
	return $memoryCode;		
}



/**
 * Send an email
 * 
 * @global type $tikilib
 * @global type $user
 * @global type $smarty
 * @param type $showPrices
 * @param type $sums
 * @param type $userDataDetail
 * @param type $shopConfig
 * @return type
 */
function send_order_received($showPrices, $sums, $shopConfig, $tpl = '', $error = '')
{
	//ToDo: check $sums
	global $tikilib;
	global $userlib;
	global $user;
	global $smarty;
	
	//$smartmail = new Smarty;
	
	if($shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
	{
		/**
		 * Returns some detailed user info
		 * userId, email, login ...
		 */
		$userDataDetail = $userlib->get_user_info($user);
		$mailReceiver = $userDataDetail['email'];
		$userData = get_tracker_shop_user_by_user($userDataDetail['login'], $shopConfig);
	}
	elseif($shopConfig['bobshopConfigTikiUserRegistration'] == 'n')
	{
		//get the userdata from the tracker bobshop_orders
		$bobshopOrderBobshopUser = bobshop_user_data_decode($shopConfig);
		
		//get the mailaddress
		$bobshopUserFields = bobshop_user_fields($shopConfig);
		$mailReceiver = bobshop_user_data_get_mail($bobshopOrderBobshopUser, $bobshopUserFields);
		//$mailReceiver = $bobshopOrderBobshopUser['Email'];
		//print_r($bobshopOrderBobshopUser);
	}	
	
	$mailSender = $shopConfig['bobshopConfigEmailSender'];
	$shopname = $shopConfig['bobshopConfigShopName'];
	
	$orderItems = get_tracker_shop_order_items($shopConfig);
	$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);
	$payment = get_tracker_shop_payment($shopConfig);

	$smarty->assign('payment', $payment);
	$smarty->assign('showPayment', 1);
	$smarty->assign('orderItems', $orderItems);

	if($shopConfig['bobshopConfigTikiUserRegistration'] == 'y')
	{
		$smarty->assign('userBobshop', $userData);
	}
	else
	{
		$smarty->assign('bobshopUserData', bobshop_user_data_decode($shopConfig));
		$smarty->assign('bobshopUserFields', bobshop_user_fields($shopConfig));						
		$smarty->assign('bobshopOrderBobshopUser', $bobshopOrderBobshopUser);
	}

	$smarty->assign('showPrices', $showPrices);
	$smarty->assign('user', $user);
	$smarty->assign('order', $order);
	$smarty->assign('orderNumberFormated', orderNumber_format($order, $shopConfig));
	$smarty->assign('shopConfig', $shopConfig);
	$smarty->assign('fieldNamesById', array_flip($shopConfig));
	$smarty->assign('mailer', 1);
	$smarty->assign('error', $error);


	if($tpl == '')
	{
		if($showPrices)
		{
			$subject = $shopname .' Bestellbestätigung';
			$mailText = $smarty->fetch('wiki-plugins/wikiplugin_bobshop_mail_order_received.tpl');
		}
		else
		{
			$subject = $shopname .' Angebotsanfrage';		
			$mailText = $smarty->fetch('wiki-plugins/wikiplugin_bobshop_mail_invite_offer_received.tpl');
		}
	}
	else
	{
		if($tpl == 'paypal')
		{
			$subject = $shopname .' Zahlungsinformationen';		
			$mailText = $smarty->fetch('wiki-plugins/wikiplugin_bobshop_mail_'. $tpl .'.tpl');
		}
	}

/*	//attachments?
	$doc = 'doccc';
	$id = md5(uniqid(time()));
	$doc = html_entity_decode($doc, ENT_COMPAT, 'UTF-8');
	$doc = utf8_encode($doc); 
	$dateiinhalt = $doc;
*/
	
	
		
	$header = "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: text/html; charset=utf-8\r\n";
	//$header .= "To: ". $mailReceiver ."\r\n";
	$header .= "From: ". $shopname ."<". $mailSender . ">\r\n";
	//mail to company will be sent after sending to the customer is ok
	//$header .= "Bcc: ". $shopname ."<". $shopConfig['bobshopConfigEmailNotifications'] . ">\r\n";
	$header .= "Reply-To: ". $shopname ."<". $mailSender . ">\r\n";
	
	
	$mail_send = mail($mailReceiver, $subject, $mailText, $header);

	if($mail_send)
	{
		$output = message('Info', 'Confirmation email was sent.');
		
		//send mail to company
		$header = "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: text/html; charset=utf-8\r\n";
		$header .= "From: ". $shopname ."<". $mailSender . ">\r\n";

		if($tpl == '')
		{
			if($showPrices)
			{
				$subject = $shopname ." - Info Bestelleingang\r\n";
				$mailText = $smarty->fetch('wiki-plugins/wikiplugin_bobshop_mail_order_received.tpl');
			}
			else
			{
				$subject = $shopname ." - Info Angebotsanfrage\r\n";
				$mailText = $smarty->fetch('wiki-plugins/wikiplugin_bobshop_mail_invite_offer_received.tpl');
			}
		}
		else
		{
			if($tpl == 'paypal')
			{
				$subject = $shopname .' Info Zahlungsinformationen';		
				$mailText = $smarty->fetch('wiki-plugins/wikiplugin_bobshop_mail_'. $tpl .'.tpl');
			}
		}		
		// send the mail
		$mail_send = mail($shopConfig['bobshopConfigEmailNotifications'], $subject, $mailText, $header);
	}
	else
	{
		$output = message('Error', 'Mail not sent!', 'errors');
	}	
	
	return $output;
}


/*
 * for debug print a array
 */
function printArray($name, $data)
{
	//print_r($data); echo '<hr>';
	if(!is_array($data))
	{
		echo '<h2>No Array given in: '. $name .'</h2>';
		return;
	}
	echo '<br><b>'. $name .'</b><br>';
	echo 'Array (<br>';
	foreach($data as $key=>$value)
	{
		if(is_array($value))
		{
			printArray($key .' >> ', $value);
		}
		else 
		{
			echo '&nbsp&nbsp&nbsp&nbsp'. $key . ' => '. $value .'<br>';
		}
	}
	echo ')<br>'; //array close
	echo '<hr>';
}

/*
 * returns the variants of a product
 */
function get_product_variants($product, $shopConfig)
{
	//is there a superset? then get the variants from there
	if(!empty($product['bobshopProductVariantSuperset']))
	{
		$productSuperset = get_tracker_shop_product_by_productId($product['bobshopProductVariantSuperset'], $shopConfig);
		$variantProductIds = explode('|', $productSuperset['bobshopProductVariantProductIds']);
	}
	else
	{
		$variantProductIds = explode('|', $product['bobshopProductVariantProductIds']);
	}
	//get the produts for the variants
	foreach($variantProductIds as $productId)
	{
		//echo 'aa'.$productId;
		if(!empty($productId))
		{
			$variants[$productId] = get_tracker_shop_product_by_productId($productId, $shopConfig);
		}
	}
	
	return $variants;
}

/*
 * returns the current date and time in sql format
 */
function date_time()
{
	date_default_timezone_set('Europe/Berlin');
	return date("Y-m-d H:i:s");
}


/*
 * format the orderId
 * eg: 1234%id%ABC|105|%'.05d
 * the last part is a sprintf
 */
function orderNumber_format($order, $shopConfig)
{
	//explode the format 
	$format = explode('|', $shopConfig['bobshopConfigOrderNumberFormat']);
	
	//add some value
	if(isset($format[1]))
	{
		$id = $order['bobshopOrderOrderNumber'] + $format[1];
	}
	
	//some more formating
	if(isset($format[2]))
	{
		$sid = sprintf($format[2], $id);
	}
	
	//replace the %id% with the new formated orderId
	$ret = str_replace('%id%', $sid, $format[0]);
		
	return $ret;
}

/*
 * used for the bobshop own user system
 * 
 * $shopConfig['bobshopConfigUserFields'] holds the fields for contact data
 * the fields are separated by |
 * * after fieldname is a requierd field
 * @ after fieldname is a email type field
 * 
 * returns a array with all fields for contact
 * 
 */

function bobshop_user_fields($shopConfig)
{
	$fieldArray = [];
	$fields = explode('|', $shopConfig['bobshopConfigUserFields']);
	foreach($fields as $key => $field)
	{
		//search for *
		$asterixPos = strpos($field, '*');
		$emailPos = strpos($field, '@');
		
		//remove useless stuff
		$fieldRaw = preg_replace('[@|\*]', '', $field);
		$fieldArray[] .= $fieldRaw;
		if($asterixPos)
		{
			$fieldArray[$fieldRaw][] .= 'required';
		}
		if($emailPos)
		{
			$fieldArray[$fieldRaw][] .= 'email';
			//speziel field for mail
			$fieldArray['mailReceiver']['userMailField'] = $fieldRaw;
		}

	}
	//print_r($fieldArray);
	return $fieldArray;
}


/*
 * used for the bobshop own user system
 * 
 * returns a array with user data ['Name'] => 'Jones' etc.
 */

function bobshop_user_data_decode($shopConfig)
{
	$text = base64_decode(get_tracker_shop_orders_order_by_orderNumber($shopConfig)['bobshopOrderBobshopUser']);

	if(!empty($text))
	{
		$data = decode_this($text);
		return json_decode($data, true);
	}
}


/*
 * returns the mailaddress from the bobshop own user system
 */
function bobshop_user_data_get_mail($data, $fields)
{
	$mail = $data[$fields['mailReceiver']['userMailField']];
	return $mail;
}

function bobshop_user_data_encode($userDataArray, $shopConfig)
{
	$order = get_tracker_shop_orders_order_by_orderNumber($shopConfig);
	$text = json_encode($userDataArray);
	$data = encode_this($text);
	update_tracker_shop_order_bobshop_user($order, base64_encode($data), $shopConfig);
}


/*
 * encode some data
 */
function encode_this($data)
{
	$secret = 'x=3i_w#';
	$algorithm = 'aes-128-cbc';
	//$iv = openssl_cipher_iv_length($algorithm);
	//$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algorithm));
	$iv = substr($secret . $secret, openssl_cipher_iv_length($algorithm));
	$data_encoded = openssl_encrypt(
			$data,
			$algorithm,
			$secret, 
			OPENSSL_RAW_DATA,
			$iv
			);
	return $data_encoded;
}

/*
 * decode some data
 */
function decode_this($data)
{
	$secret = 'x=3i_w#'; // >= 8 characters
	$algorithm = 'aes-128-cbc';
	//$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algorithm));
	$iv = substr($secret . $secret, openssl_cipher_iv_length($algorithm));
	$data_decoded = openssl_decrypt(
			$data,
			$algorithm,
			$secret, 
			OPENSSL_RAW_DATA,
			$iv
			);

	return $data_decoded;
}