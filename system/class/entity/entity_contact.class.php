<?php
// Class Object
// Name: entity_contact
// Description: contact table, stores all user account contact related information

class entity_contact extends entity
{
    var $parameter = array(
        'table' => '`Contact`',
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