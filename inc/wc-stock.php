<?php

function sew_save_product_stock_instant($request){

	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	$stocks = json_decode( $request->get_body(), true );

	sew_log_data('stock_instant', '$stocks:');
	sew_log_data('stock_instant', print_r($stocks, true));

	if(!count($stocks) > 0){
		$response['status'] = 91;
		$response['message'] = 'No stock data received';
		$response['no_products_processed'] = 0;
		$response['result'] = array();
		return new WP_REST_Response( $response, 400 );
	}

	$products_stock_updated = array();

	foreach($stocks as $stock){
		$products_stock_updated[ $stock['id'] ] = sew_update_product_stock( $stock['id'], $stock['stock_quantity']);
	}

	sew_log_data('stock_instant', '$products_stock_updated:');
	sew_log_data('stock_instant', $products_stock_updated, true);

	$response['status'] = 1;
	$response['message'] = 'Stock update processed';
	$response['no_products_processed'] = count($products_stock_updated);
	$response['result'] = $products_stock_updated;
	return new WP_REST_Response( $response, 200 );

}

function sew_save_product_stock($request){

	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	$stock_raw = $request->get_body();

	sew_log_data('stock-all', $stock_raw);

	$result = sew_product_stock_update_json_file($stock_raw);

    sew_log_data('stock-all', 'sew_product_stock_update_json_file $result: '.$result);

	if($result > 0){
		return new WP_REST_Response('stock received and saved',200);
	}else{
		return new WP_REST_Response('stock not saved',500);
	}

}

function sew_product_stock_update_json_file($content){

	$uploads_dir = WP_CONTENT_DIR.'/uploads/sew_uploads';

	if (!is_dir($uploads_dir)) {
		mkdir($uploads_dir, 0755, true);
	}

	$result = file_put_contents($uploads_dir.'/sew_stock.json', $content);

	if($result !== false){
		update_option('sew_stock_received_at', date("Y-m-d H:i:s"));
		update_option('sew_last_stock_element_processed', -1);
		update_option('sew_stock_all_processed', 0);
	}

	return $result;
}

