# BobShop
Shopping cart plugin for TikiWiki

BobShop can also be used as an easy product presentation system. See operation-modes in Wiki.

release v1_7_1 available

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

## What bobshop can not do at this time:
- inventory management (comes with 1_8_x)
- invoices
- gifts
- product presentation in categories (you can use tikiwiki functionality to do that > structures, catergories, menus etc.)

## How to install:
To install the trackers and wiki pages you can use the BobShop Profile:
http://profiles.tiki.org/BobShop

- upload the icons for payment to your file-gallery
- for PayPal > edit "/lib/wiki-plugins/wikiplugin_bobshop_paypal_inc.php" and place your REST API app credentials

## Add the "Add to cart" Button
Add the following in your wikisite.
bobshop type="add_to_cart_button" productId="1004"}

## BobShop DEMO
https://bobshopdemo.bob360.de


More details are soon available

