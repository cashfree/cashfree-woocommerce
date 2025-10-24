=== Cashfree for WooCommerce ===
Contributors: devcashfree
Requires at least: 4.4
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 4.7.8
Version: 4.7.8
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Tags: wordpress,woocommerce,payment,gateway,cashfree

Official Cashfree Payment Gateway plugin for WooCommerce.

== Description ==

This is the official Cashfree Payment Gateway plugin for WooCommerce. By integrating this plugin with your WooCommerce store you can accept payments via 100+ domestic as well as international payment modes and use advanced features such as instant refunds for online and COD orders, pre-authorization for card payments, instant settlements, and more.

For more information about Cashfree Payments please go to https://cashfree.com.

== Frequently Asked Questions ==

= Is Cashfree Payments Plugin free? =

Yes! Cashfree Payments Plugin are and always will be free.

= domain.name is not enabled. Please reach out to cashfree? =

You might be facing this issue because your domain is not yet whitelisted. But don't worry, we've got you covered! To resolve this, simply click on the [link](https://docs.cashfree.com/docs/developers-whitelisting) provided to whitelist your domain.

= What should I do if I'm using the Cashfree Payments plugin on multiple WooCommerce domains and encounter duplicate order IDs? =

No worries, we've got a simple solution for you! If you're managing the Cashfree Payments plugin across multiple WooCommerce domains and you notice the possibility of duplicate order IDs due to sequential generation. To ensure a unique identification for orders originating from different domains, consider enabling the "Order ID Prefix" feature in the plugin's configuration settings.
Enabling the order ID prefix guarantees a seamless experience for you and your customers. By adding a unique prefix to each order ID, you'll effortlessly distinguish orders across your various domains.
Disabling the Enable Order Id Prefix after it's been enabled might break post-order flows. Consider the implications before making changes.
Please be mindful of the impact on your order management process before toggling this setting.

== Installation ==

Please note, this payment gateway requires WooCommerce 3.0 and above. To ensure seamless payment integration, it is necessary to comply with payment regulations by whitelisting your domain. Please click on the [link](https://docs.cashfree.com/docs/developers-whitelisting) provided to whitelist your domain.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of the WooCommerce Cashfree plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "Cashfree" and click Search Plugins. Once you’ve found our plugin you can install it by simply clicking "Install Now", then "Activate".

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

== Changelog ==

= 4.7.8 =
* Release: Updated for compatibility with the latest WordPress release.

= 4.7.6 =
* Release: Add support for Tourfic plugin for hotel, tour, and apartment bookings.

= 4.7.4 =
* Bug Fix: Order amount correction

= 4.7.3 =
* Deprecated Property Fix: Updated the cashfree classes to prevent deprecated warnings in PHP 8.2 and later by explicitly declaring properties instead of using dynamic properties.

= 4.7.2 =
* Added additional data to get trace of the request

= 4.7.1 =
* Bug Fix: UTF-8 encoding for `item_name` and `item_description` to ensure proper character encoding.

= 4.7.0 =
* New Feature: Added support for processing payments in a popup checkout without redirecting the user to a new page.

= 4.6.1 =
* Added COD (Cash on Delivery) payment mode to Cashfree Checkout.

= 4.6.0 =
* Make it compatible with woocommerce latest version

= 4.5.9 =
* Bug fixes

= 4.5.6 =
* Support checkout block to accept payments.

= 4.5.3 =
* Fixed issue preventing status update for canceled orders.

= 4.5.2 =
* Added compatibility with High-Performance order storage (COT).

= 4.5.0 =
* Change error message for cases where the domain name is not whitelisted.
* Introduce the ability to customize the order ID prefix. This feature is particularly useful when managing multiple stores with shared order sequences. By assigning a unique prefix to each store, you can maintain clear organization and easy distinction between orders.


= 4.4.6 =
* Bug fixes

= 4.4.5 =
* Update readme for support whitelist domain at cashfree.

= 4.4.4 =
* Update readme for support latest wordpress version

= 4.4.3 =
* Improved performance and stability
* Security enhancements
* Access to new features and functionality
* Bug fixes

= 4.4.2 =
* Handled same order id to get payments

= 4.4.1 =
* Improved UI for offer widgets

= 4.4.0 =
* Update cashfree payments offer widgets

= 4.3.9 =
* Add offer section to the product and checkout page

= 4.3.8 =
* Bugfix for error message in case of paylater payment method

= 4.3.7 =
* Update plugin description and add support link.

= 4.3.6 =
* Bugfix for hdfc pay later response while failure
* Add customer name for payment detail for merchant dashboard

= 4.3.5 =
* Bugfix for webhook failed

= 4.3.4 =
* Bugfix for order capture redirection
* Change cashfree default logo size

= 4.3.3 =
* Add description of gateway on checkout page
* Add transaction detail on order notes

= 4.3.2 =
* Tested upto wc 6.0.0
* Bugfix for storing data to cashfree api's

= 4.3.1 =
* Tested upto wc 5.9.3
* Add in context configuration to accept with and without redirecting page.
* Add magic checkout.

= 4.3.0 =
* Tested upto wc 5.9.2
* Accept order without redirecting customer to another page.

= 4.2.2 =
* Tested upto wc 5.3.0
* Bug fix for order update if order paid after checkout time.
* Added refund features in plugin.

= 4.2.1 =
* Updated WooCommerce version
* Tested upto wc 5.0.0
* Bug fixes for duplicate order and redirection issue.

= 1.3 =
* Updated WooCommerce version
* Updated changelog order and readme

= 1.2 = 
* Updated release on Plugins marketplace
* Improved error messaging

= 1.0 =
* First release on Plugins marketplace
