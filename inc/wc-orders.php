<?php
/**
 * Handle WooCommerce Orders
 */

function sew_check_order_date_valid($order_date_time){

	global $sew_plugin_settings;

	//check that the sync_orders_after setting is set
    if(empty($sew_plugin_settings['sync_orders_after'])){
        return false;
    }

	$order_date_dt = strtotime($order_date_time);
	$comparison_date = strtotime($sew_plugin_settings['sync_orders_after']);

	if($order_date_dt >= $comparison_date){
		return true;
	}else{
		return false;
	}

}

function sew_check_user_role($order_data){
    global $sew_plugin_settings;
    $order_id = $order_data["id"];
    $sew_sync_user_roles = trim($sew_plugin_settings["sew_sync_user_role"]);

    //if there is nothing set, then pass all roles are valid
    if(empty($sew_sync_user_roles)){
        return true;
    }

    $sew_sync_patterns = explode(",", $sew_sync_user_roles);

    //Get the user object
    $user = get_userdata($order_data["customer_id"]);

    //Get all the user roles as an array
    $user_roles = $user->roles;

    //loop through pattern stored in eposnow plugin admin side
    foreach ($sew_sync_patterns as $sew_sync_pattern){
        //convert all character to lower
        $sew_sync_pattern = strtolower($sew_sync_pattern);
        //loop through user roles
        foreach ($user_roles as $user_role){

            $user_role = strtolower(trim($user_role));

            //replaced * with \w* . \w means a word character and * means after
            $sew_sync_match_pattern = str_replace("*","\w*", $sew_sync_pattern);

            //match user roles
            if (preg_match("/\b($sew_sync_match_pattern)\b/", $user_role)) {

                sew_log_data('wc_orders','Check User Role Filtering Order before sending to webhook');
                sew_log_data('wc_orders',"User Role: ".$user_role." - Sync Pattern: ".$sew_sync_match_pattern);
                return true;
            }
        }
    }
    return false;

}

//trigger webhook if all conditions for the order are met
function sew_filter_orders_before_sending( $order_id, $old_status,  $new_status ) {

	global $sew_plugin_settings;

	//check if order sync is enabled
	if($sew_plugin_settings['disable_order_sync'] == 1){
		return;
	}

	//get the order
	$order = wc_get_order( $order_id );
	$order_data = $order->get_data();

	sew_log_data('wc_orders','Filtering Order before sending to webhook');
	sew_log_data('wc_orders','$order_id: '.$order_id.' $old_status: '.$old_status.' $new_status: '.$new_status);
	sew_log_data('wc_orders',$order_data, true);

	//check if order date is after the date set for order syncing
	if(!sew_check_order_date_valid($order_data['date_created'])){
		if($old_status == 'manual' && $new_status == 'manual'){
			$order->add_order_note('This order cannot be synced as the order date is before the Sync Orders On/After Date defined in the settings.');
		}
		return;
	}

	//Validate user roles, which stored in eposnow plugin backend
	if(!sew_check_user_role($order_data)){
        $notes = "This order was not synced as it does not match the customer role filter in the plugin settings : " . $sew_plugin_settings["sew_sync_user_role"];
        update_post_meta($order_id, 'sew_sync_notes', sanitize_text_field($notes));
        return;
    }

	//check if already synced
	$sync_status = get_post_meta( $order_id, 'sew_slynk_sync_status', true );

	if($sync_status == 1){
		//if sent manually, then add an order note
		if($old_status == 'manual' && $new_status == 'manual'){
			$order->add_order_note('This order has already been synced with Slynk and has not been resent');
		}
		return;
	}

	//check if the order status is valid for syncing
	if(!in_array($order_data['status'], $sew_plugin_settings['valid_order_statuses'])){
		//if sent manually, then add an order note
		if($old_status == 'manual' && $new_status == 'manual'){
			$order->add_order_note('Orders can only be synced if in the status '.implode(' or ',$sew_plugin_settings['valid_order_statuses']));
		}
		return;
	}

	if($old_status == 'manual' && $new_status == 'manual'){
		$order->add_order_note('Sending order to Slynk manually');
	}else{
		$order->add_order_note('Sending order to Slynk');
	}

	sew_log_data('wc_orders','calling the sew_order_filter action to trigger the webhook');
	do_action( 'sew_order_filter', $order_id, $order_data, $order );
}


