<?php


//Create product hook does not fire if product published immediately from WP-Admin, known WC issue, fires update instead
//https://github.com/woocommerce/woocommerce/issues/23610
function sew_woocommerce_create_update_product_callback($product_id, $product){
    sew_log_data('wc_product_update', "product created/updated {$product_id}");

    global $sew_plugin_settings;
    if (isset($sew_plugin_settings['product_sync_disabled']) && $sew_plugin_settings['product_sync_disabled'] == false) {
        sew_log_data('wc_product_update', "product ID {$product_id}. Passed checks for product sync disabled.");
        if(sew_check_product_valid_to_send($product)) {
            do_action('sew_product_updated', $product_id, $product);
        }
    }
}

function sew_woocommerce_delete_product_callback($product_id){
    sew_log_data('wc_product_delete', "product deleted {$product_id}");
    global $sew_plugin_settings;
    if (isset($sew_plugin_settings['product_sync_disabled']) && $sew_plugin_settings['product_sync_disabled'] == false) {
        sew_log_data('wc_product_delete', "product ID {$product_id}. Passed checks for product sync disabled.");
        do_action('sew_product_delete', $product_id);
    }
}

function sew_get_products($request){

	$query_params = $request->get_query_params();

	$products = array();

	if(!sew_api_authentication()){
		return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	if(empty($query_params['page'])){
		$query_params['page'] = 1;
	}

	if(empty($query_params['per_page'])){
		$query_params['per_page'] = 100;
	}

	if(empty($query_params['modified_after'])){
		$query_params['modified_after'] = "1970-01-01T00:00:00";
	}

	if(empty($query_params['trigger_product_update_webhook'])){
		$query_params['trigger_product_update_webhook'] = 0;
	}

	if(empty($query_params['exclude_variable_products'])){
		$query_params['exclude_variable_products'] = 0;
	}

	if(empty($query_params['include_variations'])){
		$query_params['include_variations'] = 0;
	}

	if(empty($query_params['include_variations'])){
		$query_params['include_variations'] = 0;
	}

	$product_types[] = 'product';

	if($query_params['include_variations']){
		$product_types[] = 'product_variation';
	}

	if(empty($query_params['orderby'])){
		$query_params['orderby'] = 'ID';
	}

	if(empty($query_params['order'])){
		$query_params['order'] = 'ASC';
	}

	foreach($product_types as $product_type) {

		$args = array(
			'post_type'      => $product_type,
			'posts_per_page' => $query_params['per_page'],
			'paged'          => $query_params['page'],
			'orderby'        => $query_params['orderby'],
			'order'          => $query_params['order'],
			'date_query'     => array(
				array(
					'column' => 'post_modified_gmt',
					'after'  => $query_params['modified_after'],
				),
			),
		);

		$loop = new WP_Query( $args );

		while ( $loop->have_posts() ) : $loop->the_post();

			$product_obj     = wc_get_product( get_the_ID() );
			$product         = $product_obj->get_data();
			$product['type'] = $product_obj->get_type();

			$ignore_product = false;

			if ( $query_params['exclude_variable_products'] && $product['type'] == 'variable' ) {
				$ignore_product = true;
			}

			//update master category meta for variable products
			if(isset($query_params['update_master_category_on_variations']) && $query_params['update_master_category_on_variations'] == 1 && $product['type'] == 'variable' && !$ignore_product){
				$parent_master_category_id = get_post_meta( get_the_ID(), 'sln_product_category_master', true );
				if($parent_master_category_id) {
					//get the variations
					$variations = $product_obj->get_children();
					if ( ! empty( $variations ) ) {
						foreach ( $variations as $variation_id ) {
							update_post_meta( $variation_id, 'sln_product_category_master', esc_attr( $parent_master_category_id ) );
						}
					}
				}
			}

			if ( $query_params['trigger_product_update_webhook'] && ! $ignore_product ) {
                do_action('sew_product_updated', get_the_ID(), $product_obj);
			}

			if ( ! $ignore_product ) {
				$products[] = $product;
			}

		endwhile;

		wp_reset_query();

	}

	$rest_response['count'] = count($products);
	$rest_response['products'] = $products;

	return new WP_REST_Response($rest_response,200);

}

function sew_get_all_product_variations($request){

	$query_params = $request->get_query_params();

	$variations = array();

	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	if(empty($query_params['page'])){
		$query_params['page'] = 1;
	}

	if(empty($query_params['per_page'])){
		$query_params['per_page'] = 100;
	}

	$args = array(
		'post_type'      => 'product_variation',
		'posts_per_page' => $query_params['per_page'],
		'paged'          => $query_params['page'],
        'orderby'        => 'ID',
        'order'        => 'ASC'
	);

    //support for WPML
	if(!empty($query_params['lang'])){
        if($query_params['lang'] == 'all') {
            //suppress_filters to show variations for all languages
            $args['suppress_filters'] = 1;
        }
	}

    if(isset($query_params['include'])){
        $variation_ids = explode(',', $query_params['include']);
        $args['post__in'] = $variation_ids;
    }

	$loop = new WP_Query( $args );

    $tax_class_array = array();
	while ( $loop->have_posts() ) : $loop->the_post();
		$variation_raw = wc_get_product(get_the_ID());
		//check that a product was returned
		if($variation_raw) {
			$variation = $variation_raw->get_data();
			$parent_id = $variation['parent_id'];
			$_product = wc_get_product( $parent_id );
			if ( $_product ) {
				$parent_product                = $_product->get_data();

                //set status to same as parent
                $variation['status']        = $parent_product['status'];

				//Tax class Parent logic
				if ( $variation['tax_class'] == 'parent' && ! isset( $tax_class_array[ $parent_id ] ) ) {
					$tax_class_array[ $parent_id ] = $parent_product['tax_class']; // store to reuse it
					$variation['tax_class']        = $parent_product['tax_class'];
				} elseif ( $variation['tax_class'] == 'parent' ) {
					$variation['tax_class'] = $tax_class_array[ $parent_id ];
				}

				//get parent product categories and set the same for the variation
				$variation['categories'] = sew_get_parent_product_categories($parent_id, array(), false);

				if(!empty($parent_product['category_ids'])){
					$variation['category_ids'] = $parent_product['category_ids'];
				}

			}

			$variation['type'] = 'variation';
			$variations[]      = $variation;
		}

	endwhile;

	wp_reset_query();

	return new WP_REST_Response($variations,200);

}

//when variable products are published, webhooks are not sent for all variations sometimes
function sew_woocommerce_product_status_changed($new_status, $old_status, $post){

    if(($new_status != $old_status) && $post->post_type == 'product') {
        global $sew_plugin_settings;
        $product_id = $post->ID;
        sew_log_data('wc_product_update', "product updated {$post->ID} and status changed from $old_status to $new_status");

        if (isset($sew_plugin_settings['product_sync_disabled']) && $sew_plugin_settings['product_sync_disabled'] == false) {
            sew_log_data('wc_product_update', "product ID {$product_id}. Passed checks for product sync disabled.");
            $product_obj = wc_get_product($product_id);

            if (!empty($product_obj)) {
                if ($product_obj->is_type('variable')) {
                    if(sew_check_product_status_valid_to_send($product_obj)){
                        sew_log_data('wc_product_update', "product ID {$product_id}. Passed checks for valid status.");
                        sew_trigger_webhook_for_variations($product_obj);
                    }
                }
            }

        }
    }
}
function sew_product_meta_updated($meta_id, $post_id, $meta_key, $meta_value)
{
    global $sew_plugin_settings;

    if (isset($sew_plugin_settings['product_sync_disabled']) && $sew_plugin_settings['product_sync_disabled'] == false) {

        if ($meta_key == 'sln_product_category_master') {
            //check if the post type is a variable product
            $post_type = get_post_type($post_id);

            if ($post_type === 'product') {
                sew_set_sln_product_category_master_for_variations($post_id, $meta_value);
            }
        }
    }
}

//only fires when updating a meta value that already exists
//not using updated_post_meta because that fires only when the meta_value is changing to a new value
add_action('update_post_meta', 'sew_product_meta_updated', 10, 4);
//only fires when adding a new meta key that doesn't already exist
add_action('added_post_meta', 'sew_product_meta_updated', 10, 4);

function sew_set_sln_product_category_master_for_variations($product_id,$sln_product_category_master){
    //get the product
    $product_obj = wc_get_product($product_id);
    //get the variations
    $variations = $product_obj->get_children();
    if (!empty($variations)) {
        foreach ($variations as $variation_id) {

            $current_cat_id = get_post_meta( $variation_id, 'sln_product_category_master', true );
            //check if the category has changed and update category for variations and trigger product webhooks
            if($current_cat_id != $sln_product_category_master) {
                //add the master category to the variation post meta
                update_post_meta($variation_id, 'sln_product_category_master', esc_attr($sln_product_category_master));

                //trigger the product update webhook for the variation
                $variation_product_obj = wc_get_product($variation_id);
                do_action( 'sew_product_updated', $variation_id, $variation_product_obj );
            }
        }
    }
}

function sew_trigger_webhook_for_variations($product_obj){
    global $sew_plugin_settings;
    //get the variations
    $variations = $product_obj->get_children();
    if (!empty($variations)) {
        foreach ($variations as $variation_id) {
            //trigger the product update webhook
            $variation_product_obj = wc_get_product($variation_id);
            //check that variation exists
            if (!empty($variation_product_obj)) {
                sew_log_data('wc_product_update', "Variation ID {$variation_id} - triggering webhook.");
                do_action( 'sew_product_updated', $variation_id, $variation_product_obj );
            }
        }
    }
}

function sew_get_parent_product_categories($parent_id, $categories, $append=true){

	if(!$append){
		unset($categories);
		$categories = [];
	}

	$parent_categories = wc_get_object_terms( $parent_id, 'product_cat' );

	if(!empty($parent_categories)){
		foreach($parent_categories as $parent_category_raw){
			$parent_category['id'] = $parent_category_raw->term_id;
			$parent_category['name'] = $parent_category_raw->name;
			$parent_category['slug'] = $parent_category_raw->slug;
			$categories[] = $parent_category;
		}

	}

	return $categories;
}


function sew_update_product($request){
	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}
	$products_data = json_decode( $request->get_body(), true );
	$query_params_data = $request->get_query_params();
	$p_arr = $query_params_data['products'];
	$ids_arr = explode(',', $p_arr);
	$post_type = array('product','product_variation');
	$post_statuses = array('publish','pending','draft','auto-draft','future','private','inherit','trash');
	$response = array();
    
	$product_ids = array_keys($products_data);

	$sln_post_meta_arr = array('sln_product_type',
        'sln_sale_price_measurement_scheme_item_id',
        'sln_sale_price_measurement_unit_volume',
        'sln_unit_multiplier',
        'sln_wc_measurement_unit',
        'sln_ignore_stock_update',
        'sln_ignore_product_update');
	
	$all_ids = get_posts(array(
		'post_type' => $post_type,
		'numberposts' => -1,
		'post_status' => $post_statuses,
		'fields' => 'ids',
		'post__in' => $product_ids
	));

	foreach($products_data as $product_id => $product_value){
		if(in_array($product_id, $all_ids)){
			try{

			    //loop through each product meta
			    foreach($sln_post_meta_arr as $sln_post_meta){
                    //check if set in payload received
                    if(isset($product_value[$sln_post_meta])){
                        //if we received sln_product_type, make sure it is in lowercase
                        if($product_value['sln_product_type']){
                            $product_value['sln_product_type'] = strtolower($product_value['sln_product_type']);
                        }
                        update_post_meta($product_id, $sln_post_meta, $product_value[$sln_post_meta]);
                    }
                }

				$response[$product_id] = true;
				
			}catch(Exception $e) {
				echo 'Caught exception: ', $e->getMessage(), "\n";
				$response[$product_id] = false;
			}
		}else{
			$response[$product_id] = null;
		}	
	}

	return new WP_REST_Response($response,200);
}

