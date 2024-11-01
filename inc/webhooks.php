<?php
/**
 * Sets up webhooks
 */

function sew_webhooks_woocommerce_actions_filters_init() {

    //register our webhooks
    add_filter( 'woocommerce_webhook_topic_hooks', 'sew_add_new_topic_hooks' );
    add_filter( 'woocommerce_valid_webhook_events', 'sew_add_new_topic_events' );
    add_filter( 'woocommerce_webhook_topics', 'sew_add_new_webhook_topics' );

    add_filter( 'woocommerce_webhook_http_args', 'sew_wc_webhook_headers', 10, 3 );
    add_filter( 'woocommerce_webhook_payload', 'sew_wc_webhook_payload', 10, 4 );
    add_action( 'woocommerce_webhook_delivery', 'sew_wc_webhook_delivery_response',10,5);

    add_action('woocommerce_webhook_disabled_due_delivery_failures', 'sew_webhooks_woocommerce_webhook_disabled_due_delivery_failures', 10, 1);

    //Order webhooks
    add_action( 'woocommerce_order_status_changed', 'sew_filter_orders_before_sending', 10, 3 );
    add_action( 'woocommerce_order_actions', 'sew_wc_order_action', 10, 1 );
    add_action( 'woocommerce_order_action_sew_send_to_slynk', 'sew_send_order_to_slynk_manually' );
    add_action( 'woocommerce_order_refunded', 'sew_woocommerce_order_refunded', 10, 2 );
    add_action( 'woocommerce_refund_created', 'sew_woocommerce_refund_created',10,2 );
    add_action( 'woocommerce_order_action_sew_send_refund', 'sew_send_order_refund_to_slynk_manually');
    add_action( 'add_meta_boxes', 'sew_wc_order_add_meta_box' , 10, 2 );
    add_action( 'manage_shop_order_posts_custom_column', 'sew_custom_wc_orders_cols_func' , 10, 2 );
    add_filter( 'manage_edit-shop_order_columns', 'sew_custom_wc_orders_cols' , 10, 2 );
    add_filter( 'manage_woocommerce_page_wc-orders_columns', 'sew_custom_wc_orders_cols' , 10, 2 );
    add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'sew_custom_wc_orders_cols_func' , 10, 2);
    add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'sew_wc_orders_handle_custom_query_var', 10, 2 );

    //Product webhooks
    add_action( 'woocommerce_new_product', 'sew_woocommerce_create_update_product_callback', 10, 2 );
    add_action( 'woocommerce_new_product_variation', 'sew_woocommerce_create_update_product_callback', 10, 2 );
    add_action( 'woocommerce_update_product', 'sew_woocommerce_create_update_product_callback', 10, 2 );
    add_action( 'woocommerce_update_product_variation', 'sew_woocommerce_create_update_product_callback', 10, 2 );

    add_action( 'wp_trash_post', 'sew_woocommerce_delete_product_callback', 10, 1 );
    add_action( 'wp_delete_post', 'sew_woocommerce_delete_product_callback', 10, 1 );
    add_action( 'woocommerce_trash_product_variation', 'sew_woocommerce_delete_product_callback', 10, 1 );
    add_action( 'woocommerce_delete_product_variation', 'sew_woocommerce_delete_product_callback', 10, 1 );

    add_action('transition_post_status', 'sew_woocommerce_product_status_changed', 10, 3);
}
add_action('woocommerce_loaded', 'sew_webhooks_woocommerce_actions_filters_init', 10, 0);

/**
 * sew_add_new_topic_hooks will add a new webhook topic hook.
 * @param array $topic_hooks Existing topic hooks.
 */
function sew_add_new_topic_hooks( $topic_hooks ) {
    // Array that has the topic as resource.event with arrays of actions that call that topic.
    $new_hooks = array(
        'order.sew_filter' => array(
            'sew_order_filter',
        ),
        'order.sew_refund_filter' => array(
            'sew_refund_order_filter',
        ),
        'product.sew_stock_track' => array(
            'sew_product_stock',
        ),
        'product.sew_created'  => array(
            'sew_product_created',
        ),
        'product.sew_updated'  => array(
            'sew_product_updated',
        ),
        'product.sew_deleted'  => array(
            'sew_product_delete',
        ),
    );
    return array_merge( $topic_hooks, $new_hooks );
}

/**
 * sew_add_new_topic_events will add new events for topic resources.
 * @param array $topic_events Existing valid events for resources.
 */

function sew_add_new_topic_events( $topic_events ) {
    // New events to be used for resources.
    $new_events = array(
        'sew_filter', 'sew_stock_track', 'sew_refund_filter', 'sew_created', 'sew_updated', 'sew_deleted'
    );
    return array_merge( $topic_events, $new_events );
}

/**
 * sew_add_new_webhook_topics adds the new webhook to the dropdown list on the Webhook page.
 * @param array $topics Array of topics with the i18n proper name.
 */

