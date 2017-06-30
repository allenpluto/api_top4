<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 30/06/2017
 * Time: 3:22 PM
 */
define('PATH_SITE_BASE','C:\\wamp\\www\\api_top4\\');
include('../system/config/config.php');
$start_stamp = microtime(1);
echo '<pre>';

$entity_gallery_obj = new entity_gallery(52463);
print_r($entity_gallery_obj->fetch_value());
