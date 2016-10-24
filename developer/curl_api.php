<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14/10/2016
 * Time: 3:30 PM
 */
if (!isset($_GET['handler']))
{
    //$my_uri = $_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:'http';
    //$my_uri .= '://';
    //$my_uri .= $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']:'127.0.0.1';
    //$my_uri .= $_SERVER['REQUEST_URI']?$_SERVER['REQUEST_URI']:'/';
    $my_uri = 'http://localhost/allen_frame_trial/json/select_business_by_uri';

    $handler_uri = $my_uri.($_SERVER['QUERY_STRING']?'&handler=true':'?handler=true');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $handler_uri);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Auth-Key: 0650-2370-f1fa-24bf-019d-800f-a1b3-cf66'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $contents = curl_exec ($ch);
    //curl_close ($ch);
    //echo '<pre><h1>I\'m Caller</h1><br>';
    //print_r($_SERVER);
    print_r($contents);
}
else
{
    echo '<pre><h1>I\'m Handler</h1><br>';
    print_r($_SERVER);
    print_r(getallheaders());
}
