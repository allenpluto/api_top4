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
    //$my_uri = 'http://localhost/allen_frame_trial/json/select_business_by_uri';
    $post_value = $_GET;
    if (isset($post_value['method']))
    {
        $method = $post_value['method'];
        unset($post_value['method']);
    }
    else
    {
        $method = 'list_available_method';
    }

    $handler_uri = 'http://api.top4.com.au/json/'.$method;
    //$handler_uri = $my_uri.($_SERVER['QUERY_STRING']?'?'.$_SERVER['QUERY_STRING']:'').'&handler=true';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $handler_uri);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //'Auth-Key: 0650-2370-f1fa-24bf-019d-800f-a1b3-cf66'
        'Auth-Key: dbf5-6923-311e-367c-1a4b-14ed-aa00-f5ce'
    ));
    curl_setopt($ch,CURLOPT_POST, count($post_value));
    curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post_value));
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