function sew_check_product_valid_to_send($product){

    if(!sew_check_product_status_valid_to_send($product)){
        return false;
    }

    if(!sew_check_product_type_valid_to_send($product)){
        return false;
    }

    return true;
}

function sew_check_product_type_valid_to_send($product){
    //check product type is valid
    $invalid_product_types[] = 'variable';
    if(in_array($product->get_type(), $invalid_product_types )){
        return false;
    }
    return true;
}

function sew_check_product_status_valid_to_send($product){
    global $sew_plugin_settings;

    //check status is valid
    $invalid_statuses[] = 'auto-draft';
    if(!$sew_plugin_settings['product_sync_draft_status_enabled']){
        $invalid_statuses[] = 'draft';
    }

    if(in_array($product->get_status(), $invalid_statuses )){
        return false;
    }

    return true;
}
function sew_check_product_webhook_from_stock($product, $product_id){
    global $sew_plugin_settings;

    //fires before the product is saved so the modified dt is not yet updated
    //use current time
    $product_modified =  new DateTime(current_time('mysql'));

    $sew_stock_updated = $product->get_meta('sew_stock_updated');

    //check if sew_stock_updated meta is set
    if(!$sew_stock_updated){
        $sew_stock_updated = '1970-01-01 00:00:00';
    }

    $product_sew_stock_modified = new DateTime($sew_stock_updated);

    $interval = $product_modified->getTimestamp() - $product_sew_stock_modified->getTimestamp();

    sew_log_data('wc_product_update', "sew_check_product_webhook_from_stock for product ID {$product_id}");
    sew_log_data('wc_product_update', "product_modified:");
    sew_log_data('wc_product_update', $product_modified, true);
    sew_log_data('wc_product_update', "sew_stock_updated:");
    sew_log_data('wc_product_update', $sew_stock_updated, true);
    sew_log_data('wc_product_update', "product_sew_stock_modified:");
    sew_log_data('wc_product_update', $product_sew_stock_modified, true);
    sew_log_data('wc_product_update', "interval: {$interval}");
    sew_log_data('wc_product_update', "-------------------------------------------------------");


    //if difference is greater than x seconds then allow the webhook to trigger
    if(abs($interval) <= $sew_plugin_settings['webhook_settings']['interval_for_product_update_webhook']){
        return true;
    }

    return false;
}