function sew_add_new_webhook_topics( $topics ) {
    // New topic array to add to the list, must match hooks being created.
    $new_topics = array(
        'order.sew_filter' => __( 'Slynk Order Processing', 'woocommerce' ),
        'order.sew_refund_filter' => __( 'Slynk Refund Processing', 'woocommerce' ),
        'product.sew_stock_track' => __( 'Slynk Product Stock Change', 'woocommerce' ),
        'product.sew_created' => __( 'Slynk Product Create', 'woocommerce' ),
        'product.sew_updated' => __( 'Slynk Product Update', 'woocommerce' ),
        'product.sew_deleted' => __( 'Slynk Product Delete', 'woocommerce' ),
    );
    return array_merge( $topics, $new_topics );
}

//add auth headers to our webhooks
function sew_wc_webhook_headers($http_args, $arg, $webhook_id) {

    global $sew_plugin_settings;

    sew_log_data('webhooks', $http_args, true);
    sew_log_data('webhooks', $arg, true);
    sew_log_data('webhooks', $webhook_id, true);

    //get the webhook
    try {
        $webhook = wc_get_webhook( $webhook_id );
    }
    catch(Exception $e) {
        sew_log_data('webhooks', 'sew_wc_webhook_headers - Error getting webhook with ID '.$webhook_id.' to set headers. Error message: '.$e->getMessage());
        return $http_args;
    }

    //check that webhook was returned
    if(!$webhook){
        sew_log_data('webhooks', 'Error retrieving webhook. Null returned.');
        return $http_args;
    }

    //check delivery URL - if ours then add our headers
    if($webhook->get_delivery_url() == $sew_plugin_settings['webhook_settings']['delivery_url']){
        $http_args['headers']['Authorization'] = 'Basic '. base64_encode($sew_plugin_settings['authentication']['key'].':'.$sew_plugin_settings['authentication']['secret']);
    }

    return $http_args;
}

function sew_wc_webhook_payload($payload, $resource, $resource_id, $webhook_id){

    global $sew_plugin_settings;
    global $woocommerce;
    //get the webhook
    try {
        $webhook = wc_get_webhook( $webhook_id );
        sew_log_data('webhooks', 'Fetched webhook with ID '.$webhook_id, false);
        sew_log_data('webhooks', $webhook, true);
        sew_log_data('webhooks', 'Payload original');
        sew_log_data('webhooks', $payload, true);
    }
    catch(Exception $e) {
        sew_log_data('webhooks', 'Error getting webhook with ID '.$webhook_id.' to set payload. Error message: '.$e->getMessage());
        return $payload;
    }

    //check that webhook was returned
    if(!$webhook){
        sew_log_data('webhooks', 'Error retrieving webhook. Null returned.');
        return $payload;
    }

    try {
        $topic = $webhook->get_topic();
    }
    catch(Exception $e) {
        sew_log_data('webhooks', 'Error getting webhook topic for webhook with ID '.$webhook_id.' to set payload. Error message: '.$e->getMessage());
        return $payload;
    }

    //check delivery URL and if it is an order webhook - if ours then add additional order data
    if($topic === 'order.sew_filter' || $topic === 'order.sew_refund_filter' ) {

        $is_refund = false;

        if($webhook->get_topic() === 'order.sew_refund_filter'){
            $is_refund = true;
        }

        if ($webhook->get_delivery_url() == $sew_plugin_settings['webhook_settings']['delivery_url']) {
            $payload = sew_build_order_data($payload, $webhook_id, $is_refund);
        }
    }

    //check delivery URL and if it is a created/updated product webhook
    if($topic === 'product.sew_created' || $topic === 'product.sew_updated') {
        if ($webhook->get_delivery_url() == $sew_plugin_settings['webhook_settings']['delivery_url']) {
            if(!empty($payload['id'])) {

                //WC returns the SKU of the parent product if it is not set at the variation level
                //Need to get it from post meta
                $actual_sku                  = get_post_meta( $payload['id'], '_sku', true );
                $payload['sku_woo_original'] = $payload['sku'];
                $payload['sku']              = $actual_sku;

                //When a variation is first created WC sets the status to published regardless of the status of the variable product
                //check parent product status

                //sew_log_data('variation_create', $payload, true);
                if($payload['type'] == 'variation' && $payload['parent_id'] > 0){

                    $product_obj = wc_get_product( $payload['parent_id'] );
                    sew_log_data('webhooks', '$product_obj');
                    sew_log_data('webhooks', $product_obj, true);

                    $product_data = $product_obj->get_data();
                    sew_log_data('webhooks', '$product_data');
                    sew_log_data('webhooks', $product_data, true);

                    //set parent product status for variation
                    if(!empty($product_data['status'])){
                        $payload['status_woo_original'] = $payload['status'];
                        $payload['status'] = $product_data['status'];
                    }

                    //set the categories to be the same as the parent product categories
                    $payload['categories'] = sew_get_parent_product_categories($payload['parent_id'], array(), false);

                    if(!empty($product_data['category_ids'])){
                        $payload['category_ids'] = $product_data['category_ids'];
                    }
                }

            }

            sew_log_data('webhooks', 'Payload final');
            sew_log_data('webhooks', $payload, true);

        }
    }

    //check delivery URL and if it is a deleted product webhook
    if($topic === 'product.sew_deleted') {
        //get woo product ID from webhooks args and rebuild payload
        if ($webhook->get_delivery_url() == $sew_plugin_settings['webhook_settings']['delivery_url']) {
            unset( $payload );
            $payload['id'] = $resource_id;
        }
    }

    return $payload;
}

