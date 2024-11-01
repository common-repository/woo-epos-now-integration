<?php
function sew_get_all_tax_classes($request){

	$query_params = $request->get_query_params();

	if(!sew_api_authentication()){
        return new WP_REST_Response('Slynk WC API authorisation failed',401);
	}

	if(empty($query_params['page'])){
		$query_params['page'] = 1;
	}

	if(empty($query_params['per_page'])){
		$query_params['per_page'] = 100;
	}

	//default woo tax classes endpoint does not return the tax class ID

    $tax_classes = array();

    // Add standard class.
    $tax_classes[] = array(
        'tax_class_id' => '0',
        'id' => 'sln-wc-default-standard',
        'name' => __( 'Standard Rate (Woo default)', 'woocommerce' ),
    );

    $classes = WC_Tax::get_tax_rate_classes();

    //sew_log_data('taxes',$classes,true);

    foreach ( $classes as $class ) {

        $tax_classes[] = array(
            'tax_class_id' => $class->tax_rate_class_id,
            'id' => $class->slug,
            'name' => $class->name,
        );

    }

	return new WP_REST_Response($tax_classes, 200);

}