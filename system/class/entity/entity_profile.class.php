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
            'image_id'=>'image_id',
            'banner_id'=>'banner_id',
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
                if (isset($parameter['fields']['image']))
                {
                    unset($parameter['fields']['image']);

                }
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

    function update($value = array(), $parameter = array())
    {
        if (empty($value))
        {
            if (empty($this->row))
            {
                $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' INSERT/UPDATE entity with empty value';
                return false;
            }
            else
            {
                if (count($this->row) > 1)
                {
                    $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' UPDATE entity with multiple row';
                    return false;
                }
                $value = end($this->row);
            }
        }

        $current_row = $this->get();
        if (empty($current_row))
        {
            $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' Current Record for '.implode($this->id_group).' does not exist, cannot update';
            return false;
        }
        $current_row = end($current_row);


        if (isset($value['image']))
        {
            if (!empty($current_row['image_id']))
            {
                $image_obj = new entity_account_image($current_row['image_id']);
                $image_obj->delete();
                unset($image_obj);
            }

            $image_row = [];
            $image_size = @getimagesize($value['image']);
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
                $image_row['data'] = $value['image'];
            }
            if (!isset($image_row['type'])) $image_row['type'] = 'JPG';
            if (!empty($value['account_id'])) $image_row['prefix'] = $value['account_id'].'_';
            else $image_row['prefix'] = $current_row['account_id'].'_';

            $image_obj = new entity_account_image();
            $image_obj->set(['row'=>[$image_row]]);

            $value['image_id'] = implode(',',$image_obj->id_group);
            unset($image_obj);
        }

        if (isset($value['banner']))
        {
            if (!empty($current_row['banner_id']))
            {
                $image_obj = new entity_account_image($current_row['banner_id']);
                $image_obj->delete();
                unset($image_obj);
            }

            $image_row = [];
            $image_size = @getimagesize($value['banner']);
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
                $image_row['data'] = $value['banner'];
            }
            if (!isset($image_row['type'])) $image_row['type'] = 'JPG';
            if (!empty($value['account_id'])) $image_row['prefix'] = $value['account_id'].'_';
            else $image_row['prefix'] = $current_row['account_id'].'_';

            $image_obj = new entity_account_image();
            $image_obj->set(['row'=>[$image_row]]);

            $value['banner_id'] = implode(',',$image_obj->id_group);
            unset($image_obj);
        }

        return parent::update($value, $parameter);
    }
}