function sew_wc_build_product_data($product_id){

    $product = array();

    if(!$product_id){
        return false;
    }

    $product_raw = wc_get_product( $product_id );
    $product_data = $product_raw->get_data();

    $product['parent_id'] = $product_data['parent_id'];
    $product['name'] = $product_data['name'];
    $product['slug'] = $product_data['slug'];
    $product['category_ids'] = $product_data['category_ids'];
    $product['tag_ids'] = $product_data['tag_ids'];
    $product['shipping_class_id'] = $product_data['shipping_class_id'];
    $product['attributes'] = $product_raw->get_attributes();

    return $product;

}

function sew_wc_webhook_delivery_response($http_args, $response, $duration, $arg, $webhook_id){

    global $sew_plugin_settings;

    try {
        $webhook = wc_get_webhook( $webhook_id );
    }
    catch(Exception $e) {
        sew_log_data('webhooks', 'sew_wc_webhook_delivery_response - Error getting webhook with ID '.$webhook_id.' to set headers. Error message: '.$e->getMessage());
        return;
    }

    //check delivery URL - if not ours then exit
    if($webhook->get_delivery_url() != $sew_plugin_settings['webhook_settings']['delivery_url']){
        return;
    }

    sew_log_data('webhooks','webhook response received for webhook ID '.$webhook_id);

    //sew_log_data('webhooks', $webhook_id, false);
    sew_log_data('webhooks', '$http_args:', false);
    sew_log_data('webhooks', $http_args, true);

    sew_log_data('webhooks', '$response:', false);
    sew_log_data('webhooks', $response, true);

    sew_log_data('webhooks', '$duration:', false);
    sew_log_data('webhooks', $duration, true);

    sew_log_data('webhooks', '$arg:', false);
    sew_log_data('webhooks', $arg, true);

    //check if the response is a WP_Error Object

    if (is_wp_error($response)){
        //error already logged by printing $response in the log
        //do not proceed further
        return;
    }


    //check response for status, if 200 then update our order meta
    if($response['response']['code'] == 200){

        if(!empty($response['body'])){

            $response_data = json_decode($response['body'],true);

            if(array_key_exists('refund',$response_data)){
                unset($response_data['refund']);
                unset($response_data['http_status']);
                foreach ($response_data as $key => $val){

                    if(array_key_exists('fail', $val)){
                        continue;
                    }

                    if(empty($val['sync_date_time'])){
                        $val['sync_date_time'] = '';
                    }
                    if(empty($val['eposnow_transaction_id'])){
                        $val['eposnow_transaction_id'] = '';
                    }
                    if(empty($val['sync_notes'])){
                        $val['sync_notes'] = '';
                    }
                    if(isset($val['is_refund'])){
                        if($val['is_refund'] == true){
                            $order_data = wc_get_order($val['wc_parent_order_id']);
                            $html = "Refund Order ID: {$val['wc_order_id']} \r\n
                            Synced with Slynk: Yes \r\n
                            Last Updated: {$val['sync_date_time']} \r\n
                            EposNow ID: {$val['epn_transaction_id']} \r\n
                            Notes: {$val['sync_notes']}";
                            $order_data->add_order_note($html);
                        }
                    }
                    sew_update_wc_order_sync_status($val['wc_order_id'], 1, $val['sync_date_time'], $val['eposnow_transaction_id'], $val['sync_notes']);
                }
            }else {

                //update the order meta
                if (!empty($response_data['wc_order_id'])) {

                    if (empty($response_data['sync_date_time'])) {
                        $response_data['sync_date_time'] = '';
                    }
                    if (empty($response_data['eposnow_transaction_id'])) {
                        $response_data['eposnow_transaction_id'] = '';
                    }
                    if (empty($response_data['sync_notes'])) {
                        $response_data['sync_notes'] = '';
                    }

                    sew_update_wc_order_sync_status($response_data['wc_order_id'], 1, $response_data['sync_date_time'], $response_data['eposnow_transaction_id'], $response_data['sync_notes']);
                }
            }


        }
    }

}

function sew_check_webhook_ours($webhook){

    global $sew_plugin_settings;

    $valid_delivery_urls[] = $sew_plugin_settings['webhook_settings']['delivery_url'];
    $valid_delivery_urls[] = $sew_plugin_settings['webhook_settings']['delivery_url_legacy'];
    $valid_delivery_urls[] = $sew_plugin_settings['webhook_settings']['delivery_url_v2'];
    $valid_delivery_urls[] = esc_attr( get_option('sew_api_base_url_dev') );

    if ( in_array($webhook['delivery_url'],$valid_delivery_urls)  && in_array($webhook['topic'], $sew_plugin_settings['webhook_settings']['topics'])) {
        return true;
    } else {
        return false;
    }

}

