<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 25/10/2016
 * Time: 3:00 PM
 */
if (!isset($global_preference)) $global_preference = preference::get_instance();

// Image Size (width grid)
$global_preference->api = array();
if ($global_preference->environment == 'production')
{
    $global_preference->api = array(
        'force_ssl'=>true
    );
}
else
{
    $global_preference->api = array(
        'force_ssl'=>false
    );
}