/**
 * Used to save Restock refunded items flag in db for refund
 * @param $refund_id
 * @param $args
 */

function sew_woocommerce_refund_created($refund_id, $args){
    update_post_meta($refund_id, 'sew_stock_adjustment', $args['restock_items']);
}


/*
 * This function called when woocommerce order refunded action fired.
 */
function sew_woocommerce_order_refunded($order_id, $refund_id, $old_status ="", $new_status=""){

    global $sew_plugin_settings;

    //check if order sync is enabled
    if($sew_plugin_settings['disable_order_sync'] == 1){
        return;
    }

    //get order
    $order = wc_get_order( $order_id );

    if($sew_plugin_settings['disable_refund_order_sync'] == 1){
        if($old_status == 'manual' && $new_status == 'manual') {
            $order->add_order_note('Refund order sync is disabled in the plugin settings');
        }
        return;
    }
    //check refund date
    $refund_ids = sew_check_refund_order_date_valid($order);
    if(count($refund_ids) == 0) {
        if($old_status == 'manual' && $new_status == 'manual') {
            $order->add_order_note('This refund cannot be synced as the refund order date is before the Sync Refund Orders On/After Date is not defined in the settings.');
        }
        return;
    }

    $order_data = $order->get_data();

    //get order created date
    $created_date = $order->get_date_created()->date("Y-m-d H:i:s");
    //check if order date is after the date set for order syncing
    if(!sew_check_order_date_valid($created_date)){
        if($old_status == 'manual' && $new_status == 'manual'){
            $order->add_order_note('This refund order cannot be synced as the order date is before the Sync Orders On/After Date defined in the settings.');
        }
        return;
    }

    //here we are checking any refund from order is pending
    $refunds = check_any_refund_needs_to_process($order);
    if(count($refunds) == 0){
        if($old_status == 'manual' && $new_status == 'manual') {
            $order->add_order_note('All refunds for this order have already been sent to Slynk');
        }
        return;
    }

    sew_refund_log('Filtering Refund Order before sending to webhook');
    sew_refund_log('$order_id: '.$order_id.' $old_status: '.$old_status.' $new_status: '.$new_status);
    sew_refund_log($order_data, true);

    //Validate user roles, which stored in eposnow plugin backend
    if(!sew_check_user_role($order_data)){
        $notes = "This refund order was not synced as it does not match the customer role filter in the plugin settings : " . $sew_plugin_settings["sew_sync_user_role"];
        update_post_meta($refund_id, 'sew_sync_notes', sanitize_text_field($notes));
        return;
    }

    //check if already synced
    $sync_status = get_post_meta( $refund_id, 'sew_slynk_sync_status', true );

    if($sync_status == 1){
        //if sent manually, then add an order note
        if($old_status == 'manual' && $new_status == 'manual'){
            $order->add_order_note('This refund order id '. $refund_id .' has already been synced with Slynk and has not been resent');
        }
        return;
    }


    //check if the order status is valid for syncing
    if(!in_array('refunded', $sew_plugin_settings['valid_order_statuses'])){
        //if sent manually, then add an order note
        if($old_status == 'manual' && $new_status == 'manual'){
            $order->add_order_note('Unable to send refunds to Slynk. Please add the ‘Refunded’ order status to the Slynk EposNow plugin settings');
        }
        return;
    }

    $refund_str = implode(', ', $refunds);
    if($old_status == 'manual' && $new_status == 'manual'){
        $order->add_order_note('Sending refund(s) '.$refund_str.' to Slynk manually');
    }else{
        $order->add_order_note('Sending refund(s) '.$refund_str.' to Slynk');
    }

    sew_refund_log('Refund ids : '.$refund_str);
    sew_refund_log('calling the sew_refund_order_filter action to trigger the webhook');

    do_action( 'sew_refund_order_filter', $order_id, $refund_id);
}

/**
 * @param $order
 * @return array
 * checking any refunds needs to process from order
 */
