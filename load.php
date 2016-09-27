<?php
echo '<pre>';
print_r(dirname(__FILE__));
print_r($_SERVER);
echo file_exists('system/config/config.php');
$start_time = microtime(1);
    define('PATH_SITE_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);
	include('system/config/config.php');
    $page_content = new content();
    $page_content->build_content();
print_r(PATH_BASE);
print_r($GLOBALS['global_preference']);
print_r($page_content);

$time_stack = [];
$html = render_html(['image_src'=>19233,'name'=>'Listing Title Testing','business'=>11760],'test_template');
$time_stack['end'] = microtime(1);
print_r($time_stack);
//print_r('Execution Time: '.$end_time.' - '.$start_time.' = '.($end_time - $start_time));
print_r($html);
print_r($global_message->display());
exit();
    $page_content->render();
?>