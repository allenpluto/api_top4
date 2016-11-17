<?php
// Class Object
// Name: entity_contact
// Description: contact table, stores all user account contact related information

class entity_contact extends entity
{
    var $parameter = array(
        'table' => '`Contact`',
        'primary_key' => 'account_id',
        'table_fields' => [
            'account_id'=>'account_id',
            'first_name'=>'first_name',
            'last_name'=>'last_name',
            'company'=>'company',
            'address'=>'address',
            'address2'=>'address2',
            'city'=>'city',
            'state'=>'state',
            'zip'=>'zip',
            'country'=>'country',
            'latitude'=>'latitude',
            'longitude'=>'longitude',
            'phone'=>'phone',
            'fax'=>'fax',
            'email'=>'email',
            'url'=>'url',
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
}