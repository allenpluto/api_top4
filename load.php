<?php
echo '<pre>';
print_r(dirname(__FILE__));
print_r($_SERVER);
echo file_exists('system/config/config.php');

    define('PATH_SITE_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);
	include('system/config/config.php');
    $page_content = new content();
print_r(PATH_BASE);
print_r($GLOBALS['global_preference']);
print_r($page_content);
exit();
    $page_content->render();
?>