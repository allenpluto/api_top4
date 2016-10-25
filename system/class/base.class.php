<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 25/10/2016
 * Time: 3:31 PM
 */

abstract class base {
    public $message;
    public $format;
    public $preference;

    function __construct($parameter = array())
    {
        $this->message = message::get_instance();
        $this->format = format::get_obj();
        $this->preference = preference::get_instance();
    }
}