/*
 * Check the health of the Slynk webhooks
 */
function sew_check_webhooks(){

/*
 * Check if webhook has our URL and then if it is ours:
 * If there is no topic - delete the webhook
 * Clean out duplicates
 * Loop through webhooks and activate the deactivated ones
 * Create any missing webhooks
 */

    global $sew_plugin_settings;

    $order_webhook_topics = array('order.sew_filter','order.sew_refund_filter');
    $product_webhook_topics = array('product.sew_updated','product.sew_created','product.sew_deleted');
    $stock_webhook_topics = array('product.sew_stock_track');

    //check for duplicates and empty topics
    //the newest webhook is first in the array
    $webhooks = sew_get_webhooks();

    $webhooks_found = array();

    if(!empty($sew_plugin_settings['webhook_settings']['topics'])) {
        foreach ( $sew_plugin_settings['webhook_settings']['topics'] as $webhook_topic ) {
            $webhooks_found[ $webhook_topic ] = false;
        }
    }

    if(!empty($webhooks)) {

        foreach ($webhooks as $webhook) {
            if ( ! $webhook['error'] ) {
                if (sew_check_webhook_ours($webhook['data'])) {
                    //webhook is ours
                    //check if a topic is set
                    if (empty($webhook['data']['topic'])) {
                        //webhook has our URL but no topic
                        //remove it
                        sew_delete_webhook($webhook['data']['id']);
                        sew_log_data('webhooks-check', 'webhook ID ' . $webhook['data']['id'] . ' deleted as it has no topic set', false);
                        continue;
                    }

                    //check if we have already found this webhook
                    if ($webhooks_found[$webhook['data']['topic']]) {
                        //webhook already found previously
                        //this is a duplicate
                        sew_delete_webhook($webhook['data']['id']);
                        sew_log_data('webhooks-check', 'webhook ID ' . $webhook['data']['id'] . ' deleted as it has been detected as a duplicate. topic: ' . $webhook['data']['topic'], false);
                        continue;
                    } else {
                        //webhook has not been found previously
                        $webhooks_found[$webhook['data']['topic']] = true;
                    }

                }
            }
        }
    }
    unset($webhooks);

    //remove any webhooks that should not be there
    $webhooks = sew_get_webhooks();
    if(!empty($webhooks)) {
        foreach ($webhooks as $webhook) {
            if ( ! $webhook['error'] ) {
                //if epn is master for stock
                //stock webhooks should not be enabled
                if (!$sew_plugin_settings['wc_master'] && in_array($webhook['data']['topic'], $stock_webhook_topics)) {
                    sew_delete_webhook($webhook['data']['id']);
                    sew_log_data('webhooks-check', 'webhook ID ' . $webhook['data']['id'] . ' deleted as it should not be enabled as setting for WC master is enabled. topic: ' . $webhook['data']['topic'], false);
                    continue;
                }

                //if woo is master for stock
                //order webhooks should not be enabled
                if ($sew_plugin_settings['wc_master'] && in_array($webhook['data']['topic'], $order_webhook_topics)) {
                    sew_delete_webhook($webhook['data']['id']);
                    sew_log_data('webhooks-check', 'webhook ID ' . $webhook['data']['id'] . ' deleted as it should not be enabled as setting for EPN master is enabled. topic: ' . $webhook['data']['topic'], false);
                    continue;
                }

                //if product sync is not enabled
                //product webhooks should not be enabled
                if ($sew_plugin_settings['product_sync_disabled'] && in_array($webhook['data']['topic'], $product_webhook_topics)) {
                    sew_delete_webhook($webhook['data']['id']);
                    sew_log_data('webhooks-check', 'webhook ID ' . $webhook['data']['id'] . ' deleted as it should not be enabled as product sync is disabled. topic: ' . $webhook['data']['topic'], false);
                    continue;
                }
            }
        }
    }
    unset($webhooks);


    //check for disabled webhooks
    $webhooks = sew_get_webhooks();

    if(!empty($webhooks)) {
        foreach ($webhooks as $webhook) {
            if ( ! $webhook['error'] ) {
                if (sew_check_webhook_ours($webhook['data'])) {
                    //webhook is ours
                    //check if webhook is active
                    if ($webhook['data']['status'] != 'active') {
                        //webhook is not active
                        //re-activate it
                        sew_update_webhook($webhook['data']['id'], 'active', $sew_plugin_settings['webhook_settings']['api_version']);
                        sew_log_data('webhooks-check', 'webhook ID ' . $webhook['data']['id'] . ' enabled as it was detected as disabled. topic: ' . $webhook['data']['topic'], false);
                    }
                }
            }
        }
    }
    unset($webhooks);
    unset($webhooks_found);


    //create any missing webhooks
    $webhooks = sew_get_webhooks();
    $webhooks_found = array();

    if(!empty($sew_plugin_settings['webhook_settings']['topics'])) {
        foreach ( $sew_plugin_settings['webhook_settings']['topics'] as $webhook_topic ) {
            $webhooks_found[ $webhook_topic ] = false;
        }
    }

    if(!empty($webhooks)) {
        foreach ($webhooks as $webhook) {
            if ( ! $webhook['error'] ) {
                if (sew_check_webhook_ours($webhook['data'])) {
                    //webhook is ours
                    $webhooks_found[$webhook['data']['topic']] = true;
                }
            }
        }
    }
    unset($webhooks);

    //create order webhooks
    foreach($order_webhook_topics as $topic){
        //check if webhook exists
        if(!$webhooks_found[$topic]) {
            //webhook does not exist
            //create it
            if (!$sew_plugin_settings['wc_master']) {
                $new_webhook_id = sew_create_webhook($topic);
                if($new_webhook_id){
                    sew_log_data('webhooks-check','new webhook created with ID: '.$new_webhook_id.' and topic: '.$topic,false);
                }
            }
        }
    }

    //create stock webhooks
    foreach($stock_webhook_topics as $topic){
        //check if webhook exists
        if(!$webhooks_found[$topic]) {
            //webhook does not exist
            //create it
            if ($sew_plugin_settings['wc_master']) {
                $new_webhook_id = sew_create_webhook($topic);
                if($new_webhook_id){
                    sew_log_data('webhooks-check','new webhook created with ID: '.$new_webhook_id.' and topic: '.$topic,false);                }
            }
        }
    }

    //create product webhooks
    foreach($product_webhook_topics as $topic){
        //check if webhook exists
        if(!$webhooks_found[$topic]) {
            //webhook does not exist
            //create it
            if(!$sew_plugin_settings['product_sync_disabled']) {
                $new_webhook_id = sew_create_webhook($topic);
                if($new_webhook_id){
                    sew_log_data('webhooks-check','new webhook created with ID: '.$new_webhook_id.' and topic: '.$topic,false);                }
            }
        }
    }

}

