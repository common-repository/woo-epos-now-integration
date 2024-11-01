<?php
/*
Plugin Name:            Integration for Epos Now and WooCommerce
Plugin URI:             https://slynk.io/epos-now-woocommerce-integration/
Description:            Integration for syncing orders, products, stock and customers between WooCommerce and Epos Now
Version:                4.5.0
Author:                 Slynk Digital
Author URI:             https://www.slynk.io
License:                GPL2
License URI:            https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least:   4.5.0
WC tested up to:        9.2.3
*/

//CONSTANTS
define('SLYNK_EW_PLUGIN_VERSION','4.5.0');
define('SLYNK_EW_PLUGIN_NAME','Integration for Epos Now and WooCommerce');
define('SLYNK_EW_PLUGIN_PATH',basename(dirname(__FILE__)).'/'.basename(__FILE__));
define('SLYNK_EW_MIN_WP_VERSION','4.9.8');
define('SLYNK_EW_MIN_WC_VERSION','4.4.2');

//Settings
$sew_plugin_settings = array();

function sew_initialise($call_by_ajax=false){

	global $sew_plugin_settings;

    $sew_platform_v2_flag = (bool) esc_attr( get_option( 'sew_platform_v2', false) );

	$sew_plugin_settings['authentication']           = array();
	$sew_plugin_settings['authentication']['key']    = esc_attr( get_option( 'sew_api_key' ) );
	$sew_plugin_settings['authentication']['secret'] = esc_attr( get_option( 'sew_api_secret' ) );

    if(!get_option('sew_disable_refund_order_sync')){
        add_option( 'sew_disable_refund_order_sync', 1 );
    }

    if(!get_option('sew_product_sync_disabled')){
        add_option( 'sew_product_sync_disabled', 1 );
    }

	$sew_plugin_settings['sync_orders_after'] = date( 'Y-m-d\T00:00:00', strtotime( esc_attr( get_option( 'sew_sync_orders_after' ) ) ) );

	$sew_plugin_settings['max_orders_to_process_batch'] = 100;

	$sew_plugin_settings['sew_stock_batch_size'] = esc_attr( get_option( 'sew_stock_batch_size' ) );
	$sew_plugin_settings['sew_sync_user_role']   = esc_attr( get_option( 'sew_sync_user_role' ) );
	$sew_plugin_settings['sew_full_product_data_orders']   = esc_attr( get_option( 'sew_full_product_data_orders' ) );

	$sew_plugin_settings['logging_enabled']    = esc_attr( get_option( 'sew_log_enabled' ) );

	if(!get_option('sew_log_all_api_requests')){
		add_option( 'sew_log_all_api_requests', 0 );
	}

	if(!esc_attr(get_option('sew_product_webhook_delay'))){
		add_option( 'sew_product_webhook_delay', 180 );
	}

	$sew_plugin_settings['sew_product_webhook_delay']    = esc_attr( get_option( 'sew_product_webhook_delay' ) );

	$sew_plugin_settings['sew_log_all_api_requests']    = esc_attr( get_option( 'sew_log_all_api_requests' ) );

	$sew_plugin_settings['sew_add_cors_headers']    = esc_attr( get_option( 'sew_add_cors_headers' ) );
	$sew_plugin_settings['product_sync_disabled']    = esc_attr( get_option( 'sew_product_sync_disabled', 0) );
	$sew_plugin_settings['product_sync_draft_status_enabled']    = esc_attr( get_option( 'sew_product_sync_draft_status_enabled' ) );

	$sew_plugin_settings['cache_clear_enabled']    = esc_attr( get_option( 'sew_cache_clear_enabled' ) );
	$sew_plugin_settings['disable_order_sync'] = esc_attr( get_option( 'sew_disable_order_sync' ) );
	$sew_plugin_settings['disable_refund_order_sync'] = esc_attr( get_option( 'sew_disable_refund_order_sync' ) );
	if(!empty(esc_attr( get_option( 'sew_sync_refund_orders_after' ) ) )){
        $sew_plugin_settings['sync_refund_orders_after'] = date( 'Y-m-d\T00:00:00', strtotime( esc_attr( get_option( 'sew_sync_refund_orders_after' ) ) ) );
    }else{
        $sew_plugin_settings['sync_refund_orders_after'] = esc_attr( get_option( 'sew_sync_refund_orders_after' ) ) ;
    }

    $sew_plugin_settings['sew_unsynced_orders_refunds_days_in_past'] = (int) esc_attr( get_option('sew_unsynced_orders_refunds_days_in_past', 30) );

	//get cron interval settings
    $sew_cron_interval = esc_attr(get_option('sew_cron_interval'));
	//check cron interval settings set hourly as default
    $sew_plugin_settings['cron_interval'] = empty($sew_cron_interval)? 'hourly' : $sew_cron_interval;
    if(empty($sew_cron_interval)){
        //if empty sew cron interval then add option as hourly
        add_option( 'sew_cron_interval', 'hourly');
        //clear out old cron hooks
        wp_clear_scheduled_hook('sew_cron_setup_hourly');
        wp_clear_scheduled_hook('sew_cron_setup_5_mins');
        //set up new crons
        sew_setup_cron_jobs();
    }

	$sew_plugin_settings['wc_master']   = esc_attr( get_option( 'sew_wc_master' ) );

    $sew_plugin_settings['sew_suppress_order_emails_eposnow_orders'] = esc_attr( get_option( 'sew_suppress_order_emails_eposnow_orders' ) );
    $sew_plugin_settings['sew_disable_staging_detection'] = esc_attr(get_option('sew_disable_staging_detection'));

    //default order statuses if nothing is set on the settings page
    $sew_plugin_settings['valid_order_statuses']   = array();
    $sew_plugin_settings['valid_order_statuses'][] = 'processing';
    $sew_plugin_settings['valid_order_statuses'][] = 'completed';
    if($sew_plugin_settings['disable_refund_order_sync'] != 1){
        $sew_plugin_settings['valid_order_statuses'][] = 'refunded';
    }

    $order_statuses = get_option('sew_valid_order_statuses');
    if(!empty($order_statuses)){
        $sew_plugin_settings['valid_order_statuses'] = [];
        $sew_plugin_settings['valid_order_statuses'] = $order_statuses;
    }

    $sew_plugin_settings['development_mode'] = esc_attr( get_option('sew_development_mode') );

    $sew_plugin_settings['webhook_settings']['delivery_url_legacy'] = 'https://epn-ds.slynk.io/api/v1/woocommerce';
    $sew_plugin_settings['webhook_settings']['delivery_url_v2'] = 'https://ext-api.slynk.io/api/v1/woocommerce';

	if ( $sew_plugin_settings['development_mode'] == 1 ) {
          	$sew_plugin_settings['webhook_settings']['delivery_url'] = esc_attr(get_option('sew_api_base_url_dev'));
    } else {
        if ($sew_platform_v2_flag) { // For live
            $sew_plugin_settings['webhook_settings']['delivery_url'] = $sew_plugin_settings['webhook_settings']['delivery_url_v2'];
        } else {
            $sew_plugin_settings['webhook_settings']['delivery_url'] = $sew_plugin_settings['webhook_settings']['delivery_url_legacy'];
        }
    }

	$sew_plugin_settings['webhook_settings']['api_version']              = 3;
	$sew_plugin_settings['webhook_settings']['delete_duplicate_webhook'] = false;

	$sew_plugin_settings['webhook_settings']['topics']   = array();
	$sew_plugin_settings['webhook_settings']['topics'][] = 'order.sew_filter';
	$sew_plugin_settings['webhook_settings']['topics'][] = 'order.sew_refund_filter';
    $sew_plugin_settings['webhook_settings']['topics'][] = 'product.sew_stock_track';
    $sew_plugin_settings['webhook_settings']['topics'][] = 'product.sew_updated';
    $sew_plugin_settings['webhook_settings']['topics'][] = 'product.sew_deleted';
    $sew_plugin_settings['webhook_settings']['topics'][] = 'product.sew_created';

    $sew_plugin_settings['webhook_settings']['interval_for_product_update_webhook'] = 3;

    //only show the extra products tab if product sync is enabled
    if(!$sew_plugin_settings['product_sync_disabled'] && !$call_by_ajax) {
        require(plugin_dir_path(__FILE__) . 'admin/sew-product-extra-tab.php');
    }
}