function check_any_refund_needs_to_process($order){

    $order_refunds = $order->get_refunds();
    $refund_ids = array();
    //loop through each refund
    foreach( $order_refunds as $refund ){
        $refund_id = $refund->get_id();
        $status = (int)get_post_meta($refund_id, 'sew_slynk_sync_status', true);
        if ((int)$status !== 1) {
            sew_refund_log('$refund_id');
            sew_refund_log($refund_id);
            $refund_ids[] = $refund_id;
        }
    }
    return $refund_ids;
}

function sew_check_refund_order_date_valid($order){
    global $sew_plugin_settings;
    $refund_ids = array();
    if(empty($sew_plugin_settings['sync_refund_orders_after'])){
        return $refund_ids;
    }

    $order_refunds = $order->get_refunds();

    //loop through each refund
    foreach( $order_refunds as $refund ){
        $refund_id = $refund->get_id();
        $refunds = wc_get_order($refund_id);
        $refund_created_date = $refunds->get_date_created()->date("Y-m-d");
        if(!sew_compare_dates($refund_created_date)){
            continue;
        }
        $refund_ids[] = $refund_id;
    }
    return $refund_ids;
}


function sew_compare_dates($refund_created_date){
    global $sew_plugin_settings;
    $refund_created_date = strtotime($refund_created_date);
    $refund_order_settings_date = strtotime($sew_plugin_settings['sync_refund_orders_after']);
    if($refund_created_date < $refund_order_settings_date){
        return false;
    }
    return true;
}

//add order actions to the order edit screen in WP-Admin
function sew_wc_order_action( $actions ) {

	if ( is_array( $actions ) ) {

		$actions['sew_send_to_slynk'] = __( 'Send order to Slynk' );
		$actions['sew_send_refund'] = __('Send refund to Slynk');
	}

	return $actions;

}


//Process the order action from the WP-Admin order edit screen
function sew_send_order_to_slynk_manually( $order ) {
	$order_data = $order->get_data();
	sew_log_data('wc_orders','Manually sending order to Slynk for Order ID: '.$order_data['id'],false);
	sew_filter_orders_before_sending( $order_data['id'], 'manual',  'manual' );

}
//Process the order action from the WP-Admin order edit screen
function sew_send_order_refund_to_slynk_manually( $order ) {
    $order_data = $order->get_data();
    sew_refund_log('Manually sending refund to Slynk for Order ID: '.$order_data['id'], false);
    sew_woocommerce_order_refunded( $order_data['id'], 0,'manual',  'manual' );
}

//update order sync status
function sew_update_wc_order_sync_status($order_id, $sync_status = 0, $date_time = null, $epn_transaction_id = null, $notes = null){

    $sew_is_custom_order_table = sew_is_custom_order_table();
	if(empty($order_id)){
		return 'Order ID is empty. Order not updated.';
	}

	if(empty($date_time)){
		$date_time = current_time( 'Y-m-d H:i:s' );
	}


    if($sew_is_custom_order_table == true){
        $order_object = wc_get_order($order_id);
        $order_object->update_meta_data('sew_slynk_sync_status', $sync_status);
        $order_object->update_meta_data('sew_slynk_sync_date_time', $date_time);
    }else {
        update_post_meta($order_id, 'sew_slynk_sync_status', $sync_status);
        update_post_meta($order_id, 'sew_slynk_sync_date_time', $date_time);
    }

	if(!empty($epn_transaction_id)) {
        if($sew_is_custom_order_table == true){
            $order_object->update_meta_data('sew_epn_transaction_id', sanitize_text_field($epn_transaction_id));
        }else {
            update_post_meta($order_id, 'sew_epn_transaction_id', sanitize_text_field($epn_transaction_id));
        }
	}

	if(!empty($notes)) {
        if($sew_is_custom_order_table == true) {
            $order_object->update_meta_data('sew_sync_notes', sanitize_text_field($notes));
        }else{
            update_post_meta($order_id, 'sew_sync_notes', sanitize_text_field($notes));
        }
	}

    if($sew_is_custom_order_table == true){
        $order_object->save();
    }

	return 'Order ID '.$order_id.' updated.';
}

//Display order sync status in order detail page.

// Adding Meta container admin shop_order pages
function sew_wc_order_add_meta_box( $wc_screen_id, $wc_order )
{
    if ( $v = sew_is_custom_order_table()) {
        $sew_screen_id = wc_get_page_screen_id( 'shop-order' );
    } else {
        $sew_screen_id = 'shop_order';
    }

    if ( $wc_screen_id != $sew_screen_id ) {
        return;
    }

	add_meta_box( 'sew_wc_order_sync_meta-box', __('Slynk','slynk-woocommerce'), 'sew_custom_checkout_field_display_admin_order_meta', $sew_screen_id, 'side', 'core' );
}


