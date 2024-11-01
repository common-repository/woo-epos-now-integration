<?php

function sew_plugin_activate() {

	if ( is_admin() && esc_attr(get_option( 'sew_plugin_run_activation_function' )) == 1 ) {

        sew_log_data('activation','starting activation process', false);

		update_option( 'sew_plugin_run_activation_function', 0);

		//Check WC activated and at min version
		if ( ! sew_check_activation() ) {
			sew_log_data( 'activation', 'sew_check_activation failed', false );

			return;
		}
		sew_log_data( 'activation', 'sew_check_activation passed', false );

		//setup our cron jobs
		sew_setup_cron_jobs();

		//setup our webhooks
		//requires woocommerce to be activated
		sew_check_webhooks();

		//setup default options
		sew_setup_options();

	}

}
add_action( 'admin_init', 'sew_plugin_activate' );

function sew_plugin_deactivate() {
	//clear out all the cron jobs
	sew_remove_cron_jobs();
	delete_option('sew_plugin_run_activation_function');
	//Check WC activated
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		return;
	}

	//remove all our webhooks
	//requires woocommerce to be activated
	sew_delete_all_webhooks();
}


add_action('update_option_sew_platform_v2', 'sew_check_platform_v2', 10, 2);
add_action('update_option_sew_development_mode', 'sew_check_platform_v2', 10, 2);

function sew_check_platform_v2($old_value, $new_value){
    if($old_value !== $new_value) {
        sew_initialise(true);
        sew_delete_all_webhooks();
        sew_check_webhooks();
    }
}


function sew_check_activation(){

	$activation_check = sew_check_versions();

	if($activation_check['result'] != true){

		if (is_plugin_active( 'woo-epos-now-integration/woo-epos-now-integration.php' ) ) {

			sew_log_data('activation', 'deactivating plugin '.SLYNK_EW_PLUGIN_PATH.' as minimum version checks and activations of dependent plugins not passed.');

			deactivate_plugins( plugin_basename( SLYNK_EW_PLUGIN_PATH ) );

			//show notice in admin
			$error_message = implode( '. ', $activation_check['error_messages'] );

			add_action( 'admin_notices', function () use ( $error_message ) {
				echo '<div class="notice notice-error"><p>' . $error_message . '</p></div>';
			} );

		}
		return false;
	}

	return true;
}
add_action( 'admin_init','sew_check_activation');

function sew_check_versions() {

	$activation_check['result']         = true;
	$activation_check['error_messages'] = array();

	//check WP version
	if ( version_compare( $GLOBALS['wp_version'], SLYNK_EW_MIN_WP_VERSION, '<' ) ) {

		$activation_check['result'] = false;

		$activation_check['error_messages'][] = SLYNK_EW_PLUGIN_NAME . ' only works on WordPress version ' . SLYNK_EW_MIN_WP_VERSION . ' and later. Please update WordPress and then try again';

	}

	//check WooCommerce Activated
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

		$activation_check['result'] = false;

		$activation_check['error_messages'][] = SLYNK_EW_PLUGIN_NAME . ' only works with WooCommerce version ' . SLYNK_EW_MIN_WC_VERSION . ' and later and requires WooCommerce to be activated. Please update/activate WooCommerce and reactivate our plugin';

	}else{
		global $woocommerce;
		if ( version_compare( $woocommerce->version, SLYNK_EW_MIN_WC_VERSION, "<" ) ) {

			$activation_check['result'] = false;

			$activation_check['error_messages'][] = SLYNK_EW_PLUGIN_NAME . ' only works with WooCommerce version ' . SLYNK_EW_MIN_WC_VERSION . ' and later. Please update WooCommerce and reactivate our plugin';

		}
	}

	return $activation_check;
}

function sew_setup_options(){

	if(!esc_attr(get_option('sew_stock_received_at'))){
		update_option('sew_stock_received_at', date("Y-m-d H:i:s"));
        //add a blank stock file
        sew_product_stock_update_json_file('[]');
	}

	if(!esc_attr(get_option('sew_last_stock_element_processed'))){
		update_option('sew_last_stock_element_processed', -1);
	}

	if(!esc_attr(get_option('sew_stock_all_processed'))){
		update_option('sew_stock_all_processed', 1);
	}

	if(!esc_attr(get_option('sew_stock_batch_size'))){
		update_option('sew_stock_batch_size', 250);
	}

    if(get_option('sew_product_sync_disabled', 'no') === 'no'){
        update_option('sew_product_sync_disabled', 1);
    }

    if(get_option('sew_ignore_stock_update_enabled', 'no') === 'no'){
        update_option('sew_ignore_stock_update_enabled', 1);
    }
    if(get_option('sew_ignore_product_update_enabled', 'no') === 'no'){
        update_option('sew_ignore_product_update_enabled', 1);
    }
    if(get_option('sew_epn_title_enabled', 'no') === 'no'){
        update_option('sew_epn_title_enabled', 1);
    }
    if(get_option('sew_product_type_enabled', 'no') === 'no'){
        update_option('sew_product_type_enabled', 1);
    }
    if(get_option('sew_unit_multiplier_enabled', 'no') === 'no'){
        update_option('sew_unit_multiplier_enabled', 1);
    }
    if(get_option('sew_product_category_master_enabled', 'no') === 'no'){
        update_option('sew_product_category_master_enabled', 1);
    }
    if(get_option('sew_epn_cost_price_enabled', 'no') === 'no'){
        update_option('sew_epn_cost_price_enabled', 1);
    }
    if(get_option('sew_epn_eat_out_price_enabled', 'no') === 'no'){
        update_option('sew_epn_eat_out_price_enabled', 1);
    }
    if(get_option('sew_epn_rrp_price_enabled', 'no') === 'no'){
        update_option('sew_epn_rrp_price_enabled', 1);
    }
    if(get_option('sew_epn_barcode_enabled', 'no') === 'no'){
        update_option('sew_epn_barcode_enabled', 1);
    }
    if(get_option('sew_epn_order_code_enabled', 'no') === 'no'){
        update_option('sew_epn_order_code_enabled', 1);
    }
    if(get_option('sew_epn_article_code_enabled', 'no') === 'no'){
        update_option('sew_epn_article_code_enabled', 1);
    }
    if(get_option('sew_epn_brand_id_enabled', 'no') === 'no'){
        update_option('sew_epn_brand_id_enabled', 1);
    }
    if(get_option('sew_epn_supplier_id_enabled', 'no') === 'no'){
        update_option('sew_epn_supplier_id_enabled', 1);
    }
    if(get_option('sew_epn_tare_weight_enabled', 'no') === 'no'){
        update_option('sew_epn_tare_weight_enabled', 1);
    }
    if(get_option('sew_epn_size_enabled', 'no') === 'no'){
        update_option('sew_epn_size_enabled', 1);
    }

    if(!esc_attr(get_option('sew_log_retention_days'))){
        update_option('sew_log_retention_days', 7);
    }

    if(!esc_attr(get_option('sew_unsynced_orders_refunds_days_in_past'))){
        update_option('sew_unsynced_orders_refunds_days_in_past', 30);
    }

    if(!esc_attr(get_option('sew_webhook_user_id'))){
        update_option('sew_webhook_user_id', get_current_user_id());
    }


}