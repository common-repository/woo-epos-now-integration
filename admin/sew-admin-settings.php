<?php
global $wpdb;
global $sew_plugin_settings;
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-style', plugins_url('css/jquery-ui/jquery-ui-smoothness.css', SLYNK_EW_PLUGIN_PATH));

//Options
$sew_valid_order_statuses = $sew_plugin_settings['valid_order_statuses'];
$sew_api_key = $sew_plugin_settings['authentication']['key'];
$sew_api_secret = $sew_plugin_settings['authentication']['secret'];

$wc_get_order_statuses = wc_get_order_statuses();
$wc_get_order_statuses = sew_filter_wc_order_status_keys($wc_get_order_statuses);

foreach ($wc_get_order_statuses as $order_key => $order_title){
    $checked = in_array( $order_key, $sew_valid_order_statuses) ? "selected" : "";
    $order_status_options[] = "<option {$checked} value=\"{$order_key}\">{$order_title}</option>";
}

/* Error */
$error = [];
if(!$sew_api_key) $error[] = "API key is empty";
if(!$sew_api_secret) $error[] = "API Secret is empty";


$sew_cron_interval_options = array('five_minutes' =>'Every 5 Minutes',
    'ten_minutes' =>'Every 10 Minutes',
    'fifteen_minutes' =>'Every 15 Minutes',
    'thirty_minutes' =>'Every 30 Minutes',
    'fourty_five_minutes' =>'Every 45 Minutes',
    'hourly' =>'Hourly');
foreach ($sew_cron_interval_options as $option_key => $option_val){
    $checked = ($sew_plugin_settings['cron_interval'] == $option_key) ? "selected" : "";
    $cron_interval_options[] = "<option {$checked} value=\"{$option_key}\">{$option_val}</option>";
}


?>

<script>
    jQuery(document).ready(function() {
        jQuery('#sew_sync_orders_after').datepicker({
            dateFormat : 'dd-mm-yy',
            changeMonth: true,
            changeYear: true,
            yearRange: "2018:2030",
        });
    });

    jQuery(document).ready(function() {
        jQuery('#sew_sync_refund_orders_after').datepicker({
            dateFormat : 'dd-mm-yy',
            changeMonth: true,
            changeYear: true,
            yearRange: "2018:2030",
        });
    });


</script>


<?php

$show_dev_options = false;
if(!empty($_GET['sew_show_dev_options']) && filter_var($_GET['sew_show_dev_options'], FILTER_SANITIZE_NUMBER_INT) == 1) {
    $show_dev_options = true;
}

if(!empty($_GET['page']) && $_GET['page'] == 'sew-settings' && !empty($_GET['settings-updated']) && $_GET['settings-updated'] == 'true' ){
    //settings updated, check webhooks still valid
    sew_check_webhooks();
}

?>

<h1>Slynk Epos Now WooCommerce Integration Settings</h1>