function sew_get_sync_status_for_order($order, $sew_is_custom_order_table){

    //New HPOS
    if($sew_is_custom_order_table == true){
        $sync_status['sew_slynk_sync_status']       = $order->get_meta('sew_slynk_sync_status');
        $sync_status['sew_slynk_sync_date_time']    = $order->get_meta('sew_slynk_sync_date_time');
        $sync_status['sew_epn_transaction_id']      = $order->get_meta('sew_epn_transaction_id');
        $sync_status['sew_sync_notes']              = $order->get_meta('sew_sync_notes');
    }else{
        $order_id = $order->get_id();
        $sync_status['sew_slynk_sync_status']       = get_post_meta($order_id, 'sew_slynk_sync_status', true );
        $sync_status['sew_slynk_sync_date_time']    = get_post_meta($order_id, 'sew_slynk_sync_date_time', true );
        $sync_status['sew_epn_transaction_id']      = get_post_meta( $order_id, 'sew_epn_transaction_id', true );
        $sync_status['sew_sync_notes']              = get_post_meta( $order_id, 'sew_sync_notes', true );
    }

	if($sync_status['sew_slynk_sync_status'] == 1){
		$sync_status['colour'] = '#007500'; //green
		$sync_status['sync_text'] = 'Yes';
	}else{
		$sync_status['colour'] = '#FF0000'; //red
		$sync_status['sync_text'] = 'No';
	}

    return $sync_status;
}

function sew_custom_checkout_field_display_admin_order_meta($post_or_order_object){

    $order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

    $sew_is_custom_order_table = sew_is_custom_order_table();

	$sync_status = sew_get_sync_status_for_order($order, $sew_is_custom_order_table);

	$html = '';

	$html .= '<p><b>'.__('Synced with Slynk').'</b>: <span style="color:'.$sync_status['colour'].'">' . $sync_status['sync_text']. '</span></p>';

	$html .= '<p><b>'.__('Last Updated').'</b>: ' . $sync_status['sew_slynk_sync_date_time']. '</p>';

	$html .= '<p><b>'.__('EposNow  ID').'</b>: ' . $sync_status['sew_epn_transaction_id']. '</p>';

	$html .= '<p><b>'.__('Notes').'</b>:<br><small>' . $sync_status['sew_sync_notes']. '</small></p>';

	echo $html;
}
//add_action( 'woocommerce_admin_order_data_after_billing_address', 'sew_custom_checkout_field_display_admin_order_meta', 10, 1 );

//show the order sync status in the orders grid
function sew_custom_wc_orders_cols( $columns ) {

	$new_columns = array();
	foreach ($columns as $column_name => $column_info) {
		$new_columns[$column_name] = $column_info;
		if ('order_total' === $column_name) {
			$new_columns['slynk_order_synced'] = __('Sent to Slynk', 'woo-epos-now-integration');
			$new_columns['slynk_epn_transaction_id'] = __('EposNow ID', 'woo-epos-now-integration');
		}
	}
	return $new_columns;

}


function sew_custom_wc_orders_cols_func($column, $post_or_order_object) {

    $order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
    if ( ! is_object( $order ) && is_numeric( $order ) ) {
        $order = wc_get_order( absint( $order ) );
    }

    $sew_is_custom_order_table = sew_is_custom_order_table();

	$sync_status = sew_get_sync_status_for_order($order, $sew_is_custom_order_table);

/*	if ( $column == 'slynk_order_synced' ) {
		echo ucwords('<span style="color:'.$sync_status['colour'].'">' . $sync_status['sync_text']. '</span>');
	}*/

    if ( $column == 'slynk_order_synced' ) {
        if ($sync_status['sync_text'] == 'Yes') {
            echo '<span class="dashicons dashicons-yes" style="color:'.$sync_status['colour'].'"></span>';
        } else {
            echo '<span class="dashicons dashicons-no" style="color:'.$sync_status['colour'].'"></span>';
        }
    }

	if ( $column == 'slynk_epn_transaction_id' ) {
		echo $sync_status['sew_epn_transaction_id'];
	}

}

