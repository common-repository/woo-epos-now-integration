<?php
$wpLogDirPathOld = str_replace('themes', '', get_theme_root()).'sew-logs/';
$wpLogDirPath = str_replace('themes', '', get_theme_root()).'slynk-logs/';
$url = admin_url( 'admin.php?page=slynk-epn-wc-logs');
$currentTab = (isset($_GET['tab']) && $_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'logs';
//if delete action found it deletes the log.
if(isset($_GET['dellog']) && $_GET['dellog'] !=''){
    $delLogFileSelected = sanitize_text_field($_GET['dellog']);
    $nonce = sanitize_text_field($_GET['_wpnonce']);

    if(!wp_verify_nonce( $nonce, 'delete_log-' . $delLogFileSelected)){ wp_safe_redirect( $url.'&tab=logs' ); die; }

    $deleteAction = sew_deleteLogFile($delLogFileSelected,$wpLogDirPath, $nonce);

    //delete all files from old log directory
    if($deleteAction){
        $deleteAction = sew_deleteLogFile('all',$wpLogDirPathOld, $nonce);
    }

    if($deleteAction){
        echo '<div class="notice notice-success is-dismissible"><p>Successfully deleted '.$delLogFileSelected.'!</p></div>';
        wp_safe_redirect( $url.'&tab=logs' );  die;
    }
}
$logFiles = array();
if (is_dir($wpLogDirPath)) {
    $logFiles = scandir($wpLogDirPath, 1);
}
$logFileSelected = $logFileContent = '';

if(isset($_POST['sew_log_file'])){
    if($_POST['sew_log_file'] !=''){
        $logFileSelected = sanitize_text_field($_POST['sew_log_file']);
    }
}

$list = '';
foreach ($logFiles as $logFile){
    $file_pointer = $wpLogDirPath.$logFile;
    if ($logFile != "." && $logFile != ".." && file_exists($file_pointer)) {
        if(!$logFileSelected) $logFileSelected = $logFile;
        $selected = ($logFileSelected == $logFile) ? 'selected' : '';
        $option[] = '<option value="'.$logFile.'" '.$selected.'>'.$logFile.'</option>';
        $list .= '<p>'.$logFile.'</p>';
    }
}
if(function_exists('sew_checkSetLogFile')){
    if($logFileSelected) {
        $logFileContent = sew_checkSetLogFile($logFileSelected, $wpLogDirPath);
    }
}else{
    echo 'Error "sew_checkSetLogFile" function is not exist';
}

$nonce = wp_create_nonce( 'delete_log-' . $logFileSelected );
$noncealldel = wp_create_nonce( 'delete_log-all');
$Tabs = array('logs' => 'Logs');
?>
<div class="wrap">
    <h1>Log <a class="page-title-action" onclick="return deleteConfirmFunction()" href="<?php echo admin_url( 'admin.php?page=slynk-epn-wc-logs&dellog=all&_wpnonce='.$noncealldel);?>">Delete all logs</a></h1>
    <nav class="nav-tab-wrapper sew-nav-tab-wrapper">
        <?php
        foreach ($Tabs as $tab => $tabname){
            $active = ($tab == $currentTab) ? ' nav-tab-active' : '';
            echo '<a href="'.$url.'&tab='.$tab.'" class="nav-tab'.$active.'">'.$tabname.'</a>';
        }
        ?>
    </nav>
    <div id="sew-log-viewer-select">
        <div class="alignleft">
            <h2>
                <?php echo $logFileSelected; ?><a class="page-title-action" onclick="return deleteConfirmFunction()" href="<?php echo admin_url( 'admin.php?page=slynk-epn-wc-logs&dellog='.$logFileSelected.'&_wpnonce='.$nonce);?>">Delete log</a>
            </h2>
        </div>
        <div class="alignright">
            <form action="<?php echo $url;?>" method="post">
                <select name="sew_log_file">
                    <?php
                    if(!empty($option)) {
	                    echo implode( ' ', $option );
                    }
                    ?>
                </select>
                <button type="submit" class="button" value="View">View</button>
            </form>
        </div>
        <div class="clear"></div>
    </div>
    <div id="sew-log-viewer">
        <pre><?php echo $logFileContent; ?></pre>
    </div>
</div>