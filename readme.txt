=== Integration for Epos Now and WooCommerce ===
Contributors: slynkdigital
Tags: woocommerce,eposnow,integration,epos,stock sync,order sync, product sync, inventory sync
Requires at least: 4.9.8
Tested up to: 6.6.1
Requires PHP: 5.5
Stable tag: trunk
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate WooCommerce and Epos Now.

== Description ==

This WooCommerce plugin seamlessly integrates WooCommerce and Epos Now.

== Intro Video ==
https://www.youtube.com/watch?v=xVoqZtdbng8

= Main features =
* Sync products, stock, orders, refunds and customers
* Choose Epos Now or WooCommerce as the master for stock
* Stock updates within minutes
* Intelligent Product Linker
* Link one or many WooCommerce products to one Epos Now product
* Supports simple, variable, bundle & composite products
* Support for Epos Now measured, weighed, master and child products
* Support for Epos Now Online Order Printing
* Support for Epos Now Kitchen Display Screens (KDS)
* All the heavy lifting is handled on the Slynk servers, so your website keeps running fast optimising conversion
* Regular updates to our plugin and integration service to ensure compatibility with WordPress/WooCommerce/Epos Now updates
* No custom development needed to integrate your WooCommerce website with Epos Now
* No contract lock in, the integration service is on a rolling monthly subscription

Take a look at our <a href="https://slynk.io/epos-now-woocommerce-integration/?utm_source=wordpress.org&utm_medium=appstore&utm_campaign=epn_wc_listing" target="blank">website</a> for full details on the integration.

== Demo Video ==
https://www.youtube.com/watch?v=8hKGqqqBo1Y

> **IMPORTANT:** A subscription to the <a href="https://slynk.io/epos-now-woocommerce-integration/?utm_source=wordpress.org&utm_medium=appstore&utm_campaign=epn_wc_listing" target="blank">Slynk Epos Now WooCommerce Integration Service</a> is required. This is a companion plugin for the integration service.

== Installation ==

= Minimum Requirements =

* WooCommerce 4.4.2 or later
* WordPress 4.9.8 or later

= Automatic installation =
This is the easiest way to install the plugin. To perform an automatic installation log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "WooCommerce Epos Now Integration" and click Search Plugins. Click Install Now to begin the installation process. You will need to confirm that you want to install the plugin. After installation has completed, click the 'activate plugin' link.

= Manual installation via the WordPress interface =
1. Download the plugin zip file to your computer
2. Go to the WordPress admin panel menu Plugins > Add New
3. Choose upload
4. Upload the plugin zip file, the plugin will now be installed
5. After installation has finished, click the 'activate plugin' link

= Manual installation via FTP =
1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== Frequently Asked Questions ==

= Do I need a subscription to Slynk for the plugin to work? =

Yes, a subscription to the <a href="https://slynk.io/epos-now-woocommerce-integration/?utm_source=wordpress.org&utm_medium=appstore&utm_campaign=epn_wc_listing" target="blank">Slynk WooCommerce Epos Now Integration</a> service is required in order to sync. The service handles all the heavy lifting limiting any performance overhead on your WooCommerce website and processes all the data required to sync between Epos Now and WooCommerce.

= Where can I sign up to the integration service? =

Visit <a href="https://slynk.io/epos-now-woocommerce-integration/?utm_source=wordpress.org&utm_medium=appstore&utm_campaign=epn_wc_listing" target="blank">our website</a> for more details on the service and to sign up.

= Does this plugin work with PHP8.x =

Yes, it has been tested to work upto PHP 8.2

== Screenshots ==

1. Product Linker
2. Settings Screen
3. Product Linker Auto Linking

== Changelog ==
= 4.5.0 =
* Increased character limit for EN product name and description

= 4.4.4 =
* Fixed warning for bulk actions on products

= 4.4.3 =
* Bugfix post type in admin styles loading

= 4.4.2 =
* Bugfix for character encoding in EN description custom field