function sew_process_orders($request, $internal_call=false){

	global $sew_plugin_settings;
    $query_params = null;

	sew_log_data('wc_orders', 'starting sew_process_orders', false);

	if($internal_call) {
        sew_log_data('wc_orders', 'sew_process_orders called from internal function', false);
    }

	if(!$internal_call) {

        $query_params = $request->get_query_params();

        if (!sew_api_authentication()) {
            sew_log_data('wc_orders', 'sew_process_orders authentication failed', false);
            return new WP_REST_Response('Slynk WC API authorisation failed',401);
        }

        sew_log_data('wc_orders', 'starting sew_process_orders authentication passed', false);

    }

    if($sew_plugin_settings['disable_order_sync'] == 1){
        return new WP_REST_Response('Order sync is disabled in the plugin settings',200);
    }

	$orders_by_status = array();

    if(!empty($query_params['orders_from_date'])){
        $orders_from_date = htmlspecialchars( $query_params['orders_from_date']);
    }else{
        // Get the current date and time
        $currentDate = date('Y-m-d');
        // Calculate the date x days before today
        $orders_from_date = date('Y-m-d', strtotime('-'.$sew_plugin_settings['sew_unsynced_orders_refunds_days_in_past'].' days', strtotime($currentDate)));
    }

    $allowed_meta_keys[] = 'sew_slynk_sync_status';
    $allowed_meta_keys[] = 'sew_epn_transaction_id';
    $allowed_meta_keys[] = 'sew_slynk_sync_date_time';
    $allowed_meta_keys[] = 'sew_sync_notes';

    if(!empty($query_params['meta_key'])){
        $meta_key = htmlspecialchars($query_params['meta_key']);
        //check it is a valid value
        if(!in_array($meta_key, $allowed_meta_keys)) {
            $meta_key = 'sew_slynk_sync_status';
        }
    }else{
        $meta_key = 'sew_slynk_sync_status';
    }

    // Get orders
    $args   = array(
        'type' => 'shop_order',
        'status' => $sew_plugin_settings['valid_order_statuses'],
        'date_created' => '>=' . $orders_from_date,
        'limit' => $sew_plugin_settings['max_orders_to_process_batch'],
        'orderby' => 'date',
        'order' => 'ASC',
        'return' => 'ids',
        'meta_key' => $meta_key
    );

    $meta_compares[] = 'NOT EXISTS';
    $meta_compares[] = '=';

    foreach($meta_compares as $meta_compare) {

        unset($args['meta_compare']);

        if($meta_compare == 'NOT EXISTS'){
            $args['meta_compare'] = $meta_compare;
        }

        if($meta_compare != 'NOT EXISTS'){
            $args['meta_value'] = 0;
        }

        sew_log_data('wc_orders', 'Fetching orders with meta compare = '.$meta_compare);

        unset($order_ids);
        $order_ids = wc_get_orders($args);

        sew_log_data('wc_orders', count($order_ids) . ' orders fetched with meta compare = '.$meta_compare);

        foreach ($order_ids as $order_id) {

            //get the order
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();

            //check that the order matches the filter for user role
            if (sew_check_user_role($order_data)) {

                $orders_by_status[$order_data['status']][] = $order_id;
                sew_log_data('wc_orders', 'Order ID ' . $order_id . ' fetched to send to filtering', false);
                sew_filter_orders_before_sending($order_id, 'manual', 'manual');
            }

        }

    }

	return new WP_REST_Response($orders_by_status,200);
}

