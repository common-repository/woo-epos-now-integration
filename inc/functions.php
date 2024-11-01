<?php
/**
 * General functions
 */

function sew_print_pre($content){
	return '<pre>'.$content.'</pre>';
}


function sew_get_super_admin_id(){

	//work out the ID for the super admin
	$super_admins = get_super_admins();

	foreach ($super_admins as $admin) {

		$super_admin = get_user_by('login', $admin);

		if(!empty($super_admin)){
			$super_admin_id = $super_admin->ID;
		}

		break;
	}

	if(empty($super_admin_id)){
		$super_admin_id = 1;
	}

	return $super_admin_id;

}

// Remove initial character from woocommerce order statuses key/string
// woocommerce order status always have wc- prefix i.e completed have wc-completed
function sew_filter_wc_order_status_keys($array = []){
    if(empty($array)) return;
    $return = [];
    foreach ($array as $wc_slug => $wc_title){
        $wc_slug = substr($wc_slug, 3); // removes wc- from string
        $return[$wc_slug] = $wc_title;
    }
    return $return;
}

function sew_schedule_single_event($event_name, $hook, $args= array( false ), $time_diff=0){

	$timestamp = time() + $time_diff;

	sew_log_data('scheduled_actions', "sew_schedule_single_event called for event: $event_name, hook: $hook, time_diff: $time_diff");
	sew_log_data('scheduled_actions', $args, true);
	sew_log_data('scheduled_actions', "timestamp: $timestamp");

	//check if the action is already scheduled
	if ( !WC()->queue()->get_next($hook,$args, 'sew_product_webhooks') ) {
		//event not scheduled so we can schedule it
		sew_log_data('scheduled_actions', "scheduling event");
		WC()->queue()->schedule_single($timestamp,$hook,$args, 'sew_product_webhooks');
	}
}

function sew_get_woo_categories($selected_value = ''){
	$args = array(
		'id'                => 'sln_product_category_master',
		'name'              => 'sln_product_category_master',
		'class'             => 'form-field short',
		'taxonomy'          => "product_cat",
		'hide_empty'        => 0,
		'hierarchical'      => 1,
		'echo'              => 0,
		'show_option_none'  => 'Select master category',
		'option_none_value' => '-1',
		'orderby'           => 'name',
		'order'             => 'ASC'
	);

	if($selected_value){
		$args['selected'] = $selected_value;
	}

/*	$product_categories = get_terms($args);

	return $product_categories;*/

	$cat_select = wp_dropdown_categories($args);
/*	sew_log_data('product_cat','post id:'.get_the_id());
	sew_log_data('product_cat',$cat_select);*/

	return $cat_select;
}

function sew_get_wp_options($request){

	if(!sew_api_authentication()){
		return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	$query_params = $request->get_query_params();

	if(empty($query_params['option_name'])){
		return new WP_REST_Response('no option_name specified',500);
	}

	if(empty($query_params['search_type'])){
		$query_params['search_type'] = 'equal';
	}

	global $wpdb;

	$query = 'SELECT * FROM '.$wpdb->prefix . 'options
    WHERE option_name ';

	if($query_params['search_type'] == 'like'){
		$query .= 'LIKE "%'.$query_params['option_name'].'%"';
	}else{
		$query .= '="'.$query_params['option_name'].'"';
	}

	$options = $wpdb->get_results($query, ARRAY_A );

	return new WP_REST_Response($options,200);

}

function sew_update_options($request){
    sew_log_data('api', $_SERVER, true);
    sew_log_data('api', $request, true);

    $result = array();

    if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
    }
    //define default null array to collect result
    $updated = [];
    $failed = [];

    // define valid slynk key prefixes
    $prefixToCheck = ['sew', 'sln'];
    //Get body data from request
    $sew_options = json_decode($request->get_body(), true);
    sew_log_data('api', $sew_options, true);
    //if body data is not null than process
    if(is_array($sew_options)){
        // loop through each option key and value
        foreach ($sew_options as $sew_option_key => $sew_option_value){
            //explode key and only get first index value (mean 0 index)
            $sew_option_key_prefix = explode("_", $sew_option_key)[0];
            if (in_array($sew_option_key_prefix, $prefixToCheck)) {
                update_option($sew_option_key, $sew_option_value, true);
                $updated[] = $sew_option_key;
            }else{
                $failed[] = $sew_option_key;;
                $sew_error_message = "Option key {$sew_option_key} is not valid so it was not updated";
                sew_log_data('api', $sew_error_message,true);
            }
        }
    }

    return new WP_REST_Response(['updated' => $updated, 'failed' => $failed],200);
}

function sew_regenerate_webhooks($request){

    global $sew_plugin_settings;

    sew_log_data('api', $_SERVER, true);
    sew_log_data('api', $request, true);

    $result = array();

    if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
    }

    sew_delete_all_webhooks();
    sew_check_webhooks();

    return new WP_REST_Response("Webhooks recreated",200);
}

function sew_remove_log_files()
{
    sew_log_data("delete_logs", "Remove log files started `sew_remove_log_files`");
    $sew_log_date_format = "Y-m-d";
    $sew_log_retention_days = (int) esc_attr( get_option('sew_log_retention_days', 7) );
    sew_log_data("delete_logs", "Log retention is set to {$sew_log_retention_days} day(s)");

    if($sew_log_retention_days > 0) {

        $sew_log_retention_timestamp = strtotime("-{$sew_log_retention_days} day");
        $sew_wp_content_dir = WP_CONTENT_DIR;
        $logs_dir = $sew_wp_content_dir . '/slynk-logs';
        sew_log_data("delete_logs", "Getting log files from path {$logs_dir}");
        if (!is_dir($logs_dir)) {
            sew_log_data("delete_logs", "Dir {$logs_dir} not found!!");
            return;
        }

        $raw_logs_array = array_diff(scandir($logs_dir), array('.', '..'));

        foreach ($raw_logs_array as $raw_log_file) {
            sew_log_data("delete_logs", "Processing log file name: {$raw_log_file}");
            $filepath = $logs_dir . '/' . $raw_log_file;
            $current_file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
            $current_file_timestamp = strtotime(date($sew_log_date_format, filemtime($filepath)));
            sew_log_data("delete_logs", "Log file modified timestamp: {$current_file_timestamp}");
            sew_log_data("delete_logs", "Retention timestamp: {$sew_log_retention_timestamp}");

            if($current_file_extension == 'log' && $sew_log_retention_timestamp > $current_file_timestamp){
                unlink($filepath);
                sew_log_data("delete_logs", "Deleted log file from path {$filepath}");
            }else{
                sew_log_data("delete_logs", "Log file not deleted. Extension or timestamp checks not passed for deletion. {$filepath}");
            }
        }
    }
    sew_log_data("delete_logs", "Remove log files ended `sew_remove_log_files`");
    sew_log_data("delete_logs", "----------------------------------------------");

}
/*
 * Returns new feature is active on woocommerce or not!
 *
 * */
function sew_is_custom_order_table(){
    if ( class_exists( Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class ) &&
        function_exists( 'wc_get_container' ) &&
        wc_get_container()->get( Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
       return true;
    }
    return false;
}