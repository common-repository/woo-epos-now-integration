<?php

add_action( 'admin_enqueue_scripts', 'sew_admin_enqueue' );
function sew_admin_enqueue($hook) {
    if('eposnow_page_log-settings' == $hook || 'toplevel_page_sew-settings' == $hook){
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
        wp_enqueue_style( 'log_settings', plugins_url('css/sew-admin-style.css', __DIR__));
        wp_enqueue_script( 'sew-functions', plugins_url( 'js/sew-functions.js', __DIR__) );
    }

    if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php') {
        global $post;
        if ( isset($post) && is_object($post) && 'product' === $post->post_type ) {
            wp_enqueue_style( 'product_admin_style', plugins_url('css/product-admin-style.css', __DIR__));
        }
    }
}


add_action('admin_init', 'sew_api_options_init' );
// Init plugin settings options
function sew_api_options_init(){
	register_setting( 'sew_settings', 'sew_api_key' );
	register_setting( 'sew_settings', 'sew_api_secret' );
	register_setting( 'sew_settings', 'sew_api_base_url_dev' );
	register_setting( 'sew_settings', 'sew_stock_batch_size' );
	register_setting( 'sew_settings', 'sew_sync_orders_after' );
	register_setting( 'sew_settings', 'sew_sync_refund_orders_after' );
    register_setting( 'sew_settings', 'sew_unsynced_orders_refunds_days_in_past');
    register_setting( 'sew_settings', 'sew_sync_user_role' );
    register_setting( 'sew_settings', 'sew_full_product_data_orders' );
	register_setting( 'sew_settings', 'sew_disable_order_sync' );
	register_setting( 'sew_settings', 'sew_disable_refund_order_sync' );
	register_setting( 'sew_settings', 'sew_log_enabled' );
	register_setting( 'sew_settings', 'sew_cache_clear_enabled' );
	register_setting( 'sew_settings', 'sew_product_sync_disabled' );
	register_setting( 'sew_settings', 'sew_product_sync_draft_status_enabled' );
	register_setting( 'sew_settings', 'sew_suppress_order_emails_eposnow_orders' );
    register_setting( 'sew_settings', 'sew_wc_master' );
	register_setting( 'sew_settings', 'sew_development_mode' );
	register_setting( 'sew_settings', 'sew_valid_order_statuses' );
    register_setting( 'sew_settings', 'sew_add_cors_headers' );
    register_setting( 'sew_settings', 'sew_log_all_api_requests' );
    register_setting( 'sew_settings', 'sew_product_webhook_delay' );
    register_setting( 'sew_settings', 'sew_log_retention_days' );
    register_setting( 'sew_settings', 'sew_platform_v2' );
    register_setting( 'sew_settings', 'sew_webhook_user_id' );
    register_setting( 'sew_settings', 'sew_cron_interval', 'cron_setup_callback' );

    //Meta fields show/hide
    register_setting( 'sew_settings', 'sew_ignore_stock_update_enabled' );
    register_setting( 'sew_settings', 'sew_ignore_product_update_enabled' );
    register_setting( 'sew_settings', 'sew_epn_title_enabled' );
    register_setting( 'sew_settings', 'sew_product_type_enabled' );
    register_setting( 'sew_settings', 'sew_unit_multiplier_enabled' );
    register_setting( 'sew_settings', 'sew_product_category_master_enabled' );
    register_setting( 'sew_settings', 'sew_epn_cost_price_enabled' );
    register_setting( 'sew_settings', 'sew_epn_eat_out_price_enabled' );
    register_setting( 'sew_settings', 'sew_epn_rrp_price_enabled' );
    register_setting( 'sew_settings', 'sew_epn_barcode_enabled' );
    register_setting( 'sew_settings', 'sew_epn_order_code_enabled' );
    register_setting( 'sew_settings', 'sew_epn_article_code_enabled' );
    register_setting( 'sew_settings', 'sew_epn_brand_id_enabled' );
    register_setting( 'sew_settings', 'sew_epn_supplier_id_enabled' );
    register_setting( 'sew_settings', 'sew_epn_tare_weight_enabled' );
    register_setting( 'sew_settings', 'sew_epn_size_enabled' );
    register_setting( 'sew_settings', 'sew_epn_description_enabled' );

    if(!empty($_GET['page']) && $_GET['page'] == 'sew-settings'){
        add_filter('admin_footer_text', 'sew_add_admin_footer');
    }
}

function sew_add_admin_footer($footer_text) {
    //Add Slynk plugin version only for settings page
    if( !empty( $footer_text ) ) {
        $footer_text .= ' | ';
    }
    $footer_text .= SLYNK_EW_PLUGIN_NAME." Version ".SLYNK_EW_PLUGIN_VERSION;

    return $footer_text;
}


add_action('admin_menu','sew_api_admin_menu');
function sew_api_admin_menu() {

	add_menu_page(
		"Slynk Epos Now WooCommerce Integration",
		"EposNow",
		"manage_options",
		"sew-settings",
		"sew_admin_menu_settings_display",
		"dashicons-controls-repeat"
	);

    add_submenu_page(
        "sew-settings",
        'Logs',
        'Logs',
        'manage_options',
        'slynk-epn-wc-logs',
        'sew_admin_menu_log_settings_display'
    );

}

function sew_admin_menu_log_settings_display(){
    include (plugin_dir_path( __FILE__ )."sew-admin-log-settings.php");
}

function sew_admin_menu_main_display(){
	echo "Slynk EposNow WooCommerce Integration Settings.";
}


function sew_admin_menu_settings_display(){
	include (plugin_dir_path( __FILE__ )."sew-admin-settings.php");
}

function sew_checkSetLogFile($logFileSelected,$wpLogDirPath){
    $logFileContent = '';
    $file_pointer = $wpLogDirPath.$logFileSelected;
    if(file_exists($file_pointer)){
        $logFileContent = file_get_contents($file_pointer );
    }
    return $logFileContent;
}

function sew_deleteLogFile($delLogFileSelected,$wpLogDirPath, $nonce){
    $result = false;
    if($delLogFileSelected == 'all'){
        $files = glob($wpLogDirPath.'*'); // get all file names
        foreach($files as $file){
            if(is_file($file))
                $result = unlink($file); // delete file
        }
    }else{
        $file_pointer = $wpLogDirPath.$delLogFileSelected;
        if(file_exists($file_pointer) && wp_verify_nonce( $nonce, 'delete_log-' . $delLogFileSelected)){
            $result = unlink($file_pointer);
        }
    }
    return $result;
}

//call back function while setting update
function cron_setup_callback($input){

    global $sew_plugin_settings;

    $cron_interval = get_option('sew_cron_interval', true);
    //here we are checking that current cron time interval and newly saved cron time interval is difference than needs to remove cron and execute new cron.
    if($cron_interval !== $input){
        $sew_plugin_settings['cron_interval'] = $input;
        //clear sew_cron_order_sync hook to set new interval
        wp_clear_scheduled_hook('sew_cron_order_sync');
        sew_setup_cron_jobs();
    }
    return $input;
}