= 4.4.1 =
* Updated URL for new dashboard

= 4.4.0 =
* Added additional parameters to order sync endpoints
* Updated order meta data display in orders list

= 4.3.2 =
* Improved debug logging function

= 4.3.1 =
* Updated custom variations endpoint to use same status as parent product

= 4.3.0 =
* Added ability to bulk update Slynk custom fields for WooCommerce products

= 4.2.5 =
* Updated logic for setting woo master category for variable products
* Ensured that the sln_product_type meta is in lowercase if updated via the API

= 4.2.4 =
* Product meta update now includes more post statuses

= 4.2.3 =
* Added lb and 1/1000lb measurement units

= 4.2.2 =
* Bugfix for order processing request headers when called on cron

= 4.2.1 =
* Improved variation webhooks

= 4.2.0 =
* HPOS compatibility added
* Improved unsynced orders cron job
* Decimal values now allowed in unit multiplier
* Improved product webhooks

= 4.1.2 =
* Bugfix for order sync notes field when sanitizing the text

= 4.1.1 =
* Improved stock update detection for product webhooks

= 4.1.0 =
* Updated custom field order
* Added API endpoints for health checks

= 4.0.2 =
* Added description field to eposnow custom fields
* Added plugin version at bottom of settings page

= 4.0.1 =
* Bug fix for display of custom fields

= 4.0.0 =
* Improvements to webhook self-healing
* settings updates

= 3.9.3 =
* Bug fixes for when logs folder does not exist

= 3.9.2 =
* Updated master category setting for variations

= 3.9.1 =
* Bugfix for ignore product update and ignore stock update fields

= 3.9.0 =
* Updates to logging and log retention
* Updates to webhook health checks

= 3.8.3 =
* Added bug fix for disabling logging when option is not set

= 3.8.2 =
* Added bug fix for variation fields function declaration

= 3.8.1 =
* EposNow barcode added to custom fields

= 3.8.0 =
* New endpoint for order meta
* Additional custom fields for product sync

= 3.7.1 =
* Additional logging for webhooks

= 3.7.0 =
* Added support for WPML

= 3.6.1 =
* Additional API endpoint to fetch orders by product ID

= 3.6.0 =
* Additional API endpoint added for full stock sync

= 3.5.3 =
* Additional parameters added to slynk products endpoint

= 3.5.2 =
* Fix for empty log files

= 3.5.1 =
* Added extra check for ignore stock update

= 3.5.0 =
* Added API endpoint to fetch option values

= 3.4.0 =
* Added additional detection for published variations

= 3.3.2 =
* Updated API endpoint for processing full stock sync

= 3.3.1 =
* Added product meta save for more product types

= 3.3.0 =
* Added parent category to custom product variations end point
* Added parent category to custom product variations webhooks

= 3.2.9 =
* Updated tooltip text

= 3.2.8 =
* Added post meta setting for variations for master category
* Updated custom fetch products end point

= 3.2.7 =
* Added additional checks for blank webhooks in Woo

= 3.2.6 =
* Added additional check for blank webhooks in Woo

= 3.2.5 =
* Added check to make sure variation is returned before slynk meta data is saved

= 3.2.4 =
* Field visibility for measured products shown based on product type

= 3.2.3 =
* Additional checks for bundled and composite products added

= 3.2.2 =
* Added yards to measured products in the inches category

= 3.2.1 =
* Added yards to measurement units

= 3.2.0 =
* Added master product category fields to product meta

= 3.1.1 =
* Added check to see if valid variation is returned on variations endpoint

= 3.1.0 =
* Product CRUD webhooks now work with scheduled actions

= 3.0.9 =
* Added error handling for woo function to get webhook topic

= 3.0.8 =
* Added logging options
* Updated settings UI

= 3.0.7 =
* Fetch status from variable product for variations

= 3.0.6 =
* Fetch SKU from post meta for variable products as Woo reports parent SKU if SKU not set at variation level

