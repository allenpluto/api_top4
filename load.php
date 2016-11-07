<?php
    define('PATH_SITE_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);
	include('system/config/config.php');
    // !!! IMPORTANT !!! DO NOT print anything before content is defined, static files and special pages may need to set header response
    $page_content = new content();
//echo '<pre>';
//print_r($page_content);
//exit();
    $page_content->render();
    /*if ($page_content->result['status'] != 'OK')
    {
        echo '<pre>';
        print_r($page_content);

        $message = message::get_instance();
        print_r($message);
    }*/
//$start_time = microtime(true);
//echo '<pre>';
//print_r('Execution Time: '. (microtime(true) - $start_time) . '<br>');
?>