function sew_process_product_stock(){

	global $sew_plugin_settings;

	if(!sew_api_authentication()){
		return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

    //flush the object cache to make sure we are getting the correct values from the options table

    if(esc_attr(get_option('sew_cache_clear_enabled')) == true) {
        $cache_flushed = wp_cache_flush();
        sew_log_data('stock-all', 'object cache flushed: ' . $cache_flushed);
    }

	//do not proceed if processing is already complete
	if(esc_attr(get_option('sew_stock_all_processed'))){
		$response['status'] = 1;
		$response['message'] = 'Processing already completed';
		$response['no_products_processed'] = 0;
		$response['result'] = array();
		return new WP_REST_Response( $response, 200 );
	}

	$uploads_dir = WP_CONTENT_DIR.'/uploads/sew_uploads';

	$stock_raw = file_get_contents($uploads_dir.'/sew_stock.json');

	sew_log_data('stock-all', 'data retrieved from json file');
	sew_log_data('stock-all', $stock_raw, true);

	if(!empty($stock_raw)) {

		$stocks_decoded = json_decode( $stock_raw, true );

		//check that the stock file is not empty
		if ( count( $stocks_decoded ) == 0 ) {
			$response['status'] = 91;
			$response['message'] = 'The stock file is empty';
			$response['no_products_processed'] = 0;
			$response['result'] = array();
			return new WP_REST_Response( $response, 200 );
		}

		$stocks = $stocks_decoded[0];

		//check that the stock array is not empty
		if ( count( $stocks ) == 0 ) {
			$response['status'] = 91;
			$response['message'] = 'The stock file is empty';
			$response['no_products_processed'] = 0;
			$response['result'] = array();
			return new WP_REST_Response( $response, 200 );
		}

		//get the index of the last element that was processed
		$sew_last_stock_element_processed =  esc_attr(get_option('sew_last_stock_element_processed'));
		$sew_stock_batch_size =  $sew_plugin_settings['sew_stock_batch_size'];

		//set default batch size if one is not set in the settings
		if(!isset($sew_last_stock_element_processed)){
			$sew_last_stock_element_processed = -1;
		}

		//set default batch size if one is not set in the settings
		if(!$sew_stock_batch_size > 0){
			$sew_stock_batch_size = 250;
		}

		sew_log_data('stock-all', '$sew_last_stock_element_processed: '.$sew_last_stock_element_processed);
		sew_log_data('stock-all', '$stocks:');
		sew_log_data('stock-all', $stocks, true);

		//check if there are any more elements to process
		if(empty($stocks[$sew_last_stock_element_processed + 1])){
			update_option('sew_stock_all_processed', 1);

			$response['status'] = 1;
			$response['message'] = 'Processing already completed';
			$response['no_products_processed'] = 0;
			$response['result'] = array();
			return new WP_REST_Response( $response, 200 );
		}

		$products_stock_updated = array();

		$upper_index = $sew_stock_batch_size + $sew_last_stock_element_processed + 1; //+1 because $sew_last_stock_element_processed is set to -1 when new stock file is received

		if($upper_index > count($stocks)){
			$upper_index = count($stocks) - 1;
		}

		$last_stock_index = 0;

		for ($stock_index = $sew_last_stock_element_processed + 1; $stock_index <= $upper_index; $stock_index++) {

			sew_log_data('stock-all', 'updating stock for $stock_index: '.$stock_index);
			sew_log_data('stock-all', $stocks[$stock_index], true);

			$products_stock_updated[ $stocks[$stock_index]['id'] ] = sew_update_product_stock( $stocks[$stock_index]['id'], $stocks[$stock_index]['stock_quantity']);

			$last_stock_index = $stock_index;

			//every 50 products, save the last index
			if ($last_stock_index % 50 == 0){
				update_option('sew_last_stock_element_processed', $last_stock_index);
			}

		}

		sew_log_data('stock-all', '$products_stock_updated');
		sew_log_data('stock-all', $products_stock_updated, true);

		update_option('sew_last_stock_element_processed', $last_stock_index);

		//check if we are on the last element
		//if so, set as processed
		if(empty($stocks[$last_stock_index + 1])){
			update_option('sew_stock_all_processed', 1);

			$response['status'] = 1;
			$response['message'] = 'Processing completed';
			$response['no_products_processed'] = count($products_stock_updated);
			$response['result'] = $products_stock_updated;
			return new WP_REST_Response( $response, 200 );
		}

		$response['status'] = 2;
		$response['message'] = 'In progress. Processed upto index '.$last_stock_index;
		$response['no_products_processed'] = count($products_stock_updated);
		$response['result'] = $products_stock_updated;
		return new WP_REST_Response( $response, 200 );

	}else{

		$response['status'] = 91;
		$response['message'] = 'The stock file is empty';
		$response['no_products_processed'] = 0;
		$response['result'] = array();
		return new WP_REST_Response( $response, 200 );

	}

}
function sew_get_full_stock_sync_data(){

	global $sew_plugin_settings;

	if(!sew_api_authentication()){
		return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	$response['settings']['sew_stock_received_at'] = esc_attr(get_option('sew_stock_received_at'));
	$response['settings']['sew_stock_all_processed'] = esc_attr(get_option('sew_stock_all_processed'));
	$response['settings']['sew_last_stock_element_processed'] = esc_attr(get_option('sew_last_stock_element_processed'));
	$response['settings']['sew_stock_batch_size'] = $sew_plugin_settings['sew_stock_batch_size'];

	$uploads_dir = WP_CONTENT_DIR.'/uploads/sew_uploads';
	$stock_raw = file_get_contents($uploads_dir.'/sew_stock.json');

	sew_log_data('stock-all', 'data retrieved from json file');
	sew_log_data('stock-all', $stock_raw, true);

	if(!empty($stock_raw)) {

		$stocks_decoded = json_decode( $stock_raw, true );

		$response['message'] = 'sew_stock.json file found';;

		if(!empty($stocks_decoded[0])){
			$response['count'] = count( $stocks_decoded[0]);
		}else{
			$response['count'] = 0;
		}

		$response['data'] = $stocks_decoded;
		return new WP_REST_Response( $response, 200 );

	}else{
		$response['message'] = 'sew_stock.json file not found';
		$response['count'] = 0;
		$response['data'] = array();
		return new WP_REST_Response( $response, 200 );
	}

}

function sew_update_product_stock($product_id, $stock_qty){

	$sln_ignore_stock_update = get_post_meta($product_id, 'sln_ignore_stock_update', true);

	if(!$sln_ignore_stock_update) {

        $parent_id = wp_get_post_parent_id($product_id);
		$current_dt = current_time( 'mysql' );

		update_post_meta($product_id, 'sew_stock_updated', wc_clean($current_dt));

        if($parent_id){
            update_post_meta($parent_id, 'sew_stock_updated', wc_clean($current_dt));
        }

		return wc_update_product_stock( $product_id, $stock_qty, 'set' );
	}else{
		return false;
	}
}

//if woocommerce is master, then we need to process any stock changes
add_action('woocommerce_product_set_stock','sew_action_woocommerce_change_stock', 10, 1 );
add_action('woocommerce_variation_set_stock','sew_action_woocommerce_change_stock', 10, 1 );
function sew_action_woocommerce_change_stock( $array ){

    global $sew_plugin_settings;

    //check if woocommerce is master
    if($sew_plugin_settings['wc_master']) {
        $stock = $array->get_stock_quantity();
        $productid = $array->get_id();
        $log = array('stock' => $stock, 'product' => $productid);
        sew_log_data('stock_set', 'Product stock set:');
        sew_log_data('stock_set', $log, true);
        sew_log_data('stock_set', 'calling the sew_product_stock action to trigger the webhook');
        do_action('sew_product_stock', $productid, $stock, $array);
    }

}