<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14/10/2016
 * Time: 12:15 PM
 */

// TEST GET File Header
echo '<pre>';
$file_header = @get_headers($_GET['uri'],true);
print_r($file_header);