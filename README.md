# BobShop
Shopping cart plugin for TikiWiki

BobShop can also be used as an easy product presentation system. See operation-modes in Wiki.

release v1_91_0 available

## Features
- easy to install and configure
- no need to login to use the shopping-cart 
- individual product fields
- porducts can be enabled/disabled
- multiple payment methodes (PayPal integrated)
- 3 different tax rates
- 3 different shipping costs
- "addToCart"-button can placed on any wikipage
- built-in registration and login system (seamless cart - login/register - purchase flow)
- works _without_ the TikiWiki Functions/Plugins Payment, shopper_info, Shopping Cart etc.
- templates can be modified easy
- bootstrap is used for the templates
- different operation modes (default, sandbox, offer, presentation, info)
- saving and reloading the cart by memoryCode (a cart can be transferd to another user/account/browser)
- by memoryCode you can also create a kind of "share this cart" link
- sorting in products list by name, relevance, price up/down
- stock control system (inventory management)
- shopping without the tiki user system (user data is saved encrypted in the orders tracker)
- product quantity in the cart can be modified by input field
- individual pages/messages after submitting the order/cart
- product variations (eg. small, medium, large or red, blue, green)
- custom language file for easy translating in your language
- admin panel for orders and carts (be sure to set the permissions of this page)
- order number can be formated to suggest your customers a very big shop ;)

## What bobshop can not do at this time:
- invoices
- gifts

## How to install:
To install the trackers and wiki pages you can use the BobShop Profile:
http://profiles.tiki.org/BobShop

- upload the icons for payment to your file-gallery
- for PayPal > edit "/lib/wiki-plugins/wikiplugin_bobshop_paypal_inc.php" and place your REST API app credentials

## Add the "Add to cart" Button
Add the following in your wikisite.
{bobshop type="add_to_cart_button" productId="1004"}

## BobShop DEMO
More details are available at the BobShop DEMO site
https://bobshopdemo.bob360.de

## Manual/Documentation
https://bobshopdemo.bob360.de/Manual