function sew_get_webhooks(){

    $webhooks = null;

    //get the webhook ids
    $webhook_data_store = new WC_Webhook_Data_Store();
    $webhook_ids = $webhook_data_store->get_webhooks_ids();

    //get the data for the webhooks
    foreach($webhook_ids as $webhook_id){

        try {

            $webhook = wc_get_webhook( $webhook_id );

            if(!empty($webhook)){
                $webhooks[$webhook_id]['data'] = $webhook->get_data();
                $webhooks[$webhook_id]['error'] = false;
            }else{
                $webhooks[$webhook_id]['data']['id'] = $webhook_id;
                $webhooks[$webhook_id]['error'] = true;
                $webhooks[$webhook_id]['error_msg'] = 'no data returned for webhook';
            }
        }
        catch(Exception $e) {
            $webhooks[$webhook_id]['data']['id'] = $webhook_id;
            $webhooks[$webhook_id]['error'] = true;
            $webhooks[$webhook_id]['error_msg'] = $e->getMessage();
        }

    }

    return $webhooks;

}

function sew_create_webhook($topic){

    global $sew_plugin_settings;

    if($topic) {

        $webhook_user_id = esc_attr( get_option('sew_webhook_user_id'));

        if($webhook_user_id){
            if (!user_can($webhook_user_id, 'administrator')) {
                // User is not an admin
                sew_log_data('webhooks-check', 'webhook user ID set as '.$webhook_user_id.' but the user does not have the administrator capability',false);
                $webhook_user_id = sew_get_super_admin_id();
                sew_log_data('webhooks-check', 'using webhook user ID '.$webhook_user_id.' instead',false);
            }
        }else{
            sew_log_data('webhooks-check', 'webhook user ID is not set in the settings',false);
            $webhook_user_id = sew_get_super_admin_id();
            sew_log_data('webhooks-check', 'using webhook user ID '.$webhook_user_id.' instead',false);
        }

        $webhook_status = 'active';
        try {
            $webhook = new WC_Webhook();
            $webhook->set_name('Slynk (' . $topic . ')'); // User ID used while generating the webhook payload.
            $webhook->set_user_id($webhook_user_id); // User ID used while generating the webhook payload.
            $webhook->set_topic($topic); // Event used to trigger a webhook.
            //$webhook->set_secret( $sew_plugin_settings['authentication']['secret']  ); // Secret to validate webhook when received.
            $webhook->set_delivery_url($sew_plugin_settings['webhook_settings']['delivery_url']); // URL where webhook should be sent.
            $webhook->set_status($webhook_status); // Webhook status.
            $webhook->set_api_version($sew_plugin_settings['webhook_settings']['api_version']);
            $new_webhook_id = $webhook->save();

            sew_log_data('webhooks-check', 'webhook for topic '.$topic.' with ID ' . $new_webhook_id . ' created successfully');
            sew_log_data('webhooks-check', $webhook, true);

            return $new_webhook_id;

        } catch (Exception $e) {
            sew_log_data('webhooks-check', 'Unable to create webhook for topic ' . $topic . '. Error: ' . $e->getMessage());
        }
    }else{
        sew_log_data('webhooks-check', 'Unable to create webhook as topic was not defined ' . $topic,false);
    }

    return false;

}