<form method="post" action="options.php">
    <?php settings_fields('sew_settings');
    if(!empty($error)){
        $error_string = "<h3>Settings Errors</h3><p>".implode("</p><p>", $error)."</p>";
        echo "<div class=\"notice notice-error\">{$error_string}</div>";
    }?>
    <table class="form-table slynk-epn-table slynk-bg-sea">
        <tr class="sln-table-title">
            <th colspan="2">
                <h2>Credentials</h2>
                <div id=""><p>Required to connect to the Slynk service and require a paid subscription.</p></div>
            </th>
        </tr>
        <tr valign="top"><th scope="row">Slynk API Key</th>
            <td><input type="text" name="sew_api_key" size="60" value="<?php echo esc_attr( get_option('sew_api_key') ); ?>" /></td>
        </tr>

        <tr valign="top"><th scope="row">Slynk API Secret</th>
            <td><input type="text" name="sew_api_secret" size="60" value="<?php echo esc_attr( get_option('sew_api_secret') ); ?>" /></td>
        </tr>
    </table>

    <table class="form-table slynk-epn-table">
        <tr class="sln-table-title">
            <th colspan="3">
                <h2>Settings</h2>
                <div id=""><p>Integration settings.<br>Please note that if these settings are changed, historic orders that match the filters will also be synced. You can set the date for orders to sync from to manage this.</p></div>
            </th>
        </tr>

        <tr valign="top"><td><h2>Stock Sync</h2></td></tr>

        <tr valign="top"><th scope="row">Full Stock Sync Batch Size</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">The number of stock updates to be processed at a time during the full stock sync</span></span></td>
            <td><input type="number" name="sew_stock_batch_size" size="15" value="<?php echo esc_attr( get_option('sew_stock_batch_size') ); ?>" /></td>
        </tr>

        <tr valign="top">
            <th scope="row">Clear cache on full stock sync</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Only enable this if WP is caching the wp_options and not returning correct statuses for the full stock sync, This clears the wp object cache before the full stock sync is processed.</span></span></td>
            <?php $sew_cache_clear_option = esc_attr( get_option('sew_cache_clear_enabled') );
            $checked = ($sew_cache_clear_option ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_cache_clear_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>

        </tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>

            <th scope="row">WooCommerce Master (Stock)</th>
            <td></td>
            <?php $sew_wc_master_option = esc_attr( get_option('sew_wc_master') );
            $checked = ($sew_wc_master_option ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_wc_master" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
                <br><br><i>Only enable this if WooCommerce is the master for the stock for the integration.</i>
            </td>
        </tr>

        <tr valign="top"><td><h2>Order Sync</h2></td></tr>

        <tr valign="top">
            <th scope="row">Disable WC>EPN Order Sync</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Disable the order sync. Useful while products are still being linked for the first time.</span></span></td>
            <?php $sew_disable_order_sync = esc_attr( get_option('sew_disable_order_sync') );
            $checked = ($sew_disable_order_sync ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_disable_order_sync" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Disable WC>EPN Refund Order Sync</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Disable the refund order sync.</span></span></td>
            <?php $sew_disable_refund_order_sync = esc_attr( get_option('sew_disable_refund_order_sync') );
            $checked = ($sew_disable_refund_order_sync ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_disable_refund_order_sync" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top"><th scope="row">Sync Orders On/After</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">The date from which orders should be synced to EposNow based on the order filters</span></span></td>
            <td>
                <input type="text" id="sew_sync_orders_after" name="sew_sync_orders_after" size="15" readonly="readonly" style="background-color: #FFF;" value="<?php echo esc_attr( get_option('sew_sync_orders_after') ); ?>" /> <i>(dd-mm-yyyy)</i></td>
        </tr>
        <tr valign="top">
            <th scope="row">Order Statuses To Sync</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Select the order statuses that should be synced. By default this is set to processing and completed orders. We recommend not including pending, on-hold or cancelled orders.</span></span></td>
            <td><select name="sew_valid_order_statuses[]" multiple class="sew-select-field select2" required><?php echo implode('',$order_status_options);?></select></td>
        </tr>

        <tr valign="top"><th scope="row">Sync Refund Orders On/After</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">The date from which refund orders should be synced to EposNow based on the refund order filters</span></span></td>
            <td>
                <input type="text" id="sew_sync_refund_orders_after" name="sew_sync_refund_orders_after" size="15" readonly="readonly" style="background-color: #FFF;" value="<?php echo esc_attr( get_option('sew_sync_refund_orders_after') ); ?>" /> <i>(dd-mm-yyyy)</i></td>
        </tr>
        <tr valign="top"><th scope="row">Unsynced Orders Cron Interval</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">How often the cron to check for any unsynced orders runs. Order sync automatically, this is used as a fail safe.</span></span></td>
            <td>
                <select name="sew_cron_interval" id="sew_cron_interval">
                    <?php echo implode('',$cron_interval_options);?>
                </select>
            </td>
        </tr>

        <tr valign="top"><th scope="row">Unsynced orders cron days in past</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">When the unsynced order cron runs, how many days in the past should it check.</span></span></td>
            <td><input type="number" name="sew_unsynced_orders_refunds_days_in_past" size="60" value="<?php echo esc_attr( get_option('sew_unsynced_orders_refunds_days_in_past') ); ?>" /></td>
        </tr>

        <tr valign="top"><th scope="row">Exclude User Roles Filter</th>
            <td class="sew-tooltip-td" valign="top"><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Select the user roles for which orders should NOT be synced to EposNow. Leave blank to sync orders for all user roles.</span></span></td>
            <td><input type="number" name="sew_sync_user_role" size="60" value="<?php echo esc_attr( get_option('sew_sync_user_role') ); ?>" /><br><i>Leave blank to disable filtering by user role</i></td>
        </tr>
        <tr valign="top">
            <th scope="row">Add Full Product Data To Order</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Add additional product data to the order data before it is sent to Slynk. Not required except in special cases.</span></span></td>
            <?php $sew_full_product_data_orders = esc_attr( get_option('sew_full_product_data_orders') );
                $checked = ($sew_full_product_data_orders ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_full_product_data_orders" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>

            <th scope="row">Suppress Order Emails for Epos Now Orders</th>
            <td></td>
            <?php $sew_suppress_order_emails_eposnow_orders = esc_attr( get_option('sew_suppress_order_emails_eposnow_orders') );
            $checked = ($sew_suppress_order_emails_eposnow_orders ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_suppress_order_emails_eposnow_orders" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
                <br><br><i>Only enable this if WooCommerce is the master for the stock for the integration.</i><br><br><span style="text-decoration: underline">Suppressed emails</span><br>Admin: New Order, Cancelled Order, Failed Order<br>Customer: Processing Order, Completed Order, On Hold Order, Refunded Order, Cancelled Order, Invoice, Note </i>
            </td>
        </tr>

        <!-- <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?> -->
        <tr valign="top">
            <td><h2>Product Sync</h2></td>
        </tr>

        <tr valign="top">
            <th scope="row">Disable Product Sync</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Disable Product Sync.</span></span></td>
            <?php $sew_product_sync_disabled_option = esc_attr( get_option('sew_product_sync_disabled') );
            $checked = ($sew_product_sync_disabled_option == 1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_product_sync_disabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>

        </tr>

        <tr valign="top">
            <th scope="row">Allow draft status webhooks</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Allow product webhooks with draft status to be sent. This is recommended to be left OFF.</span></span></td>
            <?php $sew_product_sync_disabled_option = esc_attr( get_option('sew_product_sync_draft_status_enabled') );
            $checked = ($sew_product_sync_disabled_option == 1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_product_sync_draft_status_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>

        </tr>

<!--        <tr valign="top">
            <th scope="row">Product webhook delay</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">The delay before a webhook fires for a product CRUD event. Required as Woo fires a creates webhook as soon as you click on 'add variation' rather than when variation is first saved. Recommend to set to 180 seconds.</span></span></td>
            <td><input type="number" name="sew_product_webhook_delay" size="3" value="<?php /*echo esc_attr( get_option('sew_product_webhook_delay') ); */?>" /> seconds</td>

        </tr>-->
        <!-- META FIELDS STARTS -->
        <tr valign="top">
            <td>
                <h2>Meta Fields Settings</h2>
                <small>Meta fields hide/show options</small>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Ignore Stock Update</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">Ignore Stock Update.</span>
                </span>
            </td>
            <?php $sew_ignore_stock_update_enabled = esc_attr( get_option('sew_ignore_stock_update_enabled', 1) );
            $checked = ($sew_ignore_stock_update_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_ignore_stock_update_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Ignore Product Update</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">Ignore product update.</span>
                </span>
            </td>
            <?php $sew_ignore_product_update_enabled = esc_attr( get_option('sew_ignore_product_update_enabled', 1) );
            $checked = ($sew_ignore_product_update_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_ignore_product_update_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Product Title</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">Epos Now Product Title. If this is left blank, then the default WooCommerce product title will be used.</span>
                </span>
            </td>
            <?php $sew_epn_title_enabled = esc_attr( get_option('sew_epn_title_enabled', 1) );
            $checked = ($sew_epn_title_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_title_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Description</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Description.</span>
                </span>
            </td>
            <?php $sew_epn_description_enabled = esc_attr( get_option('sew_epn_description_enabled', 1) );
            $checked = ($sew_epn_description_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_description_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Product Type</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Product Type.</span>
                </span>
            </td>
            <?php $sew_product_type_enabled = esc_attr( get_option('sew_product_type_enabled', 1) );
            $checked = ($sew_product_type_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_product_type_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">WC Unit Multiplier</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Measurement Unit.</span>
                </span>
            </td>
            <?php $sew_unit_multiplier_enabled = esc_attr( get_option('sew_unit_multiplier_enabled', 1) );
            $checked = ($sew_unit_multiplier_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_unit_multiplier_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">WC Master Category</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">WC Master Category.</span>
                </span>
            </td>
            <?php $sew_product_category_master_enabled = esc_attr( get_option('sew_product_category_master_enabled', 1) );
            $checked = ($sew_product_category_master_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_product_category_master_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Cost Price (ex tax)</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Cost Price Excluding Tax.</span>
                </span>
            </td>
            <?php $sew_epn_cost_price_enabled = esc_attr( get_option('sew_epn_cost_price_enabled', 1) );
            $checked = ($sew_epn_cost_price_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_cost_price_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Eat Out Price</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Eat Out Price.</span>
                </span>
            </td>
            <?php $sew_epn_eat_out_price_enabled = esc_attr( get_option('sew_epn_eat_out_price_enabled', 1) );
            $checked = ($sew_epn_eat_out_price_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_eat_out_price_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN RRP price</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow recommended retail price.</span>
                </span>
            </td>
            <?php $sew_epn_rrp_price_enabled = esc_attr( get_option('sew_epn_rrp_price_enabled', 1) );
            $checked = ($sew_epn_rrp_price_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_rrp_price_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Barcode</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Barcode.</span>
                </span>
            </td>
            <?php $sew_epn_barcode_enabled = esc_attr( get_option('sew_epn_barcode_enabled', 1) );
            $checked = ($sew_epn_barcode_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_barcode_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Order code</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Order Code.</span>
                </span>
            </td>
            <?php $sew_epn_order_code_enabled = esc_attr( get_option('sew_epn_order_code_enabled', 1) );
            $checked = ($sew_epn_order_code_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_order_code_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Article code</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Article Code.</span>
                </span>
            </td>
            <?php $sew_epn_article_code_enabled = esc_attr( get_option('sew_epn_article_code_enabled', 1) );
            $checked = ($sew_epn_article_code_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_article_code_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Brand ID</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Brand ID.</span>
                </span>
            </td>
            <?php $sew_epn_brand_id_enabled = esc_attr( get_option('sew_epn_brand_id_enabled', 1) );
            $checked = ($sew_epn_brand_id_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_brand_id_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Supplier ID</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Supplier ID.</span>
                </span>
            </td>
            <?php $sew_epn_supplier_id_enabled = esc_attr( get_option('sew_epn_supplier_id_enabled', 1) );
            $checked = ($sew_epn_supplier_id_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_supplier_id_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Tare Weight</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Tare Weight.</span>
                </span>
            </td>
            <?php $sew_epn_tare_weight_enabled = esc_attr( get_option('sew_epn_tare_weight_enabled', 1) );
            $checked = ($sew_epn_tare_weight_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_tare_weight_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">EPN Size</th>
            <td>
                <span class="sew-tooltip-box">
                    <i class="sew-icon-default dashicons dashicons-info"></i>
                    <span class="sew-tooltip-text">EposNow Size.</span>
                </span>
            </td>
            <?php $sew_epn_size_enabled = esc_attr( get_option('sew_epn_size_enabled', 1) );
            $checked = ($sew_epn_size_enabled ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_epn_size_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <!-- META FIELDS ENDS -->

        <tr valign="top"><td><h2>General Settings</h2></td></tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>
            <th scope="row">v2 platform</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Connected to Slynk v2?</span></span></td>
            <?php $sew_platform_v2_enabled_option = esc_attr( get_option('sew_platform_v2') );
            $checked = ($sew_platform_v2_enabled_option == 1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_platform_v2" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>

        </tr>

        <tr valign="top">
            <th scope="row">Log Enabled</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Recommend leaving this disabled unless trying to debug.</span></span></td>
            <?php $sew_log_enabled_option = esc_attr( get_option('sew_log_enabled') );
            $checked = ($sew_log_enabled_option ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_log_enabled" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>

        </tr>

        <tr valign="top">
            <th scope="row">Log retention</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">How many days of logs should be kept</span></span></td>
            <td><input type="number" name="sew_log_retention_days" size="2" max="180" value="<?php echo esc_attr( get_option('sew_log_retention_days', 7) ); ?>" /> days</td>

        </tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>
            <th scope="row">Webhook User ID</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Which user ID should be used to create the slynk webhooks</span></span></td>
            <td><input type="number" name="sew_webhook_user_id" size="2" value="<?php echo esc_attr( get_option('sew_webhook_user_id', get_current_user_id()) ); ?>" /></td>
        </tr>

        <tr valign="top">
            <th scope="row">Add CORS headers</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Only required if you are seeing CORS errors when using the product linker.</span></span></td>
            <?php $sew_add_cors_headers = esc_attr( get_option('sew_add_cors_headers') );
            $checked = ($sew_add_cors_headers ==1)?'checked="checked"':'';
            ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_add_cors_headers" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>
            <th scope="row">Development Mode Enabled</th>
            <td></td>
		    <?php $sew_development_mode = $sew_plugin_settings['development_mode'];
		    $checked = ($sew_development_mode ==1)?'checked="checked"':'';
		    ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_development_mode" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>
            <th scope="row">API Base URL - Dev Mode</th>
            <td></td>
            <td><input type="text" name="sew_api_base_url_dev" size="60" value="<?php echo esc_attr( get_option('sew_api_base_url_dev') ); ?>" /></td>
        </tr>

        <tr valign="top" <?php if(!$show_dev_options){ ?>style="display:none;" <?php }?>>
            <th scope="row">Log ALL API requests</th>
            <td><span class="sew-tooltip-box"><i class="sew-icon-default dashicons dashicons-info"></i><span class="sew-tooltip-text">Logs ALL incoming API requests for ALL endpoints. Use for debugging for short periods of time only.</span></span></td>
            <?php $sew_log_all_api_requests = esc_attr( get_option('sew_log_all_api_requests') );
		    $checked = ($sew_log_all_api_requests ==1)?'checked="checked"':'';
		    ?>
            <td>
                <label class="toggle">
                    <input type="checkbox" name="sew_log_all_api_requests" size="60" <?php echo $checked;?> value="1" class="toggle-checkbox"/>
                    <div class="toggle-switch"></div>
                    <span class="toggle-label"></span>
                </label>
            </td>
        </tr>


    </table>
    <?php submit_button(); ?>
</form>