= 3.0.5 =
* Added custom endpoint for WC products

= 3.0.4 =
* Webhook improvements

= 3.0.3 =
* Bug fix for webhook delivery

= 3.0.2 =
* Bug fix for webhook delivery

= 3.0.1 =
* Product sync bug fixes
* Fixed payload for deleted product webhook
* Fixed refund webhook payload for deleted products

= 3.0.0 =
* Product sync beta features added

= 2.5.8 =
* Added extra logging
* Added option to clear object cache when processing full stock sync

= 2.5.7 =
* Added additional info to webhook payload for bundle/composite products

= 2.5.6 =
* Added check for WP_Error on webhook response
* Tested with WP 5.8.1
* Tested with WC 5.6.0

= 2.5.5 =
* Added shipping tax line ids as extra check
* Tested with WC 5.5.1

= 2.5.4 =
* Updated function names
* Tested with WC 5.4.0

= 2.5.3 =
* Added ability to set the time interval for the orders cron

= 2.5.2 =
* Refunds feature activation bug fix

= 2.5.1 =
* Added setting to add headers for CORS if required

= 2.5.0 =
* Added refunds to integration

= 2.4.1 =
* Updated compatibility with WP 5.6.1 and WC 5.0.0

= 2.4.0 =
* Added filter for webhook payload

= 2.3.1 =
* Bugfix for datepicker on settings page

= 2.3.0 =
* Added feature to be able to select which order statuses to sync to EposNow
* Updated settings page UI

= 2.2.0 =
* Updated plugin name
* Added additional sanitization

= 2.1.7 =
* Added permission_callback parameter to API endpoints

= 2.1.6 =
* Added option to add more product data to the orders webhook
* Tested with WooCommerce 4.4 Release Candidate
* Tested with WordPress 5.5

= 2.1.5 =
* Added orderby ID parameter to API endpoint for retrieving variable products

= 2.1.4 =
* Fix to fetch missing tax rate ids from line items

= 2.1.3 =
* Tested with WooCommerce 4.2.0
* Fix for fetching more than 100 variations per product

= 2.1.2 =
* Added per page parameter into API calls for WC variations

= 2.1.1 =
* Minor bug fix for logging
* Tested with WooCommerce 4.0

= 2.1.0 =
* Added settings and functionality to suppress order emails if WooCommerce is the master and the WooCommerce order is created from an Epos Now transaction
* Tested for compatibility with WC 3.8.1
* Tested for compatibility with WP 5.3.1

= 2.0.4 =
* Added extra context for API responses

= 2.0.3 =
* Improvements on on order sync status updates

= 2.0.2 =
* Added additional checks for stock webhooks

= 2.0.1 =
* Fixed bug with webhook activation checks

= 2.0.0 =
* Added support for having WooCommerce or Epos Now as the master
* Improved checks for disabled order sync
* Performance improvements

= 1.0.10 =
* Strengthened checks for sync order after setting

= 1.0.9 =
* Added log viewer
* Added additional logging
* Improvements to cron job to catch orders not sent via webhook successfully

= 1.0.8 =
* Tested with WC 3.7.0
* Improved product variations retrieval
* Updated log file names for full stock sync

= 1.0.7 =
* Added additional info into webhook payload to reduce the number of API calls required to WooCommerce
* Added epos now loyalty points balance user meta field in user profile

= 1.0.6 =
* Improvements to initialisation and webhook delivery

= 1.0.5 =
* Improvements and bug fixes for plugin activation processes

= 1.0.4 =
* Improvements in stock sync
* Added ability to filter orders by customer role
* Development and debugging mode updated

= 1.0.3 =
* Added pagination to variable products API end point

= 1.0.2 =
* Fixed bug in full stock sync process

= 1.0.1 =
* Improved activation processes
* Added cron jobs to check webhooks

= 1.0.0 =
* Public release of plugin

== Upgrade Notice ==
= 1.0.1 =
Improvements in activation processes