function sew_update_webhook($webhook_id, $status = 'disabled', $version = 3){
    $webhook = wc_get_webhook( $webhook_id );
    $webhook->set_status( $status );
    $webhook->set_api_version( $version );
    $webhook->save();
}

function sew_delete_webhook($webhook_id){
    $webhook = wc_get_webhook( $webhook_id );
    $webhook->delete( true );
}

function sew_delete_all_webhooks(){
    //get all webhooks in system
    $webhooks = sew_get_webhooks();

    //check if our webhooks exist
    if(!empty($webhooks)) {
        foreach ( $webhooks as $webhook ) {
            if ( ! $webhook['error'] ) {
                if ( sew_check_webhook_ours( $webhook['data']) ) {
                    sew_delete_webhook($webhook['data']['id']);
                }
            }
        }
    }
}

function sew_webhooks_woocommerce_webhook_disabled_due_delivery_failures($webhook_id){

    sew_log_data('webhooks-check', 'webhook ID '.$webhook_id.' disabled due to to delivery failures', false);

    $webhook = wc_get_webhook( $webhook_id );
    $webhook_data = $webhook->get_data();
    if(!empty($webhook_data)){
        if ( sew_check_webhook_ours( $webhook_data ) ) {
            sew_log_data('webhooks-check', 'webhook ID '.$webhook_id.' is a slynk webhook', false);
            sew_log_data('webhooks-check', $webhook_data, true);
        }
    }

}

function sew_build_order_data($payload, $webhook_id, $is_refund = false){
    global $sew_plugin_settings;

    sew_log_data('webhooks', 'original payload for webhook ID '.$webhook_id);
    sew_log_data('webhooks', $payload, true);


    $payload = apply_filters('sew_wc_webhook_order_filter',$payload);

    sew_log_data('webhooks', 'Payload after filter for webhook ID '.$webhook_id);
    sew_log_data('webhooks', $payload, true);

    $sew_customer_data = array();

    //add customer info into the payload
    //check if customer is set for this order
    if ( $payload['customer_id'] > 0 ) {

        $user = get_user_by( 'id', $payload['customer_id'] );

        if ( ! empty( $user ) ) {

            $user_meta = get_user_meta( $payload['customer_id'], '', true );

            $sew_customer_data['id']          = $user->ID;
            $sew_customer_data['user_email']  = $user->user_email;
            $sew_customer_data['user_status'] = $user->user_status;

            if ( ! empty( $user_meta['first_name'][0] ) ) {
                $sew_customer_data['first_name'] = $user_meta['first_name'][0];
            }

            if ( ! empty( $user_meta['last_name'][0] ) ) {
                $sew_customer_data['last_name'] = $user_meta['last_name'][0];
            }

        } else {
            $sew_customer_data['id'] = 0;
        }

    } else {
        $sew_customer_data['id'] = 0;
    }

    $payload['sew_customer_data'] = $sew_customer_data;
    $payload['sew_bundle_composite_order'] = 0;
    sew_log_data('webhooks', 'payload after sew_customer_data added for webhook ID '.$webhook_id);
    sew_log_data('webhooks', $payload, true);

    //store order line items
    $main_order_line_items = array();

    //loop through line items
    foreach ( $payload['line_items'] as $line_item ) {

        //add tax rate IDs
        foreach ( $line_item['taxes'] as $tax_line ) {
            if (empty($payload['sew_tax_lines'][$tax_line['id']])) {
                $payload['sew_tax_lines'][$tax_line['id']] = WC_Tax::_get_tax_rate($tax_line['id']);
            }
        }

        //add product data if needed
        if($sew_plugin_settings['sew_full_product_data_orders']){

            $parent_product_id = 0;

            if($line_item['variation_id']){
                $product_id = $line_item['variation_id'];
                $parent_product_id = $line_item['product_id'];
            }else{
                $product_id = $line_item['product_id'];
            }

            if (empty($payload['sew_products'][$product_id])) {
                $payload['sew_products'][$product_id] = sew_wc_build_product_data($product_id);
            }

            //get parent product if needed
            if (empty($payload['sew_products'][$parent_product_id]) && $parent_product_id) {
                $payload['sew_products'][$parent_product_id] = sew_wc_build_product_data($parent_product_id);
            }
        }
        //checking bundle or composite product
        if(isset($line_item['bundled_by']) || isset($line_item['composite_parent'])){
            //checking line item have bundle or composite product
            $order_line_item = getOrderBundleCompositeItems($line_item);
            //if order line item have bundled/composite product then set flag that item have bundled/composite product
            if(count($order_line_item) > 0){
                //flag set
                $payload['sew_bundle_composite_order'] = 1;
                //check refund order
                if($is_refund){
                    $id = $line_item['id'];
                    // store bundled/composite product main order data
                    $main_order_line_items[$id] = $order_line_item;
                }
            }
        }
    }
    //add tax rate data into the payload
    //check the line items
    if ( ! empty( $payload['tax_lines'] ) ) {
        foreach ( $payload['tax_lines'] as $tax_line ) {
            if ( empty( $payload['sew_tax_lines'][ $tax_line['rate_id'] ] ) ) {
                $payload['sew_tax_lines'][ $tax_line['rate_id'] ] = WC_Tax::_get_tax_rate( $tax_line['rate_id'] );
            }
        }
    }
    //check the shipping lines
    if ( ! empty( $payload['shipping_lines'] ) ) {
        foreach ( $payload['shipping_lines'] as $shipping_line ) {
            if ( ! empty( $shipping_line['taxes'] ) ) {
                foreach ( $shipping_line['taxes'] as $shipping_line_tax ) {
                    if ( empty( $payload['sew_tax_lines'][ $shipping_line_tax['id'] ] ) ) {
                        $payload['sew_tax_lines'][ $shipping_line_tax['id'] ] = WC_Tax::_get_tax_rate( $shipping_line_tax['id'] );
                    }
                }
            }
        }
    }

    //checking refund
    if($is_refund){

        unset($payload['refunds']);
        $order = wc_get_order($payload['id']);

        sew_log_data('webhooks','$order');
        sew_log_data('webhooks', $order, true);


        $order_refunds = $order->get_refunds();
        $refund_ids = array();
        $refund_keys = array();
        // Loop through the order refunds array
        foreach( $order_refunds as $key => $refund ){
            $refund_keys[] = $key;
            $refund_id = $refund->get_id();


            $status = (int)get_post_meta($refund_id, 'sew_slynk_sync_status', true);
            if($status == 1){
                continue;
            }
            //check already in queue in to sync with eposnow.
            $refund_ids[] = $refund_id;
            $refund_orders = array();
            $refund_orders['id'] = $refund_id;
            $refund_orders['parent_id'] = $payload['id'];
            $refund_orders['discount_total'] = $refund->get_discount_total();
            $refund_orders['discount_tax'] = $refund->get_discount_tax();
            $refund_orders['shipping_total'] = $refund->get_shipping_total();
            $refund_orders['shipping_tax'] = $refund->get_shipping_tax();
            $refund_orders['cart_tax'] = $refund->get_cart_tax();
            $refund_orders['total'] = $refund->get_total();
            $refund_orders['total_tax'] = $refund->get_total_tax();
            $refund_orders['amount'] = $refund->get_amount();
            $refund_orders['reason'] = $refund->get_reason();
            $refund_orders['refunded_by'] = $refund->get_refunded_by();
            $refund_orders['refunded_payment'] = $refund->get_refunded_payment();
            $refund_orders['date_created'] = $refund->get_date_created();
            $refund_orders['sew_customer_data'] = $payload['sew_customer_data'];
            $refund_orders['customer_id'] = $payload['customer_id'];
            $refund_orders['sew_stock_adjustment'] = get_post_meta($refund_id, 'sew_stock_adjustment', true);
            $refund_orders['sew_tax_lines'] = $payload['sew_tax_lines'];


            // Loop through the order refund line items
            $refund_orders['line_items'] = getRefundLineItems($refund, $main_order_line_items);
            $refund_orders['shipping_lines'] = getRefundShipping($refund);
            $refund_orders['tax_lines'] = getRefundTaxLines($refund);

            //get refund data
            $payload['refunds'][$refund_id] = $refund_orders;
        }
    }

    sew_log_data('webhooks', 'final payload for webhook ID '.$webhook_id);
    sew_log_data('webhooks', $payload, true);
    return $payload;
}

