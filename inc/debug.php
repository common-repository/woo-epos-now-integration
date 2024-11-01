<?php
/*
Debugging code
*/

function sew_log_data($logfile = 'debug', $str='', $print_array = false){

	global $sew_plugin_settings;

	//check if we should log the data
	//always log the errors
	if($logfile != 'errors' && !$sew_plugin_settings['logging_enabled']){
		return;
	}

    $logs_dir = WP_CONTENT_DIR.'/slynk-logs';

    if (!is_dir($logs_dir)) {
	    wp_mkdir_p($logs_dir);
    }

    if($print_array){
	    $str = print_r($str, true);
    }

    file_put_contents($logs_dir.'/'.date("Y-m-d").'_'.$logfile.'.log', date("d-m-Y H:i:s").' : '. $str ."\r\n",FILE_APPEND);


}
