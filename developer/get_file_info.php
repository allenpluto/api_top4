<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14/10/2016
 * Time: 12:15 PM
 */

// TEST GET File Header
echo '<pre>';
if (isset($_GET['uri']))
{
    $_GET['path'] = str_replace('http://localhost','C:\wamp\www',$_GET['uri']);
    $_GET['path'] = str_replace('/','\\',$_GET['path']);
}
$file_info = array(
    'file_size'=>filesize($_GET['path']),
    'file_update'=>filemtime($_GET['path'])
);
print_r($file_info);