function getRefundLineItems($order, $main_order_line_items = array()){
    $line_items = array();
    $is_composite = false;
    $is_bundle = false;
    $bundled_by_key = 'bundled_by';
    $bundled_items_key = 'bundled_items';
    $composite_parent_key = 'composite_parent';
    $composite_children_key = 'composite_children';

    $bundle_composite_refund_mapping = array();
    foreach( $order->get_items() as $item_id => $item ){

        $sew_item = array();
        $sew_item['id'] = $item_id;
        $sew_item['name'] = $item->get_name();
        $sew_item['product_id'] = $item->get_product_id();
        $sew_item['variation_id'] = $item->get_variation_id();
        $sew_item['quantity'] = $item->get_quantity();
        $sew_item['subtotal'] = $item->get_subtotal();
        $sew_item['subtotal_tax'] = $item->get_subtotal_tax();
        $sew_item['total'] = $item->get_subtotal();
        $sew_item['total_tax'] = $item->get_subtotal_tax();

        $sew_item['refunded_item_id'] = $item->get_meta('_refunded_item_id');

        $item_line_taxes = maybe_unserialize( $item['line_tax_data'] );
        if ( isset( $item_line_taxes['total'] ) ) {
            $line_tax = array();
            foreach ( $item_line_taxes['total'] as $tax_rate_id => $tax ) {
                $line_tax[ $tax_rate_id ] = array(
                    'id'       => $tax_rate_id,
                    'total'    => $tax,
                    'subtotal' => '',
                );
            }
            foreach ( $item_line_taxes['subtotal'] as $tax_rate_id => $tax ) {
                $line_tax[ $tax_rate_id ]['subtotal'] = $tax;
            }
            $sew_item['taxes'] = array_values( $line_tax );
        }


        //if any composite or bundled items found in main order
        if(count($main_order_line_items) > 0){
            $refunded_item_id  = $item->get_meta('_refunded_item_id');

            //checking refund item id exists in bundle or composite products array
            if(array_key_exists($refunded_item_id, $main_order_line_items)){
                //checking refund item id for bundled product
                if(isset($main_order_line_items[$refunded_item_id][$bundled_by_key])) {
                    $bundle_by = $main_order_line_items[$refunded_item_id][$bundled_by_key];
                    $bundled_items = $main_order_line_items[$refunded_item_id][$bundled_items_key];
                    $sew_item[$bundled_by_key] = $bundle_by;
                    $sew_item[$bundled_items_key] = $bundled_items;
                }

                //checking refund item id for composite product
                if(isset($main_order_line_items[$refunded_item_id][$composite_parent_key])) {
                    $composite_parent = $main_order_line_items[$refunded_item_id][$composite_parent_key];
                    $composite_children = $main_order_line_items[$refunded_item_id][$composite_children_key];
                    $sew_item[$composite_parent_key] = $composite_parent;
                    $sew_item[$composite_children_key] = $composite_children;
                }
                $bundle_composite_refund_mapping[$refunded_item_id] = $item_id;
            }
        }

        $line_items[] = $sew_item;
    }

    //check if any bundled or composite products are there
    if(count($main_order_line_items) > 0) {
        //loop through each line_items to map refund product ids.
        foreach ($line_items as $k => $line_item) {
            //checking for bundled items (this is a parent product)
            if (isset($line_item[$bundled_items_key]) && count($line_item[$bundled_items_key]) > 0) {
                $bundled_items = array();
                foreach ($line_item[$bundled_items_key] as $bundled_item) {
                    $bundled_items[] = $bundle_composite_refund_mapping[$bundled_item];
                }
                $line_item[$bundled_items_key] = $bundled_items;
            }

            //checking for bundle product (this is a child products)
            if (isset($line_item[$bundled_by_key]) && !empty($line_item[$bundled_by_key])) {
                $line_item[$bundled_by_key] = $bundle_composite_refund_mapping[$line_item[$bundled_by_key]];
            }

            //checking for composite products (this is a parent product)
            if (isset($line_item[$composite_children_key]) && count($line_item[$composite_children_key]) > 0) {
                $composite_children = array();
                foreach ($line_item[$composite_children_key] as $composite_item) {
                    $composite_children[] = $bundle_composite_refund_mapping[$composite_item];
                }
                $line_item[$composite_children_key] = $composite_children;
            }

            //checking for composite products (this is a child product)
            if (isset($line_item[$composite_parent_key]) && !empty($line_item[$composite_parent_key])) {
                $line_item[$composite_parent_key] = $bundle_composite_refund_mapping[$line_item[$composite_parent_key]];
            }

            $line_items[$k] = $line_item;
        }
    }

    return $line_items;
}