function sew_get_orders_by_product($request){

	global $sew_plugin_settings;
	$query_params = $request->get_query_params();

	$orders = array();

    if (!sew_api_authentication()) {
        sew_log_data('wc_orders', 'sew_process_orders authentication failed', false);
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
    }

	if(empty($query_params['product_id'])){
		return new WP_REST_Response('Product ID must be provided',400);
	}

	if(empty($query_params['product_type'])){
		$query_params['product_type'] = 'simple';
	}

	if(empty($query_params['get_order_data'])){
		$query_params['get_order_data'] = 0;
	}

	$product_id = filter_var( $query_params['product_id'], FILTER_SANITIZE_NUMBER_INT );

	global $wpdb;

	$order_item_meta_key = '_product_id';

	if($query_params['product_type'] != 'simple'){
		$order_item_meta_key = '_variation_id';
	}

    $sew_is_custom_order_table = sew_is_custom_order_table();
    if($sew_is_custom_order_table == true) {

        $results = $wpdb->get_col("
        SELECT DISTINCT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->prefix}wc_orders AS orders ON order_items.order_id = orders.id
        WHERE order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '" . $order_item_meta_key . "'
        AND order_item_meta.meta_value = '" . $product_id . "'
        ORDER BY order_items.order_id DESC");

    }else {

        $results = $wpdb->get_col("
        SELECT DISTINCT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '" . $order_item_meta_key . "'
        AND order_item_meta.meta_value = '" . $product_id . "'
        ORDER BY order_items.order_id DESC");

    }

	$order = null;
	$order_result = array();

	foreach($results as $order_id){
		unset($order_result);
		$order_result['id'] = $order_id;

		if($query_params['get_order_data']){
			$order = wc_get_order( $order_id );
			if($order) {
				$order_result['data'] = $order->get_data();

                //get the line items
                foreach($order_result['data']['line_items'] as $line_item_id=>$line_item){
                    $order_result['data']['line_items'][$line_item_id] = $line_item->get_data();
                }

                //get the tax lines
                foreach($order_result['data']['tax_lines'] as $tax_line_item_id=>$tax_line_item){
                    $order_result['data']['tax_lines'][$tax_line_item_id] = $tax_line_item->get_data();
                }

			}else{
				$order_result['data'] = 'unable to fetch order data';
			}
		}

		$orders[] = $order_result;
	}

	return new WP_REST_Response($orders,200);

}


/**
 * @param $request
 * @param false $internal_call
 * @return WP_REST_Response
 * process pending refund orders
 */
function sew_process_refund_orders($request, $internal_call=false){

    global $sew_plugin_settings;

    sew_refund_log( 'starting sew_process_refund_orders', false);

    if($internal_call) {
        sew_refund_log( 'sew_process_refund_orders called from internal function', false);
    }

    if(!$internal_call) {

        if (!sew_api_authentication()) {
            sew_refund_log('sew_process_refund_orders authentication failed', false);
            return new WP_REST_Response('Slynk WC API authorisation failed',401);
        }

        sew_refund_log( 'starting sew_process_refund_orders authentication passed', false);

    }

    if($sew_plugin_settings['disable_order_sync'] == 1){
        return new WP_REST_Response('Order sync is disabled in the plugin settings',200);
    }

    if($sew_plugin_settings['disable_refund_order_sync'] == 1){
        return new WP_REST_Response('Refund order sync is disabled in the plugin settings',200);
    }

    if(empty($sew_plugin_settings['sync_refund_orders_after'])){
        return new WP_REST_Response('This order cannot be synced as the refund order date is before the Sync Refund Order On/After Date is not defined in the settings',200);
    }

    if(strtotime($sew_plugin_settings['sync_refund_orders_after']) < strtotime($sew_plugin_settings['sync_orders_after'])){
        return new WP_REST_Response('Refund order date should be equal or greater than order sync date in the plugin settings',200);
    }
    $orders_by_status = array();

    foreach($sew_plugin_settings['valid_order_statuses'] as $order_status) {

        // Get orders
        $args   = array(
            'type' => 'shop_order_refund',
            'status' => $order_status,
            'date_created' => '>=' . $sew_plugin_settings['sync_refund_orders_after'],
            'limit' => $sew_plugin_settings['max_orders_to_process_batch'],
            'orderby' => 'date',
            'order' => 'ASC',
            'return' => 'ids',
            //'sew_slynk_sync_status' => 0    //note that this removes all other meta query values, only happens if we specify this arg
            'meta_key'      => 'sew_slynk_sync_status', //New
            'meta_value'    => 0, //New
        );

        $orders_by_status[$order_status] = wc_get_orders( $args );

    }

    $refund_order_status = [];
    //loop through order by status
    foreach($orders_by_status as $order_status => $orders_arr){

        foreach($orders_arr as $key => $refund_order_id){

            //get parent id from refund order
            $parent_order_id = wc_get_order( $refund_order_id )->get_parent_id();
            $orders_by_status[$order_status][$parent_order_id][] = $refund_order_id;
            unset($orders_by_status[$order_status][$key]);
        }
    }

    //loop through order by status
    foreach($orders_by_status as $order_status => $orders_arr){
        $refund_order_status[$order_status] = array();
        foreach($orders_arr as $parent_order_id => $refund_arr) {
            //get parent order data
            $parent_order = wc_get_order($parent_order_id);
            //get order created date
            $created_date = $parent_order->get_date_created()->date("Y-m-d H:i:s");
            //check if order date is after the date set for order syncing
            if(!sew_check_order_date_valid($created_date)){
                continue;
            }

            //parent order date pass to check if any refund order is before refund order sync on/after
            $refund_order_status[$order_status][] = $refund_arr;
            //get parent order data to check user role
            $parent_order_data = $parent_order->get_data();
            sew_refund_log('$parent_order_data');
            sew_refund_log($parent_order_data, true);
            //check that the order matches the filter for user role
            if (sew_check_user_role($parent_order_data)) {
                sew_refund_log('Parent Order ID ' . $parent_order_id . ' fetched to send to filtering', false);
                sew_woocommerce_order_refunded($parent_order_id, 0, 'manual', 'manual');
            }
        }
    }


    return new WP_REST_Response($refund_order_status,200);
}

/**
 * Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function sew_wc_orders_handle_custom_query_var( $query, $query_vars ) {

	if ( isset( $query_vars['sew_slynk_sync_status'] ) ) {

		if($query_vars['sew_slynk_sync_status'] == 1){

			$query['meta_query'][] = array(
				'key' => 'sew_slynk_sync_status',
				'value' => 1,
				'compare' => '='
			);

		}

		if($query_vars['sew_slynk_sync_status'] == 0){
			//note this removes all other meta queries
			//this should only fire when our custom meta parameter is being searched for
			$query['meta_query'] = array(
										'relation' => 'OR',
										array(
											'key' => 'sew_slynk_sync_status',
											'value' => 0,
											'compare' => '='
										),
										array(
											'key' => 'sew_slynk_sync_status',
											'value' => NULL,
											'compare' => '='
										),
										array(
											'key' => 'sew_slynk_sync_status',
											'compare' => 'NOT EXISTS'
										)
									);
		}

	}

	return $query;
}


function sew_update_orders($request){

	sew_log_data('api', $_SERVER, true);
    sew_log_data('api', $request, true);

	$result = array();

	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

    $orders = json_decode($request->get_body(), true);
	sew_log_data('api', $orders, true);

	if(empty($orders)){
		return new WP_REST_Response('empty body',400);
	}

	//update the data for each order
	foreach($orders as $order){

        if(!isset($order['wc_order_id'])){
            continue;
        }

		if(empty($order['epn_transaction_id'])){
			$order['epn_transaction_id'] = '';
		}
		sew_log_data('api','$order');
		sew_log_data('api', $order, true);
        if(isset($order['is_refund'])){
            if($order['is_refund'] == true){
                $order_data = wc_get_order($order['wc_parent_order_id']);
                $html = "Refund Order ID: {$order['wc_order_id']} \r\n
                Synced with Slynk: Yes \r\n
                Last Updated: {$order['sync_date_time']} \r\n
                EposNow ID: {$order['epn_transaction_id']} \r\n
                Notes: {$order['sync_notes']}";
                $order_data->add_order_note($html);
            }
        }


		$result[$order['wc_order_id']] = sew_update_wc_order_sync_status($order['wc_order_id'], 1, $date_time = $order['sync_date_time'], $epn_transaction_id = $order['epn_transaction_id'], $order['sync_notes']);
	}

	return new WP_REST_Response($result,200);
}
function sew_update_order_meta($request){

	sew_log_data('api', $_SERVER, true);
    sew_log_data('api', $request, true);

	$result = array();

	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

    $orders = json_decode($request->get_body(), true);
	sew_log_data('api', $orders, true);

	if(empty($orders)){
		return new WP_REST_Response('empty body',400);
	}
    $sew_is_custom_order_table = sew_is_custom_order_table();

	//update the data for each order
	foreach($orders as $order) {

        if (!empty($order['wc_order_id'])) {

            if($sew_is_custom_order_table == true){
                $order_object = wc_get_order($order['wc_order_id']);
            }

            if (isset($order['epn_transaction_id'])) {
                if(trim($order['epn_transaction_id']) != ""){

                    if($sew_is_custom_order_table == true){
                        $order_object->update_meta_data('epn_transaction_id', sanitize_text_field($order['epn_transaction_id']));
                    }else {
                        update_post_meta($order['wc_order_id'], 'epn_transaction_id', sanitize_text_field($order['epn_transaction_id']));
                    }

                }
            }
            if (isset($order['sew_slynk_sync_status'])) {
                if(trim($order['sew_slynk_sync_status']) != ""){

                    if($sew_is_custom_order_table == true){
                        $order_object->update_meta_data('sew_slynk_sync_status',  $order['sew_slynk_sync_status']);
                    }else {
                        update_post_meta($order['wc_order_id'], 'sew_slynk_sync_status', $order['sew_slynk_sync_status']);
                    }

                }
            }
            if (isset($order['sew_slynk_sync_date_time'])) {
                if(trim($order['sew_slynk_sync_date_time']) != ""){

                    if($sew_is_custom_order_table == true){
                        $order_object->update_meta_data('sew_slynk_sync_date_time',  $order['sew_slynk_sync_date_time']);
                    }else {
                        update_post_meta($order['wc_order_id'], 'sew_slynk_sync_date_time', $order['sew_slynk_sync_date_time']);
                    }
                }
            }
            if (isset($order['sew_sync_notes'])) {
                if(trim($order['sew_sync_notes']) != ""){

                    if($sew_is_custom_order_table == true){
                        $order_object->update_meta_data('sew_sync_notes',  sanitize_text_field($order['sew_sync_notes']));
                    }else {
                        update_post_meta($order['wc_order_id'], 'sew_sync_notes', sanitize_text_field($order['sew_sync_notes']));
                    }
                }
            }

            if($sew_is_custom_order_table == true){
                sew_log_data("api", "Successful to save order meta to new custom order table");
                $order_object->save();
            }
        } else {
            sew_log_data('api', 'no wc_order_id for order', false);
            sew_log_data('api', $order, true);
        }

    }

	return new WP_REST_Response($result,200);
}

//Suppress emails if order is from EposNow and setting is enabled

//admin emails
add_filter( 'woocommerce_email_recipient_new_order', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_cancelled_order', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_failed_order', 'sln_suppress_eposnow_order_emails', 10, 2 );

//customer emails
add_filter( 'woocommerce_email_recipient_customer_processing_order', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_completed_order', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_invoice', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_note', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_on_hold_order', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_refunded_order', 'sln_suppress_eposnow_order_emails', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_cancelled_order', 'sln_suppress_eposnow_order_emails', 10, 2 );

function sln_suppress_eposnow_order_emails( $recipient, $order ) {

    global $sew_plugin_settings;

    //do not interfere with the WooCommerce settings page
    $page = isset( $_GET['page'] ) ? sanitize_text_field($_GET['page']) : '';
    if ( 'wc-settings' === $page ) {
        return $recipient;
    }

    //check the setting
    if($sew_plugin_settings['sew_suppress_order_emails_eposnow_orders'] == 1){

        $order_data = $order->get_data();

        sew_log_data('wc_emails','----------------------- Order ID: '.$order_data['id'].' -----------------------',false);
        sew_log_data('wc_emails','Recipient: '.$recipient,false);
        sew_log_data('wc_emails','Order meta: ',false);
        sew_log_data('wc_emails',$order_data['meta_data'],true);

        if(!empty($order_data['meta_data'])){

            sew_log_data('wc_emails','Order meta items: ',false);

            foreach($order_data['meta_data'] as $order_meta_obj) {

                $order_meta = $order_meta_obj->get_data();

                sew_log_data('wc_emails',$order_meta,true);

                if (!empty($order_meta['key'])) {

                    if ($order_meta['key'] == 'sew_transaction_source' && $order_meta['value'] == 'eposnow') {
                        sew_log_data('wc_emails','Suppressing email',false);
                        $recipient = '';
                        break;
                    }
                }

            }

        }



    }

    return $recipient;
}
function sew_refund_log($str, $arr = false){
    sew_log_data('wc_refunds', $str, $arr);
}