<?php
/**
 * Product Epos Now (Slynk) tab
 */
global $measurement_scheme;
$measurement_scheme = array(
   /* 0 =>  "Please select",*/
    1 =>  "Centimetres",
    2 =>  "Metres",
    3 =>  "Grams",
    4 =>  "Kilograms",
    5 =>  "Millilitres",
    6 =>  "Centilitres",
    7 =>  "Litres",
    8 =>  "Square Centimeter",
    9 =>  "Square Meter",
    11 =>  "Square Feet",
    12 =>  "Square Yard",
    18 =>  "Yards",
    28 =>  "1/1000 Pounds",
    29 =>  "Pounds",
);

add_filter( 'woocommerce_product_data_tabs', 'slynk_epos_now_product_data_tab' , 99 , 1 );
function slynk_epos_now_product_data_tab( $product_data_tabs ) {
    $product_data_tabs['slynk_epos_now'] = array(
        'label' => __( 'Epos Now (Slynk)'),
        'target' => 'slynk_epos_now',
        //'class' => array( 'hide_if_variable' ), //show_if_simple  Show if the product type is simple
    );
    return $product_data_tabs;
}

add_action( 'woocommerce_product_data_panels', 'add_slynk_epos_now_product_data_fields' );
function add_slynk_epos_now_product_data_fields() {
    global $woocommerce, $post, $product_object, $measurement_scheme;


    ?>
    <div id="slynk_epos_now" class="panel woocommerce_options_panel">
        <?php


         $ignore_stock_meta = $product_object->get_meta('sln_ignore_stock_update');
         $ignore_product_meta = $product_object->get_meta('sln_ignore_product_update');

        if(esc_attr( get_option('sew_ignore_stock_update_enabled', 1) ) == 1 )
        {
            woocommerce_wp_checkbox(array(
                'id'        => 'sln_ignore_stock_update',
                'label'         => __('Ignore Stock Update', 'sln-ignore-stock-update'),
                'desc_tip'      => 'true',
                'value'         => ($ignore_stock_meta == 1) ? 'yes' : 'no',
                'description'       => __('Ignore stock update')));
        }

    if(esc_attr( get_option('sew_ignore_product_update_enabled', 1) ) == 1 )
    {
        woocommerce_wp_checkbox(array(
            'id' => 'sln_ignore_product_update',
            'label' => __('Ignore Product Update', 'sln-ignore-product-update'),
            'desc_tip' => 'true',
            'value' => ($ignore_product_meta == 1) ? 'yes' : 'no',
            'description' => __('Ignore product update')));

    }

    if(esc_attr( get_option('sew_epn_title_enabled', 1) ) == 1 )
    {
        $sln_epn_title_value = $product_object->get_meta('sln_epn_title', true);
        if (empty ($sln_epn_title_value)) {
            $sln_epn_title_value = '';
        }
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_title',
                'label' => __('EPN Product Name'),
                'class' => 'sln_text_field sln_epn_title',
                'type' => 'text',
                'placeholder' => $product_object->get_title(),
                'desc_tip' => 'true',
                'description' => __('Epos Now Product Title. If this is left blank, then the default WooCommerce product title will be used. Max 256 characters.'),
                'value' => $sln_epn_title_value
            )
        );
    }

    if(esc_attr( get_option('sew_epn_description_enabled', 1) ) == 1 ) {
        //EPN description
        $sln_epn_description_value = $product_object->get_meta('sln_epn_description', true);
        woocommerce_wp_textarea_input(
            array(
                'id' => 'sln_epn_description',
                'label' => __('EPN Description'),
                'type' => 'textarea',
                'class' => 'sln_textarea_field sln_half_field sln_epn_description',
                'desc_tip' => 'true',
                'description' => __('EposNow Description. Max 3999 characters'),
                'default' => '',
                'value' => $sln_epn_description_value,
                'custom_attributes' => array('maxlength' => '3999'),
            )
        );
    }

    if(esc_attr( get_option('sew_product_type_enabled', 1) ) == 1 ) {
        woocommerce_wp_select(array(
            'id' => 'sln_product_type',
            'label' => __('EPN Product Type'),
            'class' => 'sln_product_type_select short',
            'default' => '0',
            'desc_tip' => 'true',
            'description' => __('EPN Product Type'),
            'options' => array(
                'standard' => 'Standard',
                'measured' => 'Measured',
                'weighted' => 'Weighted'
            ),
        ));
        woocommerce_wp_select(array(
            'id' => 'sln_sale_price_measurement_scheme_item_id',
            'label' => __('EPN Measurement Unit'),
            'class' => 'sln_measured_weighted short sln_epn_measure_unit',
            'desc_tip' => 'true',
            'description' => __('EPN Measurement Unit'),
            'options' => $measurement_scheme,
        ));

        $sln_spmuv_value = $product_object->get_meta('sln_sale_price_measurement_unit_volume', true);
        if (empty ($sln_spmuv_value)) {
            $sln_spmuv_value = 1;
        }
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_sale_price_measurement_unit_volume',
                'label' => __('EPN Measurement Unit Volume'),
                'type' => 'number',
                'class' => 'sln_measured_weighted sln_measurement_unit_volume',
                'desc_tip' => 'true',
                'description' => __('EPN Measurement Unit Volume'),
                'default' => '1',
                'value' => $sln_spmuv_value,
                'custom_attributes' => array('min' => '1'),
            )
        );

    }

    if(esc_attr( get_option('sew_unit_multiplier_enabled', 1) ) == 1 ) {
        $sln_cum_value = $product_object->get_meta('sln_unit_multiplier', true);
        if (empty ($sln_cum_value)) {
            $sln_cum_value = 1;
        }
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_unit_multiplier',
                'label' => __('WC Unit Multiplier'),
                'type' => 'number',
                'class' => 'sln_unit_multiplier',
                'desc_tip' => 'true',
                'description' => __('WooCommerce Unit Multiplier'),
                'value' => $sln_cum_value,
                'custom_attributes' => array('min' => '1'),
            )
        );
    }

    if(esc_attr( get_option('sew_product_type_enabled', 1) ) == 1 ) {
        $wc_measure_unit = $product_object->get_meta('sln_wc_measurement_unit', true);
        woocommerce_wp_select(array(
            'id' => 'sln_wc_measurement_unit',
            'label' => __('WC Measurement Unit'),
            'class' => 'sln_wc_measurement sln_measured_weighted short sln_wc_measure_unit',
            'desc_tip' => 'true',
            'description' => __('WC Measurement Unit'),
            // 'value'       => $wc_measure_unit,
            'options' => $measurement_scheme,
        ));
    }

    if(esc_attr( get_option('sew_product_category_master_enabled', 1) ) == 1 ) {
        $sln_product_category_master = $product_object->get_meta('sln_product_category_master');
        if (empty($sln_product_category_master)) {
            $sln_product_category_master = '';
        }

        $sln_master_cat_html = '<p class="form-field sln_product_category_master_field">';
        $sln_master_cat_html .= '<label for="sln_product_category_master">WC Master Category</label>';
        $sln_master_cat_html .= wc_help_tip('If you have more than one category selected for this product, please select the master category. This will be the category used to determine the mapped EposNow category from our category linker. If only one category is selected, then we will use that category instead.');
        $sln_master_cat_html .= sew_get_woo_categories($sln_product_category_master);
        $sln_master_cat_html .= '</p>';

        echo $sln_master_cat_html;
    }

    if(esc_attr( get_option('sew_epn_cost_price_enabled', 1) ) == 1 ) {
        //EPN Cost Price
        $sln_epn_cost_price_value = $product_object->get_meta('sln_epn_cost_price', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_cost_price',
                'label' => __('EPN Cost Price (ex tax)'),
                'type' => 'number',
                'class' => 'sln_numeric_field sln_epn_cost_price',
                'desc_tip' => 'true',
                'description' => __('EposNow Cost Price'),
                'default' => '',
                'value' => $sln_epn_cost_price_value,
                'custom_attributes' => array('step' => 'any', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_eat_out_price_enabled', 1) ) == 1 ) {
        //EPN Eat out price
        $sln_epn_eat_out_price_value = $product_object->get_meta('sln_epn_eat_out_price', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_eat_out_price',
                'label' => __('EPN Eat out price'),
                'type' => 'number',
                'class' => 'sln_numeric_field sln_epn_eat_out_price',
                'desc_tip' => 'true',
                'description' => __('EposNow Eat Out Price'),
                'default' => '',
                'value' => $sln_epn_eat_out_price_value,
                'custom_attributes' => array('step' => 'any', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_rrp_price_enabled', 1) ) == 1 ) {
        //EPN RRP price
        $sln_epn_rrp_price_value = $product_object->get_meta('sln_epn_rrp_price', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_rrp_price',
                'label' => __('EPN RRP price'),
                'type' => 'number',
                'class' => 'sln_numeric_field sln_epn_rrp_price',
                'desc_tip' => 'true',
                'description' => __('EposNow recommended retail price'),
                'default' => '',
                'value' => $sln_epn_rrp_price_value,
                'custom_attributes' => array('step' => 'any', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_barcode_enabled', 1) ) == 1 ) {
        //EPN Barcode
        $sln_epn_barcode_value = $product_object->get_meta('sln_epn_barcode', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_barcode',
                'label' => __('EPN Barcode'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_barcode',
                'desc_tip' => 'true',
                'description' => __('EposNow Barcode'),
                'default' => '',
                'value' => $sln_epn_barcode_value
            )
        );
    }

    if(esc_attr( get_option('sew_epn_order_code_enabled', 1) ) == 1 ) {
        //EPN Order code
        $sln_epn_order_code_value = $product_object->get_meta('sln_epn_order_code', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_order_code',
                'label' => __('EPN Order code'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_order_code',
                'desc_tip' => 'true',
                'description' => __('EposNow Order Code'),
                'default' => '',
                'value' => $sln_epn_order_code_value
            )
        );
    }

    if(esc_attr( get_option('sew_epn_article_code_enabled', 1) ) == 1 ) {
        //EPN Article code
        $sln_epn_article_code_value = $product_object->get_meta('sln_epn_article_code', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_article_code',
                'label' => __('EPN Article code'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_article_code',
                'desc_tip' => 'true',
                'description' => __('EposNow Article Code'),
                'value' => $sln_epn_article_code_value,
            )
        );
    }

    if(esc_attr( get_option('sew_epn_brand_id_enabled', 1) ) == 1 ) {
        //EPN Brand ID
        $sln_epn_brand_id_value = $product_object->get_meta('sln_epn_brand_id', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_brand_id',
                'label' => __('EPN Brand ID'),
                'type' => 'number',
                'class' => 'sln_int_field sln_epn_brand_id',
                'desc_tip' => 'true',
                'description' => __('EposNow Brand ID'),
                'default' => '',
                'value' => $sln_epn_brand_id_value,
                'custom_attributes' => array('step' => '1', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_supplier_id_enabled', 1) ) == 1 ) {
        //EPN Supplier ID
        $sln_epn_supplier_id_value = $product_object->get_meta('sln_epn_supplier_id', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_supplier_id',
                'label' => __('EPN Supplier ID'),
                'type' => 'number',
                'class' => 'sln_int_field sln_epn_supplier_id',
                'desc_tip' => 'true',
                'description' => __('EposNow Supplier ID'),
                'default' => '',
                'value' => $sln_epn_supplier_id_value,
                'custom_attributes' => array('step' => '1', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_tare_weight_enabled', 1) ) == 1 ) {
        //EPN Tare Weight
        $sln_epn_tare_weight_value = $product_object->get_meta('sln_epn_tare_weight', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_tare_weight',
                'label' => __('EPN Tare Weight'),
                'type' => 'number',
                'class' => 'sln_int_field sln_epn_tare_weight',
                'desc_tip' => 'true',
                'description' => __('EposNow Tare Weight'),
                'default' => '',
                'value' => $sln_epn_tare_weight_value,
                'custom_attributes' => array('step' => '1', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_size_enabled', 1) ) == 1 ) {
        //EPN Size
        $sln_epn_size_value = $product_object->get_meta('sln_epn_size', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_size',
                'label' => __('EPN Size'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_size',
                'desc_tip' => 'true',
                'description' => __('EposNow Size'),
                'default' => '',
                'value' => $sln_epn_size_value,
            )
        );
    }
        ?>
    </div>
    <?php
}

add_action('woocommerce_process_product_meta_simple', 'sew_product_save_options');
add_action('woocommerce_process_product_meta_variable', 'sew_product_save_options');
add_action('woocommerce_process_product_meta_subscription', 'sew_product_save_options');
add_action('woocommerce_process_product_meta_grouped', 'sew_product_save_options');
add_action('woocommerce_process_product_meta_bundle', 'sew_product_save_options');
add_action('woocommerce_process_product_meta_composite', 'sew_product_save_options');

function sew_product_save_options($product_id)
{
    global $sew_plugin_settings;

    $product_obj = wc_get_product($product_id);
	$product_data = $product_obj->get_data();

    $sln_ignore_stock_update = 0;
    if (isset($_POST['sln_ignore_stock_update'])) {
        $sln_ignore_stock_update   = ( $_POST['sln_ignore_stock_update'] == 'yes' ) ? 1 : 0;
    }
    update_post_meta($product_id, 'sln_ignore_stock_update', $sln_ignore_stock_update);

    $sln_ignore_product_update = 0;
    //! empty( $_POST['sln_ignore_product_update'] )
    if (isset($_POST['sln_ignore_product_update'])) {
        $sln_ignore_product_update   = ( $_POST['sln_ignore_product_update'] == 'yes' ) ? 1 : 0;
    }
    update_post_meta($product_id, 'sln_ignore_product_update', $sln_ignore_product_update);

    /*
    * New Added Custom fields by slynk
    * */
    if(isset($_POST['sln_epn_cost_price'])) {
        update_post_meta($product_id, 'sln_epn_cost_price', esc_attr($_POST['sln_epn_cost_price']));
    }
    if(isset($_POST['sln_epn_eat_out_price'])) {
        update_post_meta( $product_id, 'sln_epn_eat_out_price', esc_attr($_POST['sln_epn_eat_out_price']) );
    }
    if(isset($_POST['sln_epn_rrp_price'])) {
        update_post_meta( $product_id, 'sln_epn_rrp_price', esc_attr($_POST['sln_epn_rrp_price']) );
    }
    if(isset($_POST['sln_epn_barcode'])) {
        update_post_meta( $product_id, 'sln_epn_barcode', esc_attr($_POST['sln_epn_barcode']) );
    }
    if(isset($_POST['sln_epn_order_code'])) {
        update_post_meta( $product_id, 'sln_epn_order_code', esc_attr($_POST['sln_epn_order_code']) );
    }
    if(isset($_POST['sln_epn_article_code'])) {
        update_post_meta( $product_id, 'sln_epn_article_code', esc_attr($_POST['sln_epn_article_code']) );
    }
    if(isset($_POST['sln_epn_brand_id'])) {
        update_post_meta( $product_id, 'sln_epn_brand_id', esc_attr($_POST['sln_epn_brand_id']) );
    }
    if(isset($_POST['sln_epn_supplier_id'])) {
        update_post_meta( $product_id, 'sln_epn_supplier_id', esc_attr($_POST['sln_epn_supplier_id']) );
    }
    if(isset($_POST['sln_epn_tare_weight'])) {
        update_post_meta( $product_id, 'sln_epn_tare_weight', esc_attr($_POST['sln_epn_tare_weight']) );
    }
    if(isset($_POST['sln_epn_size'])) {
        update_post_meta( $product_id, 'sln_epn_size', esc_attr($_POST['sln_epn_size']) );
    }
    if(isset($_POST['sln_epn_description'])) {
        update_post_meta( $product_id, 'sln_epn_description', $_POST['sln_epn_description'] );
    }

    if(isset($_POST['sln_product_type']))
    {
        $sln_product_type = $_POST['sln_product_type'];
        if ($sln_product_type == 'measured' || $sln_product_type == 'weighted'){
            $sln_wc_measurement_unit = $_POST['sln_wc_measurement_unit'];
            $sln_sale_price_measurement_scheme_item_id = $_POST['sln_sale_price_measurement_scheme_item_id'];

            $valid = checkEpnWcMeasurementUnits($sln_wc_measurement_unit, $sln_sale_price_measurement_scheme_item_id);
            if (!$valid) {
                $parent_error = get_post_meta($product_id, 'slynk_error', true);
                if (!in_array($product_id, $parent_error)) {
                    if (!empty($parent_error)) $parent_error[] = $product_id;
                    else $parent_error = array($product_id);

                    //sew_log_data('variations', 'In valid : ' . $product_id);
                    //sew_log_data('variations', $parent_error, true);
                    update_post_meta($product_id, 'slynk_error', $parent_error);
                }
                return false;
            }
        }
        if (!empty($sln_product_type)) {
            update_post_meta($product_id, 'sln_product_type', $sln_product_type);
        }

        if ($sln_product_type == 'standard') {
            update_post_meta($product_id, 'sln_sale_price_measurement_scheme_item_id', '');
        } else {
            update_post_meta($product_id, 'sln_sale_price_measurement_scheme_item_id', esc_attr($_POST['sln_sale_price_measurement_scheme_item_id']));
        }
        if ($sln_product_type == 'standard') {
            update_post_meta($product_id, 'sln_sale_price_measurement_unit_volume', '');
        } else {
            update_post_meta($product_id, 'sln_sale_price_measurement_unit_volume', esc_attr($_POST['sln_sale_price_measurement_unit_volume']));
        }

        if ($sln_product_type == 'standard') {
            update_post_meta($product_id, 'sln_wc_measurement_unit', "");
        } else {
            $sln_wc_measurement_unit = $_POST['sln_wc_measurement_unit'];
            if (!empty($sln_wc_measurement_unit)) {
                update_post_meta($product_id, 'sln_wc_measurement_unit', esc_attr($sln_wc_measurement_unit));
            } else {
                update_post_meta($product_id, 'sln_wc_measurement_unit', "");
            }
        }
    }

    if(isset($_POST['sln_epn_title'])) {
        update_post_meta($product_id, 'sln_epn_title', $_POST['sln_epn_title']);
    }

    if(isset($_POST['sln_unit_multiplier'])) {
        $sln_unit_multiplier = $_POST['sln_unit_multiplier'];
        if (!empty($sln_unit_multiplier)) {
            update_post_meta($product_id, 'sln_unit_multiplier', esc_attr($sln_unit_multiplier));
        }
    }

    if(isset($_POST['sln_product_category_master'])) {
        update_post_meta($product_id, 'sln_product_category_master', esc_attr($_POST['sln_product_category_master']));
    }

}

function sln_select_plugin_scripts($hook) {
    if($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'edit.php') {
        $screen = get_current_screen();
		if($screen->id === 'product' || $screen->id === 'edit-product'){
			wp_enqueue_script( 'select_plugin_scripts', plugins_url('js/sew-admin-script.js', __DIR__) );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'sln_select_plugin_scripts' );


/**
 * Create new fields for variations
 *
*/

add_action( 'woocommerce_product_after_variable_attributes', 'sew_variation_settings_fields', 10, 3 );

function sew_variation_settings_fields( $loop, $variation_data, $variation ) {
    global $measurement_scheme;

    $ignore_stock_meta = get_post_meta( $variation->ID, 'sln_ignore_stock_update', true );
    $ignore_product_meta = get_post_meta( $variation->ID, 'sln_ignore_product_update', true );

    if(esc_attr( get_option('sew_ignore_stock_update_enabled', 1) ) == 1 ) {
        woocommerce_wp_checkbox(
            array(
            'id' => 'sln_ignore_stock_update[' . $variation->ID . ']',
            'label' => __(' Ignore Stock Update', 'sln-ignore-stock-update'),
            'desc_tip' => 'true',
            'value' => ($ignore_stock_meta == 1) ? 'yes' : 'no',

            'description' => __('Ignore stock update')
            )
        );
    }

    if(esc_attr( get_option('sew_ignore_product_update_enabled', 1) ) == 1 ) {
        woocommerce_wp_checkbox(array(
                'id' => 'sln_ignore_product_update[' . $variation->ID . ']',
                'label' => __(' Ignore Product Update', 'sln-ignore-product-update'),
                'desc_tip' => 'true',
                'value' => ($ignore_product_meta == 1) ? 'yes' : 'no',
                'description' => __('Ignore product update')
            )
        );
    }

    if(esc_attr( get_option('sew_epn_title_enabled', 1) ) == 1 ) {
        $sln_epn_title_value = get_post_meta($variation->ID, 'sln_epn_title', true);
        if (empty ($sln_epn_title_value)) {
            $sln_epn_title_value = '';
        }

        ////sew_log_data('variations',$variation,true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_title[' . $variation->ID . ']',
                'label' => __('EPN Product Title'),
                'class' => 'sln_full_field sln_epn_title',
                'type' => 'text',
                'placeholder' => $variation->post_title,
                'desc_tip' => 'true',
                'description' => __('Epos Now Product Title. If this is left blank, then the default WooCommerce product title will be used.'),
                'value' => $sln_epn_title_value
            )
        );
    }

    if(esc_attr( get_option('sew_epn_description_enabled', 1) ) == 1 ) {
        //EPN Size
        $sln_epn_description_value = get_post_meta($variation->ID, 'sln_epn_description', true);
        woocommerce_wp_textarea_input(
            array(
                'id' => 'sln_epn_description[' . $variation->ID . ']',
                'label' => __('EPN Description'),
                'type' => 'text',
                'class' => 'sln_textarea_field sln_epn_description',
                'desc_tip' => 'true',
                'description' => __('EposNow Description. Max 3999 characters'),
                'default' => '',
                'value' => $sln_epn_description_value,
                'custom_attributes' => array('maxlength' => '3999'),
            )
        );
    }

    if(esc_attr( get_option('sew_product_type_enabled', 1) ) == 1 ) {
        woocommerce_wp_select(
            array(
                'id' => 'sln_product_type[' . $variation->ID . ']',
                'label' => __('EPN Product Type'),
                'class' => 'sln_product_type_select sln_full_field',
                'desc_tip' => 'true',
                'description' => __('Choose Product Type.'),
                'value' => get_post_meta($variation->ID, 'sln_product_type', true),
                'options' => array(
                    'standard' => 'Standard',
                    'measured' => 'Measured',
                    'weighted' => 'Weighted'
                ),
            )
        );
        woocommerce_wp_select(
            array(
                'id' => 'sln_sale_price_measurement_scheme_item_id[' . $variation->ID . ']',
                'label' => __('EPN Measurement Unit'),
                'class' => 'sln_measured_weighted sln_full_field sln_epn_measure_unit',
                'desc_tip' => 'true',
                'description' => __('Choose EPN Measurement Unit.'),
                'value' => get_post_meta($variation->ID, 'sln_sale_price_measurement_scheme_item_id', true),
                'options' => $measurement_scheme,
            )
        );

        $sln_spmuv_value = get_post_meta($variation->ID, 'sln_sale_price_measurement_unit_volume', true);
        if (empty ($sln_spmuv_value)) {
            $sln_spmuv_value = 1;
        }
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_sale_price_measurement_unit_volume[' . $variation->ID . ']',
                'label' => __('EPN Price is Per'),
                'class' => 'sln_measured_weighted sln_full_field sln_measurement_unit_volume',
                'type' => 'number',
                'desc_tip' => 'true',
                'description' => __('This is the number of units for the price. For example, if you set product price to 10 and set this field to 100 and the measurement unit to Kg, this means that the price for this product is 10 per 100 Kg. You can see the equivalent fields in the Epos Now back office under the Product Pricing section for measured products.'),
                'value' => $sln_spmuv_value,
                'custom_attributes' => array('min' => '1'),
            )
        );
    }

    if(esc_attr( get_option('sew_unit_multiplier_enabled', 1) ) == 1 ) {
        $sln_um_value = get_post_meta($variation->ID, 'sln_unit_multiplier', true);
        if (empty ($sln_um_value)) {
            $sln_um_value = 1;
        }
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_unit_multiplier[' . $variation->ID . ']',
                'label' => __('WC Unit Multiplier'),
                'class' => 'sln_full_field sln_unit_multiplier',
                'type' => 'number',
                'desc_tip' => 'true',
                'description' => __('WC Unit Multiplier'),
                'value' => $sln_um_value,
                'custom_attributes' => array('min' => '1'),
            )
        );
    }

    if(esc_attr( get_option('sew_product_type_enabled', 1) ) == 1 ) {
        $wc_measure_unit = get_post_meta($variation->ID, 'sln_wc_measurement_unit', true);
        woocommerce_wp_select(
            array(
                'id' => 'sln_wc_measurement_unit[' . $variation->ID . ']',
                'label' => __('WC Measurement Unit'),
                'class' => 'sln_wc_measurement sln_measured_weighted short sln_full_field sln_wc_measure_unit',
                'desc_tip' => 'true',
                'description' => __('Choose WC Measurement Unit.'),
                'value' => $wc_measure_unit,
                'options' => $measurement_scheme,
            )
        );
    }
    /*
    * New Added Custom fields by slynk
    * */
    if(esc_attr( get_option('sew_epn_cost_price_enabled', 1) ) == 1 ) {
        //EPN Cost Price
        $sln_epn_cost_price_value = get_post_meta($variation->ID, 'sln_epn_cost_price', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_cost_price[' . $variation->ID . ']',
                'label' => __('EPN Cost Price (ex tax)'),
                'type' => 'number',
                'class' => 'sln_numeric_field sln_epn_cost_price',
                'desc_tip' => 'true',
                'description' => __('EposNow Cost Price'),
                'default' => '',
                'value' => $sln_epn_cost_price_value,
                'custom_attributes' => array('step' => 'any', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_eat_out_price_enabled', 1) ) == 1 ) {
        //EPN Eat out price
        $sln_epn_eat_out_price_value = get_post_meta($variation->ID, 'sln_epn_eat_out_price', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_eat_out_price[' . $variation->ID . ']',
                'label' => __('EPN Eat out price'),
                'type' => 'number',
                'class' => 'sln_numeric_field sln_epn_eat_out_price',
                'desc_tip' => 'true',
                'description' => __('EposNow Eat Out Price'),
                'default' => '',
                'value' => $sln_epn_eat_out_price_value,
                'custom_attributes' => array('step' => 'any', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_rrp_price_enabled', 1) ) == 1 ) {
        //EPN RRP price
        $sln_epn_rrp_price_value = get_post_meta($variation->ID, 'sln_epn_rrp_price', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_rrp_price[' . $variation->ID . ']',
                'label' => __('EPN RRP price'),
                'type' => 'number',
                'class' => 'sln_numeric_field sln_epn_rrp_price',
                'desc_tip' => 'true',
                'description' => __('EposNow recommended retail price'),
                'default' => '',
                'value' => $sln_epn_rrp_price_value,
                'custom_attributes' => array('step' => 'any', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_barcode_enabled', 1) ) == 1 ) {
        //EPN Barcode
        $sln_epn_barcode_value = get_post_meta($variation->ID, 'sln_epn_barcode', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_barcode[' . $variation->ID . ']',
                'label' => __('EPN Barcode'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_barcode',
                'desc_tip' => 'true',
                'description' => __('EposNow Barcode'),
                'default' => '',
                'value' => $sln_epn_barcode_value
            )
        );
    }

    if(esc_attr( get_option('sew_epn_order_code_enabled', 1) ) == 1 ) {
        //EPN Order code
        $sln_epn_order_code_value = get_post_meta($variation->ID, 'sln_epn_order_code', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_order_code[' . $variation->ID . ']',
                'label' => __('EPN Order code'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_order_code',
                'desc_tip' => 'true',
                'description' => __('EposNow Order Code'),
                'default' => '',
                'value' => $sln_epn_order_code_value
            )
        );
    }

    if(esc_attr( get_option('sew_epn_article_code_enabled', 1) ) == 1 ) {
        //EPN Article code
        $sln_epn_article_code_value = get_post_meta($variation->ID, 'sln_epn_article_code', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_article_code[' . $variation->ID . ']',
                'label' => __('EPN Article code'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_article_code',
                'desc_tip' => 'true',
                'description' => __('EposNow Article Code'),
                'value' => $sln_epn_article_code_value,
            )
        );
    }

    if(esc_attr( get_option('sew_epn_brand_id_enabled', 1) ) == 1 ) {
        //EPN Brand ID
        $sln_epn_brand_id_value = get_post_meta($variation->ID, 'sln_epn_brand_id', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_brand_id[' . $variation->ID . ']',
                'label' => __('EPN Brand ID'),
                'type' => 'number',
                'class' => 'sln_int_field sln_epn_brand_id',
                'desc_tip' => 'true',
                'description' => __('EposNow Brand ID'),
                'default' => '',
                'value' => $sln_epn_brand_id_value,
                'custom_attributes' => array('step' => '1', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_supplier_id_enabled', 1) ) == 1 ) {
        //EPN Supplier ID
        $sln_epn_supplier_id_value = get_post_meta($variation->ID, 'sln_epn_supplier_id', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_supplier_id[' . $variation->ID . ']',
                'label' => __('EPN Supplier ID'),
                'type' => 'number',
                'class' => 'sln_int_field sln_epn_supplier_id',
                'desc_tip' => 'true',
                'description' => __('EposNow Supplier ID'),
                'default' => '',
                'value' => $sln_epn_supplier_id_value,
                'custom_attributes' => array('step' => '1', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_tare_weight_enabled', 1) ) == 1 ) {
        //EPN Tare Weight
        $sln_epn_tare_weight_value = get_post_meta($variation->ID, 'sln_epn_tare_weight', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_tare_weight[' . $variation->ID . ']',
                'label' => __('EPN Tare Weight'),
                'type' => 'number',
                'class' => 'sln_int_field sln_epn_tare_weight',
                'desc_tip' => 'true',
                'description' => __('EposNow Tare Weight'),
                'default' => '',
                'value' => $sln_epn_tare_weight_value,
                'custom_attributes' => array('step' => '1', 'min' => '0'),
            )
        );
    }

    if(esc_attr( get_option('sew_epn_size_enabled', 1) ) == 1 ) {
        //EPN Size
        $sln_epn_size_value = get_post_meta($variation->ID, 'sln_epn_size', true);
        woocommerce_wp_text_input(
            array(
                'id' => 'sln_epn_size[' . $variation->ID . ']',
                'label' => __('EPN Size'),
                'type' => 'text',
                'class' => 'sln_text_field sln_epn_size',
                'desc_tip' => 'true',
                'description' => __('EposNow Size'),
                'default' => '',
                'value' => $sln_epn_size_value,
            )
        );
    }

}

add_action( 'woocommerce_save_product_variation', 'sln_save_variation_settings_fields', 10, 2 );

function sln_save_variation_settings_fields( $post_id ) {

    $variation = wc_get_product($post_id);
    //check that a variation is returned
    if($variation) {
	    $parent_id = $variation->get_parent_id();

	    //sew_log_data('variations', '$parent_id : ' . $parent_id);
	    $sln_product_type = $_POST['sln_product_type'][ $post_id ];
	    if ( $sln_product_type == 'measured' || $sln_product_type == 'weighted'){
		    $sln_wc_measurement_unit                   = $_POST['sln_wc_measurement_unit'][ $post_id ];
		    $sln_sale_price_measurement_scheme_item_id = $_POST['sln_sale_price_measurement_scheme_item_id'][ $post_id ];

		    //sew_log_data('variations', $_POST['sln_sale_price_measurement_scheme_item_id'], true);
		    //sew_log_data('variations', '$sln_wc_measurement_unit : '.$sln_wc_measurement_unit);
		    //sew_log_data('variations', '$sln_sale_price_measurement_scheme_item_id : '.$sln_sale_price_measurement_scheme_item_id);
		    $valid = checkEpnWcMeasurementUnits( $sln_wc_measurement_unit, $sln_sale_price_measurement_scheme_item_id );
		    if ( ! $valid ) {
			    $parent_error = get_post_meta( $parent_id, 'slynk_error', true );
			    if ( ! in_array( $post_id, $parent_error ) ) {
				    if ( ! empty( $parent_error ) ) {
					    $parent_error[] = $post_id;
				    } else {
					    $parent_error = array( $post_id );
				    }

				    //sew_log_data('variations', 'In valid : ' . $parent_id);
				    //sew_log_data('variations', $parent_error, true);
				    update_post_meta( $parent_id, 'slynk_error', $parent_error );
			    }

			    return false;
		    }
	    }

	    if ( ! empty( $sln_product_type ) ) {
		    update_post_meta( $post_id, 'sln_product_type', $sln_product_type );
	    }

        if(isset($_POST['sln_product_type'][ $post_id ])) {
            if ($sln_product_type == 'standard') {
                $sln_sale_price_measurement_scheme_item_id = '';
                $sln_sale_price_measurement_unit_volume = '';
            } else {
                $sln_sale_price_measurement_scheme_item_id = $_POST['sln_sale_price_measurement_scheme_item_id'][$post_id];
                $sln_sale_price_measurement_unit_volume = $_POST['sln_sale_price_measurement_unit_volume'][$post_id];
            }
            if (isset($sln_sale_price_measurement_scheme_item_id)) {
                update_post_meta($post_id, 'sln_sale_price_measurement_scheme_item_id', esc_attr($sln_sale_price_measurement_scheme_item_id));
            }

            if (isset($sln_sale_price_measurement_unit_volume)) {
                update_post_meta($post_id, 'sln_sale_price_measurement_unit_volume', esc_attr($sln_sale_price_measurement_unit_volume));
            }

            if ( $sln_product_type == 'standard' ) {
                update_post_meta( $post_id, 'sln_wc_measurement_unit', "" );
            } else {
                $sln_wc_measurement_unit = $_POST['sln_wc_measurement_unit'][ $post_id ];
                if ( ! empty( $sln_wc_measurement_unit ) ) {
                    update_post_meta( $post_id, 'sln_wc_measurement_unit', esc_attr( $sln_wc_measurement_unit ) );
                } else {
                    update_post_meta( $post_id, 'sln_wc_measurement_unit', "" );
                }
            }
        }


        // Slynk Ignore stock update check box value save
        $sln_ignore_stock_update = 0;
        if ( isset($_POST['sln_ignore_stock_update'][ $post_id ])) {
            $sln_ignore_stock_update   = ( $_POST['sln_ignore_stock_update'][ $post_id ] == 'yes' ) ? 1 : 0;
        }
        update_post_meta( $post_id, 'sln_ignore_stock_update', esc_attr($sln_ignore_stock_update) );

        // Slynk Ignore product update check box value save
        $sln_ignore_product_update = 0;
        if ( isset($_POST['sln_ignore_product_update'][ $post_id ])) {
            $sln_ignore_product_update   = ( $_POST['sln_ignore_product_update'][ $post_id ] == 'yes' ) ? 1 : 0;
        }
        update_post_meta( $post_id, 'sln_ignore_product_update', esc_attr($sln_ignore_product_update) );

        /*
        * New Added Custom fields by slynk
        * */
        if(isset($_POST['sln_epn_cost_price'][ $post_id ])) {
            update_post_meta($post_id, 'sln_epn_cost_price', esc_attr($_POST['sln_epn_cost_price'][$post_id]));
        }
        if(isset($_POST['sln_epn_eat_out_price'][ $post_id ])) {
            update_post_meta($post_id, 'sln_epn_eat_out_price', esc_attr($_POST['sln_epn_eat_out_price'][$post_id]));
        }
        if(isset($_POST['sln_epn_rrp_price'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_rrp_price', esc_attr($_POST['sln_epn_rrp_price'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_barcode'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_barcode', esc_attr($_POST['sln_epn_barcode'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_order_code'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_order_code', esc_attr($_POST['sln_epn_order_code'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_article_code'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_article_code', esc_attr($_POST['sln_epn_article_code'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_brand_id'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_brand_id', esc_attr($_POST['sln_epn_brand_id'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_supplier_id'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_supplier_id', esc_attr($_POST['sln_epn_supplier_id'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_tare_weight'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_tare_weight', esc_attr($_POST['sln_epn_tare_weight'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_size'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_size', esc_attr($_POST['sln_epn_size'][ $post_id ]) );
        }
        if(isset($_POST['sln_epn_description'][ $post_id ])) {
            update_post_meta( $post_id, 'sln_epn_description', $_POST['sln_epn_description'][ $post_id ] );
        }


	    /* if($sln_product_type == 'standard'){
			 $sln_cost_price_measurement_scheme_item_id = '';
		 }else{
			 $sln_cost_price_measurement_scheme_item_id = $_POST['sln_cost_price_measurement_scheme_item_id'][ $post_id ];
		 }
		 if( isset( $sln_cost_price_measurement_scheme_item_id ) ) {
			 update_post_meta( $post_id, 'sln_cost_price_measurement_scheme_item_id', esc_attr( $sln_cost_price_measurement_scheme_item_id ) );
		 }

		 if($sln_product_type == 'standard'){
			 $sln_cost_price_measurement_unit_volume = '';
		 }else{
			 $sln_cost_price_measurement_unit_volume = $_POST['sln_cost_price_measurement_unit_volume'][ $post_id ];
		 }
		 if( isset( $sln_cost_price_measurement_unit_volume ) ) {
			 update_post_meta( $post_id, 'sln_cost_price_measurement_unit_volume', esc_attr( $sln_cost_price_measurement_unit_volume ) );
		 }*/

        if(isset($_POST['sln_unit_multiplier'][ $post_id ])) {
            $sln_unit_multiplier = $_POST['sln_unit_multiplier'][$post_id];
            if (!empty($sln_unit_multiplier)) {
                update_post_meta($post_id, 'sln_unit_multiplier', esc_attr($sln_unit_multiplier));
            }
        }

        if(isset($_POST['sln_epn_title'][ $post_id ])) {
            update_post_meta($post_id, 'sln_epn_title', $_POST['sln_epn_title'][$post_id]);
        }

    }
}

function checkEpnWcMeasurementUnits($wc_unit, $epn_unit){
    $length = [1, 2, 18];
    $volume = [5, 6, 7];
    $area = [8, 9];
    $weight_uk = [3, 4];
    $weight_us = [28, 29];
    if((in_array($wc_unit, $length) && in_array($epn_unit, $length))
        || (in_array($wc_unit, $volume) && in_array($epn_unit, $volume))
        || (in_array($wc_unit, $area) && in_array($epn_unit, $area) )
        || (in_array($wc_unit, $weight_uk) && in_array($epn_unit, $weight_uk) )
        || (in_array($wc_unit, $weight_us) && in_array($epn_unit, $weight_us) )
        || ($wc_unit == $epn_unit)
    ){
        return 1;
    }
    return 0;
}
add_action( 'admin_notices', 'slynk_validation_admin_notices');
function slynk_validation_admin_notices() {

    if(!isset($_GET['post']) || !is_admin()) return;

    $product_id = $_GET['post'];
    $post_type = get_post_type();
    if($product_id && $post_type == 'product'){
        $errors = get_post_meta($product_id, 'slynk_error', true);
        if(empty($errors) || $errors == '') return false;

        $error = '';
        /*if(in_array($product_id, $errors)){*/
            $message = "Unable to save the product as the measurement units selected are not compatible. Please select compatible measurement units. For example cm/m, yd/yd, ml/cl/l, g/kg, lb/per 1000lb";
       /* }else {
            $message = "Having error in product measurement to save";
            $error = '<ul>';
            foreach ($errors as $pid) {
                $error .= '<li>' . get_the_title($pid) . '</li>';
            }
            $error .= '</ul>';
        }*/
        ?>
        <div class="error">
            <p><?php esc_html_e( $message, 'slynk' ); ?></p>
        </div>

        <?php
        delete_post_meta($product_id, 'slynk_error');
    }
}


add_action( 'woocommerce_variable_product_bulk_edit_actions', 'sln_variable_bulk_edit_actions' );
function sln_variable_bulk_edit_actions() {
    global $post;
    if(esc_attr( get_option('sew_ignore_stock_update_enabled', 1) ) == 1 )
    {
    ?>
    <optgroup label="<?php esc_attr_e( 'Slynk Ignore stock update', 'slynk' ); ?>">
        <option value="variable_sln_ignore_stock_enable"><?php esc_html_e( 'Toggle "Enabled"', 'slynk' ); ?></option>
        <option value="variable_sln_ignore_stock_disable"><?php esc_html_e( 'Toggle "Disabled"', 'slynk' ); ?></option>
    </optgroup>
        <?php }
    if(esc_attr( get_option('sew_ignore_product_update_enabled', 1) ) == 1 )
    {
    ?>
    <optgroup label="<?php esc_attr_e( 'Slynk Ignore Product update', 'slynk' ); ?>">
        <option value="variable_sln_ignore_product_enable"><?php esc_html_e( 'Toggle "Enabled"', 'slynk' ); ?></option>
        <option value="variable_sln_ignore_product_disable"><?php esc_html_e( 'Toggle "Disabled"', 'slynk' ); ?></option>
    </optgroup>
    <?php } ?>
    <optgroup label="<?php esc_attr_e( 'Slynk', 'slynk' ); ?>">
        <?php
        if(esc_attr( get_option('sew_unit_multiplier_enabled', 1) ) == 1 ) {
            ?>
            <option value="variable_sln_unit_multiplier"><?php esc_html_e( 'WC Unit Multiplier', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_product_category_master_enabled', 1) ) == 1 ) {
            ?>
            <option value="variable_sln_product_category_master"><?php esc_html_e( 'WC Master Category', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_cost_price_enabled', 1) ) == 1 ) {
            //EPN Cost Price
            ?>
            <option value="variable_sln_epn_cost_price"><?php esc_html_e( 'EPN Cost Price (ex tax)', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_eat_out_price_enabled', 1) ) == 1 ) {
            //EPN Eat out price
            ?>
            <option value="variable_sln_epn_eat_out_price"><?php esc_html_e( 'EPN Eat out price', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_rrp_price_enabled', 1) ) == 1 ) {
            //EPN RRP price
            ?>
            <option value="variable_sln_epn_rrp_price"><?php esc_html_e( 'EPN RRP price', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_order_code_enabled', 1) ) == 1 ) {
            //EPN Order code
            ?>
            <option value="variable_sln_epn_order_code"><?php esc_html_e( 'EPN Order code', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_article_code_enabled', 1) ) == 1 ) {
            //EPN Article code
            ?>
            <option value="variable_sln_epn_article_code"><?php esc_html_e( 'EPN Article code', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_brand_id_enabled', 1) ) == 1 ) {
            //EPN Brand ID
            ?>
            <option value="variable_sln_epn_brand_id"><?php esc_html_e( 'EPN Brand ID', 'slynk' ); ?></option>
            <?php
        }
        if(esc_attr( get_option('sew_epn_supplier_id_enabled', 1) ) == 1 ) {
            //EPN Supplier ID
            ?>
            <option value="variable_sln_epn_supplier_id"><?php esc_html_e( 'EPN Supplier ID', 'slynk' ); ?></option>
            <?php
        }
        ?>
    </optgroup>
    <?php
}

add_action('wp_ajax_sln_woocommerce_bulk_edit_variations', 'sln_woocommerce_bulk_edit_variations');
function sln_woocommerce_bulk_edit_variations()
{
    ob_start();

    check_ajax_referer( 'bulk-edit-variations', 'security' );

    // Check permissions again and make sure we have what we need.
    if ( ! current_user_can( 'edit_products' ) || empty( $_POST['product_id'] ) || empty( $_POST['bulk_action'] ) ) {
        wp_die( -1 );
    }

    $product_id  = absint( $_POST['product_id'] );
    $bulk_action = wc_clean( wp_unslash( $_POST['bulk_action'] ) );
    $data        = ! empty( $_POST['data'] ) ? wc_clean( wp_unslash( $_POST['data'] ) ) : array();
    $variations  = array();

    if ( apply_filters( 'woocommerce_bulk_edit_variations_need_children', true ) ) {
        $variations = get_posts(
            array(
                'post_parent'    => $product_id,
                'posts_per_page' => -1,
                'post_type'      => 'product_variation',
                'fields'         => 'ids',
                'post_status'    => array( 'publish', 'private' ),
            )
        );
    }

    if($bulk_action == 'variable_sln_ignore_stock_enable' || $bulk_action == 'variable_sln_ignore_stock_disable'){
        $key = "sln_ignore_stock_update";
        sln_toggle_save_func( $variations, $data['value'], $key );
    }elseif($bulk_action == 'variable_sln_ignore_product_enable' || $bulk_action == 'variable_sln_ignore_product_disable'){
        $key = "sln_ignore_product_update";
        sln_toggle_save_func( $variations, $data['value'], $key );
    }else{
        $key = str_replace('variable_', '', $bulk_action);
        sln_value_save_func( $variations, $data['value'], $key );
    }


    WC_Product_Variable::sync( $product_id );
    wc_delete_product_transients( $product_id );
    wp_die();
}

function sln_toggle_save_func( $variations, $value, $key ){
    foreach ( $variations as $variation_id ) {
        $sln_toggle_update = $value == 'true' ? 1 : 0;
        update_post_meta( $variation_id, $key, esc_attr($sln_toggle_update) );
    }
}

function sln_value_save_func( $variations, $value, $key ){
    foreach ( $variations as $variation_id ) {
        $sln_toggle_update = $value ?? "";
        update_post_meta( $variation_id, $key, esc_attr($sln_toggle_update) );
    }
}

/*
 * Add hook to at end of woocommerce bulk edit action
 * this will add our custom fields in bulk edit
*/
add_action( 'woocommerce_product_bulk_edit_end', 'product_bulk_edit_fields', 10);
/**
 * @function : sln_woocommerce_product_bulk_edit_fields
 * @description : Add new fields in bulk/quick edit
 **/
function product_bulk_edit_fields()
{
    if(esc_attr( get_option('sew_ignore_stock_update_enabled', 1) ) == 1 )
     {
         ?>
    <div class="inline-edit-group sln_edit_container">
        <label class="sln_ignore_stock_update">
            <span class="title"><?php esc_html_e( 'Ignore stock update', 'slynk' ); ?></span>
            <span class="input-text-wrap">
                <select class="sln_ignore_stock_update" name="sln_ignore_stock_update" >
                      <option value="">— No change —</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </span>
        </label>
    </div>
    <?php
    }
    if(esc_attr( get_option('sew_ignore_product_update_enabled', 1) ) == 1 )
    {?>
    <div class="inline-edit-group sln_edit_container">
        <label class="sln_ignore_product_update">
            <span class="title"><?php esc_html_e( 'Ignore product update', 'slynk' ); ?></span>
            <span class="input-text-wrap">
                <select class="sln_ignore_product_update" name="sln_ignore_product_update" >
                    <option value="">— No change —</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </span>
        </label>
    </div>
    <?php
    }
    /*if(esc_attr( get_option('sew_epn_title_enabled', 1) ) == 1 )
    {
        ?>
    <div class="inline-edit-group sln_edit_container">
        <label class="sln_epn_title">
            <span class="title"><?php esc_html_e( 'EPN Product Name', 'slynk' ); ?></span>
            <span class="input-text-wrap">
                <input type="text" name="sln_epn_title" class="text sln_text_field sln_epn_title" alt="<?php esc_attr_e( 'EPN Product Name', 'slynk' ); ?>" value="">
            </span>
        </label>
    </div>
        <?php }
    if(esc_attr( get_option('sew_epn_description_enabled', 1) ) == 1 ) {
        */?><!--
        <div class="inline-edit-group sln_edit_container sln_epn_description_field">
            <label class="sln_epn_description">
                <span class="title"><?php /*esc_html_e( 'EPN Description', 'slynk' ); */?></span>
                <span class="input-text-wrap">
                    <textarea maxlength="3999" name="sln_epn_description" class="text sln_textarea_field sln_half_field sln_epn_description" placeholder="<?php /*esc_attr_e( '- No Change -', 'slynk' ); */?>"></textarea>
            </span>
            </label>
        </div>
        --><?php
/*    }*/
    if(esc_attr( get_option('sew_unit_multiplier_enabled', 1) ) == 1 ) {
        ?>
        <div class="inline-edit-group sln_edit_container sln_product_type_field">
            <label class="sln_product_type">
                <span class="title"><?php esc_html_e( 'WC Unit Multiplier', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                   <input type="number" step="1" class="text sln_unit_multiplier" name="sln_unit_multiplier" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
<?php
    }
    if(esc_attr( get_option('sew_product_category_master_enabled', 1) ) == 1 ) {
        ?>
        <div class="inline-edit-group sln_edit_container">
            <label class="sln_product_category_master">
                <span class="title"><?php esc_html_e( 'WC Master Category', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                   <?php echo sew_get_woo_categories(); ?>
                </span>
            </label>
        </div>
<?php
    }
    if(esc_attr( get_option('sew_epn_cost_price_enabled', 1) ) == 1 ) {
        //EPN Cost Price
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_cost_price_field">
            <label class="sln_epn_cost_price">
                <span class="title"><?php esc_html_e( 'EPN Cost Price (ex tax)', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                   <input type="number" step="any" min="0" name="sln_epn_cost_price" class="number sln_numeric_field sln_epn_cost_price" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
<?php
    }
    if(esc_attr( get_option('sew_epn_eat_out_price_enabled', 1) ) == 1 ) {
        //EPN Eat out price
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_eat_out_price_field">
            <label class="sln_epn_eat_out_price">
                <span class="title"><?php esc_html_e( 'EPN Eat out price', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                  <input type="number" step="any" min="0" name="sln_epn_eat_out_price" class="number sln_numeric_field sln_epn_eat_out_price" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
        <?php
    }
    if(esc_attr( get_option('sew_epn_rrp_price_enabled', 1) ) == 1 ) {
        //EPN RRP price
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_rrp_price_field">
            <label class="sln_epn_rrp_price">
                <span class="title"><?php esc_html_e( 'EPN RRP price', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                  <input type="number" step="any" min="0" name="sln_epn_rrp_price" class="number sln_numeric_field sln_epn_rrp_price" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
        <?php
    }
 /*   if(esc_attr( get_option('sew_epn_barcode_enabled', 1) ) == 1 ) {
        //EPN Barcode
        */?><!--
        <div class="inline-edit-group sln_edit_container sln_epn_barcode_field">
            <label class="sln_epn_barcode">
                <span class="title"><?php /*esc_html_e( 'EPN Barcode', 'slynk' ); */?></span>
                <span class="input-text-wrap">
                  <input type="text" name="sln_epn_barcode" class="text sln_text_field sln_epn_barcode" placeholder="- No Change -">
                </span>
            </label>
        </div>
        --><?php
/*    }*/
    if(esc_attr( get_option('sew_epn_order_code_enabled', 1) ) == 1 ) {
        //EPN Order code
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_order_code_field">
            <label class="sln_epn_order_code">
                <span class="title"><?php esc_html_e( 'EPN Order code', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                  <input type="text" name="sln_epn_order_code" class="text sln_text_field sln_epn_order_code" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
        <?php
    }
    if(esc_attr( get_option('sew_epn_article_code_enabled', 1) ) == 1 ) {
        //EPN Article code
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_article_code_field">
            <label class="sln_epn_article_code">
                <span class="title"><?php esc_html_e( 'EPN Article code', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                  <input type="text" name="sln_epn_article_code" class="text sln_text_field sln_epn_article_code" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
        <?php
    }
    if(esc_attr( get_option('sew_epn_brand_id_enabled', 1) ) == 1 ) {
        //EPN Brand ID
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_brand_id_field">
            <label class="sln_epn_brand_id">
                <span class="title"><?php esc_html_e( 'EPN Brand ID', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                  <input type="number" step ="1" min="0" name="sln_epn_brand_id" class="number sln_int_field sln_epn_brand_id" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
        <?php
    }

    if(esc_attr( get_option('sew_epn_supplier_id_enabled', 1) ) == 1 ) {
        //EPN Supplier ID
        ?>
        <div class="inline-edit-group sln_edit_container sln_epn_supplier_id_field">
            <label class="sln_epn_supplier_id">
                <span class="title"><?php esc_html_e( 'EPN Supplier ID', 'slynk' ); ?></span>
                <span class="input-text-wrap">
                  <input type="number" step ="1" min="0" name="sln_epn_supplier_id" class="number sln_int_field sln_epn_supplier_id" placeholder="<?php esc_attr_e( '- No Change -', 'slynk' ); ?>">
                </span>
            </label>
        </div>
        <?php
    }

   /* if(esc_attr( get_option('sew_epn_tare_weight_enabled', 1) ) == 1 ) {
        //EPN Tare Weight
        */?><!--
        <div class="inline-edit-group sln_edit_container sln_epn_tare_weight_field">
            <label class="sln_epn_tare_weight">
                <span class="title"><?php /*esc_html_e( 'EPN Tare Weight', 'slynk' ); */?></span>
                <span class="input-text-wrap">
                  <input type="number" step ="1" min="0" name="sln_epn_tare_weight" class="number sln_int_field sln_epn_tare_weight" placeholder="<?php /*esc_attr_e( '- No Change -', 'slynk' ); */?>">
                </span>
            </label>
        </div>
        <?php
/*    }

    if(esc_attr( get_option('sew_epn_size_enabled', 1) ) == 1 ) {
        //EPN Size
        */?>
        <div class="inline-edit-group sln_edit_container sln_epn_size_field">
            <label class="sln_epn_size">
                <span class="title"><?php /*esc_html_e( 'EPN Size', 'slynk' ); */?></span>
                <span class="input-text-wrap">
                    <input type="text" name="sln_epn_size" class="text sln_text_field sln_epn_size" placeholder="<?php /*esc_attr_e( '- No Change -', 'slynk' ); */?>">
                </span>
            </label>
        </div>
        --><?php
/*    }*/
}

/*
 * Save Hooks
 * */
add_action( 'woocommerce_product_bulk_edit_save', 'sew_product_bulk_save_options', 10, 1 );
function sew_product_bulk_save_options($product)
{
    //global $sew_plugin_settings;
    sew_log_data('bulk_edit_product', $product);
    sew_log_data('bulk_edit_product', $_REQUEST, true);

    if (isset($_REQUEST['sln_ignore_stock_update']) && $_REQUEST['sln_ignore_stock_update'] !== '' ) {
        $sln_ignore_stock_update = ($_REQUEST['sln_ignore_stock_update'] == 'yes') ? 1 : 0;
        $product->update_meta_data( 'sln_ignore_stock_update', $sln_ignore_stock_update );
    }

    if (isset($_REQUEST['sln_ignore_product_update']) && $_REQUEST['sln_ignore_product_update'] !== '' ) {
        $sln_ignore_product_update = ($_REQUEST['sln_ignore_product_update'] == 'yes') ? 1 : 0;
        $product->update_meta_data( 'sln_ignore_product_update', $sln_ignore_product_update );
    }

    if(isset($_REQUEST['sln_epn_cost_price']) && $_REQUEST['sln_epn_cost_price'] !== '' ) {
        $product->update_meta_data( 'sln_epn_cost_price', esc_attr($_REQUEST['sln_epn_cost_price']) );
    }
    if(isset($_REQUEST['sln_epn_eat_out_price']) && $_REQUEST['sln_epn_eat_out_price'] !== '' ) {
        $product->update_meta_data( 'sln_epn_eat_out_price', esc_attr($_REQUEST['sln_epn_eat_out_price']) );
    }
    if(isset($_REQUEST['sln_epn_rrp_price']) && $_REQUEST['sln_epn_rrp_price'] !== '' ) {
        $product->update_meta_data( 'sln_epn_rrp_price', esc_attr($_REQUEST['sln_epn_rrp_price']) );
    }
    if(isset($_REQUEST['sln_epn_barcode']) && $_REQUEST['sln_epn_barcode'] !== '' ) {
        $product->update_meta_data( 'sln_epn_barcode', esc_attr($_REQUEST['sln_epn_barcode']) );
    }
    if(isset($_REQUEST['sln_epn_order_code']) && $_REQUEST['sln_epn_order_code'] !== '' ) {
        $product->update_meta_data( 'sln_epn_order_code', esc_attr($_REQUEST['sln_epn_order_code']) );
    }
    if(isset($_REQUEST['sln_epn_article_code']) && $_REQUEST['sln_epn_article_code'] !== '' ) {
        $product->update_meta_data( 'sln_epn_article_code', esc_attr($_REQUEST['sln_epn_article_code']) );
    }
    if(isset($_REQUEST['sln_epn_brand_id']) && $_REQUEST['sln_epn_brand_id'] !== '' ) {
        $product->update_meta_data( 'sln_epn_brand_id', esc_attr($_REQUEST['sln_epn_brand_id']) );
    }
    if(isset($_REQUEST['sln_epn_supplier_id']) && $_REQUEST['sln_epn_supplier_id'] !== '' ) {
        $product->update_meta_data( 'sln_epn_supplier_id', esc_attr($_REQUEST['sln_epn_supplier_id']) );
    }
    if(isset($_REQUEST['sln_epn_tare_weight']) && $_REQUEST['sln_epn_tare_weight'] !== '' ) {
        $product->update_meta_data( 'sln_epn_tare_weight', esc_attr($_REQUEST['sln_epn_tare_weight']) );
    }
    if(isset($_REQUEST['sln_epn_size']) && $_REQUEST['sln_epn_size'] !== '' ) {
        $product->update_meta_data( 'sln_epn_size', esc_attr($_REQUEST['sln_epn_size']) );
    }
    if(isset($_REQUEST['sln_epn_description']) && $_REQUEST['sln_epn_description'] !== '' ) {
        $product->update_meta_data( 'sln_epn_description', $_REQUEST['sln_epn_description'] );
    }
    if(isset($_REQUEST['sln_epn_title']) && $_REQUEST['sln_epn_title'] !== '' ) {
        $product->update_meta_data( 'sln_epn_title', $_REQUEST['sln_epn_title'] );
    }

    if(isset($_REQUEST['sln_unit_multiplier']) && $_REQUEST['sln_unit_multiplier'] !== '' ) {
        $sln_unit_multiplier = $_REQUEST['sln_unit_multiplier'];
        if (!empty($sln_unit_multiplier)) {
            $product->update_meta_data( 'sln_unit_multiplier', esc_attr($_REQUEST['sln_unit_multiplier']) );
        }
    }

    if(isset($_REQUEST['sln_product_category_master']) && $_REQUEST['sln_product_category_master'] !== '' ) {
        $product->update_meta_data( 'sln_product_category_master', esc_attr($_REQUEST['sln_product_category_master']) );
    }

    $product->save();
}