//Legacy webhooks processing
//To be removed once all users have upgraded the plugin
function sew_product_webhook_scheduled_action($product_id, $event){

    sew_log_data('scheduled_actions', "sew_product_webhook_scheduled_action hook run for product ID {$product_id} and event = $event.");

    $action = '';

    if($product_id) {
        //get the product
        $product_obj = wc_get_product( $product_id );
        sew_log_data('scheduled_actions', "product ID {$product_id} retrieved");
        sew_log_data('scheduled_actions', $product_obj, true);

        $post_status = get_post_status($product_id);
        sew_log_data('scheduled_actions', "get_post_status {$post_status}");

        if(!empty($product_obj)) {

            $post_status = get_post_status($product_id);
            $product = $product_obj->get_data();

            if($post_status == 'trash' || $product['status'] == 'trash'){
                $action = 'delete';
            }else {
                if (isset($product['date_modified_gmt']) && isset($product['date_created_gmt']) && $product['date_created_gmt'] == $product['date_modified_gmt'] ) {
                    $action = 'create';
                } else {
                    $action = 'update';
                }
            }

        }else{
            $action = 'delete';
        }

        sew_log_data('scheduled_actions', "product ID {$product_id} action: $action");

        if ( $action == 'create' ) {
            do_action( 'sew_product_created', $product_id, $product_obj );
            sew_log_data('scheduled_actions', "sew_product_created called for product ID {$product_id}");
        }

        if ( $action == 'update' ) {
            do_action( 'sew_product_updated', $product_id, $product_obj );
            sew_log_data('scheduled_actions', "sew_product_updated called for product ID {$product_id}");
        }

        if ( $action == 'delete' ) {
            do_action('sew_product_delete', $product_id);
            sew_log_data('scheduled_actions', "sew_product_delete called for product ID {$product_id}");
        }

    }else{
        sew_log_data('scheduled_actions', "product ID not supplied");
    }

}
add_action( 'sew_product_webhook_sa', 'sew_product_webhook_scheduled_action', 10, 2 );