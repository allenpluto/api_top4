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
        if (isset($parameter['row']))
        {
            $row = &$parameter['row'];
        }
        else
        {
            if (empty($this->row))
            {
                $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' INSERT/UPDATE entity with empty row';
                return false;
            }
            else
            {
                $row = &$this->row;
            }
        }

        foreach ($row as $record_index=>&$record)
        {
            if (isset($record['image']))
            {
                $image_row = [];
                $image_size = @getimagesize($record['image']);
                if ($image_size !== false)
                {
                    $image_row['width'] = $image_size[0];
                    $image_row['height'] = $image_size[1];
                    if (isset($image_size['mime']))
                    {
                        switch ($image_size['mime'])
                        {
                            case 'image/gif':
                                $image_row['type'] = 'GIF';
                                break;
                            case 'image/png':
                                $image_row['type'] = 'PNG';
                                break;
                            case 'image/jpeg':
                            case 'image/pjpeg';
                            default:
                                $image_row['type'] = 'JPG';

                        }
                    }
                    $image_row['data'] = $record['image'];
                }
                if (!isset($image_row['type'])) $image_row['type'] = 'JPG';
                $image_row['prefix'] = $record['account_id'].'_';
                $image_obj = new entity_account_image();
                $image_obj->set(['row'=>[$image_row]]);

                $record['image_id'] = implode(',',$image_obj->id_group);
                unset($image_obj);
            }
            if (isset($record['banner']))
            {
                $image_row = [];
                $image_size = @getimagesize($record['banner']);
                if ($image_size !== false)
                {
                    $image_row['width'] = $image_size[0];
                    $image_row['height'] = $image_size[1];
                    if (isset($image_size['mime']))
                    {
                        switch ($image_size['mime'])
                        {
                            case 'image/gif':
                                $image_row['type'] = 'GIF';
                                break;
                            case 'image/png':
                                $image_row['type'] = 'PNG';
                                break;
                            case 'image/jpeg':
                            case 'image/pjpeg';
                            default:
                                $image_row['type'] = 'JPG';

                        }
                    }
                    $image_row['data'] = $record['banner'];
                }
                if (!isset($image_row['type'])) $image_row['type'] = 'JPG';
                $image_row['prefix'] = $record['account_id'].'_';
                $image_obj = new entity_account_image();
                $image_obj->set(['row'=>[$image_row]]);

                $record['banner_id'] = implode(',',$image_obj->id_group);
                unset($image_obj);
            }

        }
        return parent::set($parameter);
    }
}