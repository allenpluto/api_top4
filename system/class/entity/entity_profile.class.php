<?php
// Class Object
// Name: entity_profile
// Description: profile table, stores all user account profile related information

class entity_profile extends entity
{
    var $parameter = array(
        'table' => '`Profile`',
        'primary_key' => 'account_id',
        'table_fields' => [
            'account_id'=>'account_id',
            'nickname'=>'nickname',
            'personal_message'=>'personal_message',
            'friendly_url'=>'friendly_url',
            'credit_points'=>'credit_points',
            'updated'=>'updated',
            'entered'=>'entered'
        ]
    );

    function __construct($value = null, $parameter = array())
    {
        $dbLocation = 'mysql:dbname=top4_main;host='.DATABASE_HOST;
        $dbUser = DATABASE_USER;
        $dbPass = DATABASE_PASSWORD;
        $db = new PDO($dbLocation, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''));

        $this->_conn = $db;

        parent::__construct($value, $parameter);
    }

    function set($parameter = array())
    {
        return parent::set($parameter);
    }
}