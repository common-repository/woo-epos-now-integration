<?php
add_action( 'rest_api_init', 'sew_api_register_routes' );
function sew_api_register_routes() {

	$version = '1';
	$namespace = 'wc-slynk/v' . $version; //important to have wc- in the namespace to use the WC authentication

	register_rest_route(
		$namespace,
		'/wc-orders',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_process_orders',
                'permission_callback' => '__return_true',
			),
			array(
				'methods' => 'PUT',
				'callback' => 'sew_update_orders',
                'permission_callback' => '__return_true',
			)
		)
	);

    register_rest_route(
		$namespace,
		'/wc-orders/update-meta',
		array(
			array(
				'methods' => 'PUT',
				'callback' => 'sew_update_order_meta',
                'permission_callback' => '__return_true',
			)
		)
	);

    register_rest_route(
        $namespace,
        '/wc-refund-orders',
        array(
            array(
                'methods' => 'GET',
                'callback' => 'sew_process_refund_orders',
                'permission_callback' => '__return_true',
            )
        )
    );

	register_rest_route(
		$namespace,
		'/wc-orders-by-product',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_get_orders_by_product',
				'permission_callback' => '__return_true',
			)
		)
	);

	register_rest_route(
		$namespace,
		'/wc-products',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_get_products',
				'permission_callback' => '__return_true',
			),
		)
	);

	//doesn't yet exist in woocommerce rest api
	register_rest_route(
		$namespace,
		'/wc-products-variations',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_get_all_product_variations',
                'permission_callback' => '__return_true',
			)
		)
	);

	//get wc tax classes with ID
	register_rest_route(
		$namespace,
		'/wc-tax-classes',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_get_all_tax_classes',
                'permission_callback' => '__return_true',
			)
		)
	);

	register_rest_route(
		$namespace,
		'/wc-products-stock-instant',
		array(
			array(
				'methods' => 'PUT',
				'callback' => 'sew_save_product_stock_instant',
                'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		$namespace,
		'/wc-products-stock',
		array(
			array(
				'methods' => 'PUT',
				'callback' => 'sew_save_product_stock',
                'permission_callback' => '__return_true',
			),
			array(
				'methods' => 'GET',
				'callback' => 'sew_process_product_stock',
                'permission_callback' => '__return_true',
			)
		)
	);

	register_rest_route(
		$namespace,
		'/wc-products-stock-full',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_get_full_stock_sync_data',
                'permission_callback' => '__return_true',
			)
		)
	);

	register_rest_route(
		$namespace,
		'/wc-run-activation-tasks',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_plugin_activate',
                'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		$namespace,
		'/wc-product-sync',
		array(
			array(
				'methods' => 'PUT',
				'callback' => 'sew_update_product',
                'permission_callback' => '__return_true',
			),
		)
	);

	//get wp options
	register_rest_route(
		$namespace,
		'/wp-options',
		array(
			array(
				'methods' => 'GET',
				'callback' => 'sew_get_wp_options',
				'permission_callback' => '__return_true',
			)
		)
	);

    register_rest_route(
        $namespace,
        '/update-options',
        array(
            array(
                'methods' => 'PUT',
                'callback' => 'sew_update_options',
                'permission_callback' => '__return_true',
            )
        )
    );

    register_rest_route(
        $namespace,
        '/regenerate-webhooks',
        array(
            array(
                'methods' => 'PUT',
                'callback' => 'sew_regenerate_webhooks',
                'permission_callback' => '__return_true',
            )
        )
    );
}

function sew_api_authentication(){

    $check_permissions = true;
    $perform_auth = apply_filters( 'slynk_rest_check_permissions', $check_permissions);

	//use the WooCommerce Authentication
    if($perform_auth) {
        $api_authentication = new WC_REST_Authentication();
        //if the authentication fails, WooCommerce throws a 401 exception and returns the response automatically
        $result = $api_authentication->authenticate(false);
        return $result;
    }else{
        return true;
    }

}

add_action('init', 'sew_handle_preflight');
function sew_handle_preflight() {
    if(esc_attr( get_option('sew_add_cors_headers') ) == 1) {
        $origin = get_http_origin();
        if ($origin === 'https://dashboard.slynk.io') {
            header("Access-Control-Allow-Origin: https://dashboard.slynk.io");
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
            header("Access-Control-Allow-Credentials: true");
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, X-WP-Nonce, Content-Type, Accept, Authorization');
            if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
                status_header(200);
                exit();
            }
        }
    }
}

/*
 * Add logging for all REST API requests for debugging
 * Useful if other apps are making API requests
 */
if(esc_attr( get_option( 'sew_log_all_api_requests' ) )) {
	add_filter( 'rest_pre_echo_response', 'sew_log_all_api_responses', 10, 3 );
}

function sew_log_all_api_responses( $response, $object, $request ) {

	global $sew_plugin_settings;
	if($sew_plugin_settings['sew_log_all_api_requests'] == 1){
		sew_log_data('api-all',  '-------------------------------------------------------', false);
		sew_log_data('api-all',  '$request', false);

		sew_log_data('api-all',  'METHOD: '.$request->get_method(), false);
		sew_log_data('api-all',  'ROUTE: '.$request->get_route(), false);

		sew_log_data('api-all',  'HEADERS', false);
		sew_log_data('api-all',  $request->get_headers(), true);

		sew_log_data('api-all',  'BODY', false);
		sew_log_data('api-all',  $request->get_body(), true);

/*		sew_log_data('api-all',  '$object', false);
		sew_log_data('api-all',  $object, true);*/
		sew_log_data('api-all',  '$response', false);
		sew_log_data('api-all',  $response, true);
	}

	return $response;
}