add_action( 'init', 'sew_initialise' );

//add all the other plugin files
require( 'inc/debug.php' );
require( 'inc/activation.php' );
require( 'inc/cron.php' );
require( 'inc/functions.php' );
require( 'inc/webhooks.php' );
require( 'inc/api.php' );
require( 'inc/wc-orders.php' );
require( 'inc/wc-products.php' );
require( 'inc/wc-stock.php' );
require( 'inc/wc-taxes.php' );
require( 'inc/users.php' );

// Load pages for admin view like settings, options, etc...
if (is_admin()) {
	require (plugin_dir_path( __FILE__ ) . 'admin/sew-admin.php');
}

//leave the activation hooks here
//doesn't work from included files on some servers
register_activation_hook( SLYNK_EW_PLUGIN_PATH , 'sew_plugin_activate_set_flag' );
register_deactivation_hook(SLYNK_EW_PLUGIN_PATH, 'sew_plugin_deactivate');

//use wp option to trigger activation function, works more reliably as all global variables are then also loaded
function sew_plugin_activate_set_flag(){
	update_option( 'sew_plugin_run_activation_function', 1 );
    update_option('slynk_ew_plugin_version', SLYNK_EW_PLUGIN_VERSION); // added in V3.2.7
}

add_action( 'plugins_loaded', 'sew_init_hooks', - PHP_INT_MAX );
function sew_init_hooks(){
    add_action( 'before_woocommerce_init', 'sew_declare_wc_compatibility');
}
/**
 * Declare compatibility with WC features.
 *
 * @return void
 */
function sew_declare_wc_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}