<?php
//setup the wp cron

add_filter( 'cron_schedules', 'sew_add_cron_interval' );

function sew_add_cron_interval( $schedules ) {
	$schedules['five_minutes'] = array(
		'interval' => 300,
		'display'  => esc_html__( 'Every 5 Minutes' ),
	);

    $schedules['ten_minutes'] = array(
        'interval' => 10*60,
        'display'  => esc_html__( 'Every 10 Minutes' ),
    );

    $schedules['fifteen_minutes'] = array(
        'interval' => 15*60,
        'display'  => esc_html__( 'Every 15 Minutes' ),
    );

    $schedules['thirty_minutes'] = array(
        'interval' => 30*60,
        'display'  => esc_html__( 'Every 30 Minutes' ),
    );

    $schedules['fourty_five_minutes'] = array(
        'interval' => 45*60,
        'display'  => esc_html__( 'Every 45 Minutes' ),
    );
	return $schedules;
}


function sew_setup_cron_jobs(){

    global $sew_plugin_settings;
    $cron_interval = $sew_plugin_settings['cron_interval'];


	if (! wp_next_scheduled ( 'sew_cron_order_sync' )) {
		wp_schedule_event(time(), $cron_interval, 'sew_cron_order_sync');
	}

	if (! wp_next_scheduled ( 'sew_cron_setup_hourly' )) {
		wp_schedule_event(time(), 'hourly', 'sew_cron_setup_hourly');
	}

	if (! wp_next_scheduled ( 'sew_cron_setup_twicedaily' )) {
		wp_schedule_event(time(), 'twicedaily', 'sew_cron_setup_twicedaily');
	}

	if (! wp_next_scheduled ( 'sew_cron_setup_daily' )) {
		wp_schedule_event(time(), 'daily', 'sew_cron_setup_daily');
	}
}

//remove all our cron jobs
function sew_remove_cron_jobs(){
	wp_clear_scheduled_hook('sew_cron_setup_5_mins');
	wp_clear_scheduled_hook('sew_cron_order_sync');
	wp_clear_scheduled_hook('sew_cron_setup_hourly');
	wp_clear_scheduled_hook('sew_cron_setup_twicedaily');
	wp_clear_scheduled_hook('sew_cron_setup_daily');
}

add_action('sew_cron_order_sync', 'sew_order_sync');
// do something every hour
function sew_order_sync() {

    if(esc_attr( get_option('sew_disable_order_sync') ) != 1) {
        sew_process_orders(null, true);
        if(esc_attr( get_option('sew_disable_refund_order_sync') ) != 1){
            sew_process_refund_orders(null, true);
        }
    }
}

// hourly cron
add_action('sew_cron_setup_hourly', 'sew_cron_hourly');
function sew_cron_hourly() {
	sew_check_webhooks();
}

//twice a day cron
add_action('sew_cron_setup_twicedaily', 'sew_cron_twicedaily');
function sew_cron_twicedaily() {
	sew_setup_cron_jobs();
}

//once a day cron
add_action('sew_cron_setup_daily', 'sew_cron_daily');
function sew_cron_daily() {
	sew_setup_options();
    sew_remove_log_files(); //Remove logs on x days
}