function getRefundShipping($order){
    $shipping_data = array();
    foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
        $shipping_data[] = array(
            'id'            => $shipping_item_id,
            'method_id'     => $shipping_item->get_method_id(),
            'method_title'  => $shipping_item->get_name(),
            'instance_id'   => $shipping_item->get_instance_id(),
            'total_tax'     => $shipping_item->get_total_tax(),
            'total'         => wc_format_decimal( $shipping_item->get_total(), 2 ),
            'taxes'         => array($shipping_item->get_taxes()),
        );
    }
    return $shipping_data;
}

function getRefundTaxLines($order){
    $tax_data = [];
    foreach ( $order->get_items( 'tax' ) as $tax_item_id => $tax_item ) {
        $tax_data[] = array(
            'id'                    => $tax_item_id,
            'rate_code'             => $tax_item->get_rate_code(),
            'rate_id'               => $tax_item->get_rate_id(),
            'label'                 => $tax_item->get_label(),
            'tax_total'             => $tax_item->get_tax_total(),
            'shipping_tax_total'    => wc_format_decimal( $tax_item->get_shipping_tax_total(), 2 ),
            'rate_percent'          => $tax_item->get_rate_percent(),
        );
    }
    return $tax_data;
}

//check and return bundled products
function getOrderBundleCompositeItems($line_item){
    $refund_products = array();

    //check if bundled
    if(isset($line_item['bundled_by'])){
        if(!empty($line_item['bundled_by']) || count($line_item['bundled_items']) > 0){
            $refund_products['bundled_items'] = $line_item['bundled_items'];
            $refund_products['bundled_by'] = $line_item['bundled_by'];
        }
    }

    //check if composite
    if(isset($line_item['composite_parent'])){
        if(!empty($line_item['composite_parent']) || count($line_item['composite_children']) > 0){
            $refund_products['composite_children'] = $line_item['composite_children'];
            $refund_products['composite_parent'] = $line_item['composite_parent'];
        }
    }

    sew_log_data('webhooks','$refund_products');
    sew_log_data('webhooks', $refund_products, true);
    return $refund_products;
}




function sew_refund_webhook_log($str, $arr = false)
{
    sew_log_data('refunds-webhook', $str, $arr);
}