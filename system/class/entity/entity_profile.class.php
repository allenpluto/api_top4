<?php
// Class Object
// Name: entity_profile
// Description: profile table, stores all user account profile related information

class entity_profile extends entity
{
    var $parameter = array(
        'table' => '`Profile`',
        'primary_key' => 'account_id',
    );

    function __construct($value = null, $parameter = array())
    {
        parent::__construct();

        $dbLocation = 'mysql:dbname=top4_main;host='.DATABASE_HOST;
        $dbUser = DATABASE_USER;
        $dbPass = DATABASE_PASSWORD;
        $db = new PDO($dbLocation, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''));

        $this->_conn = $db;
    }
}