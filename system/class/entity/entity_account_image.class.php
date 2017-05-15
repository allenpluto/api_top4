<?php
// Class Object
// Name: entity_account_image
// Description: Image table (under top4_main), stores all user account related images

class entity_account_image extends entity
{
    var $parameter = array(
        'table' => '`Image`',
        'primary_key' => 'id',
        'table_fields' => [
            'id'=>'id',
            'type'=>'type',
            'width'=>'width',
            'height'=>'height',
            'prefix'=>'prefix',
            'data'=>'data'
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

    function get($parameter = array())
    {
        $get_result = parent::get();
        if ($get_result === false) return false;

        foreach ($get_result as $row_index=>&$row)
        {
            $row['file_uri'] = 'https://www.top4.com.au/custom/profile/'.$row['prefix'].'photo_'.$row['id'].'.'.strtolower($row['type']);
        }

        return $get_result;
    }
}

?>