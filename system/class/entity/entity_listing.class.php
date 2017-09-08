<?php
// Class Object
// Name: entity_listing
// Description: Listing table, business listings

class entity_listing extends entity
{
    var $parameter = array(
        'table' => 'Listing',
        'relational_fields'=>[
            'category'=>[
                'table'=>'Listing_Category',
                'primary_key'=>['listing_id','category_id'],
                'source_id_field'=>'listing_id',
                'target_id_field'=>'category_id'
            ],
        ]
    );

    function get($parameter = array())
    {
        $get_listing_parameter = ['fields' => ['id','title','friendly_url','abn','address','address2','city','region','state','zip_code','account_id','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','bulked','importID','thumb_id','image_id','banner_id','category','updated','entered']];
        $get_listing_parameter = array_merge($get_listing_parameter, $parameter);
        if (in_array('thumb',$get_listing_parameter['fields']))
        {
            $get_listing_parameter['fields'] = array_diff($get_listing_parameter['fields'],['thumb']);
            $get_listing_parameter['fields'][] = 'thumb_id';
        }
        if (in_array('image',$get_listing_parameter['fields']))
        {
            $get_listing_parameter['fields'] = array_diff($get_listing_parameter['fields'],['image']);
            $get_listing_parameter['fields'][] = 'image_id';
        }
        if (in_array('banner',$get_listing_parameter['fields']))
        {
            $get_listing_parameter['fields'] = array_diff($get_listing_parameter['fields'],['banner']);
            $get_listing_parameter['fields'][] = 'banner_id';
        }
        $get_listing_result = parent::get($get_listing_parameter);

        if (empty($get_listing_result))
        {
            return $get_listing_result;
        }

        foreach($get_listing_result as $row_index=>&$row)
        {
            if (!empty($row['thumb_id']))
            {
                $image_obj = new entity_listing_image($row['thumb_id']);
                $image_row = $image_obj->get();
                if (!empty($image_row))
                {
                    $image_row = end($image_row);
                    $row['thumb'] = $image_row['file_uri'];
                }
                unset($image_obj);
                unset($image_row);
            }
            if (!empty($row['image_id']))
            {
                $image_obj = new entity_listing_image($row['image_id']);
                $image_row = $image_obj->get();
                if (!empty($image_row))
                {
                    $image_row = end($image_row);
                    $row['image'] = $image_row['file_uri'];
                }
                unset($image_obj);
                unset($image_row);
            }
            if (!empty($row['banner_id']))
            {
                $image_obj = new entity_listing_image($row['banner_id']);
                $image_row = $image_obj->get();
                if (!empty($image_row))
                {
                    $image_row = end($image_row);
                    $row['banner'] = $image_row['file_uri'];
                }
                unset($image_obj);
                unset($image_row);
            }
        }

        return $get_listing_result;
    }

    function set($parameter = array())
    {
        if (!isset($parameter['row']))
        {
            $parameter['row'] = $this->row;
        }
//print_r($parameter);
        $set_listing_parameter = ['fields' => ['id','title','abn','address','address2','postcode_suburb_id','city','region','state','zip_code','latitude','longitude','account_id','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','bulked','importID','thumb_id','image_id','banner_id','category','updated','entered','cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount'],'row'=>[]];
        foreach ($parameter['row'] as $row_index=>&$row)
        {
            $set_listing_row = $row;
            if (isset($set_listing_row['image']))
            {
                if (empty($set_listing_row['image']))
                {
                    $set_listing_row['image_id'] = 0;
                    $set_listing_row['thumb_id'] = 0;
                }
                else
                {
                    $image_row = [
                        'width'=>500,
                        'height'=>500,
                        'type'=>'JPG',
                        'prefix'=>'sitemgr_'
                    ];
                    $image_size = @getimagesize($set_listing_row['image']);
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
                        $image_row['data'] = $set_listing_row['image'];
                    }
                    if (!empty($set_listing_row['account_id'])) $image_row['prefix'] = $set_listing_row['account_id'].'_';
                    $image_obj = new entity_listing_image();
                    $image_obj->set(['row'=>[$image_row]]);

                    $set_listing_row['image_id'] = implode(',',$image_obj->id_group);
                    unset($image_obj);
                }

                if (!empty($set_listing_row['image_id']))
                {
                    // Create Thumbnail Image for Business Logo
                    $thumb_row = [
                        'width'=>200,
                        'height'=>200,
                        'prefix'=>$image_row['prefix']
                    ];
                    if ($image_row['type'] == 'PNG')
                    {
                        $thumb_row['type'] = 'PNG';
                        $thumb_row_mime = 'image/png';
                    }
                    else
                    {
                        $thumb_row['type'] = 'JPG';
                        $thumb_row_mime = 'image/jpeg';
                    }
                    $source_image = imagecreatefromstring(file_get_contents($set_listing_row['image']));
                    $target_image = imagecreatetruecolor($thumb_row['width'],$thumb_row['height']);

                    imagecopyresampled($target_image,$source_image,0,0,0,0,$thumb_row['width'], $thumb_row['height'],$image_row['width'],$image_row['height']);
                    imageinterlace($target_image,true);

                    ob_start();
                    if ($thumb_row['type'] == 'JPG') imagejpeg($target_image, NULL, 80);
                    if ($thumb_row['type'] == 'PNG')
                    {
                        imagesavealpha($target_image, true);
                        imagepng($target_image, NULL, 7, PNG_NO_FILTER);
                    }
                    $thumb_file = ob_get_contents();
                    ob_get_clean();
                    $thumb_row['data'] = 'data:'.$thumb_row_mime.';base64,'.base64_encode($thumb_file);

                    $image_obj = new entity_listing_image();
                    $image_obj->set(['row'=>[$thumb_row]]);

                    $set_listing_row['thumb_id'] = implode(',',$image_obj->id_group);
                    unset($image_obj);

                    imagedestroy($source_image);
                    imagedestroy($target_image);
                }

                unset($set_listing_row['image']);
            }
            if (isset($set_listing_row['banner']))
            {
                if (empty($set_listing_row['banner']))
                {
                    $set_listing_row['banner_id'] = 0;
                }
                else
                {
                    $image_row = [
                        'width'=>1200,
                        'height'=>200,
                        'type'=>'JPG',
                        'prefix'=>'sitemgr_'
                    ];
                    $image_size = @getimagesize($set_listing_row['banner']);
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
                        $image_row['data'] = $set_listing_row['banner'];
                    }
                    if (!empty($set_listing_row['account_id'])) $image_row['prefix'] = $set_listing_row['account_id'].'_';
                    $image_obj = new entity_listing_image();
                    $image_obj->set(['row'=>[$image_row]]);

                    $set_listing_row['banner_id'] = implode(',',$image_obj->id_group);
                    unset($image_obj);
                }
                unset($set_listing_row['banner']);
            }
            if (isset($set_listing_row['phone'])) {$set_listing_row['phone'] = $this->format->phone_remove_format($set_listing_row['phone']);}
            if (isset($set_listing_row['alternate_phone'])) {$set_listing_row['alternate_phone'] = $this->format->phone_remove_format($set_listing_row['alternate_phone']);}
            if (isset($set_listing_row['mobile_phone'])) {$set_listing_row['mobile_phone'] = $this->format->phone_remove_format($set_listing_row['mobile_phone']);}
            if (isset($set_listing_row['fax'])) {$set_listing_row['fax'] = $this->format->phone_remove_format($set_listing_row['fax']);}
            if (isset($set_listing_row['cd_plan_name']))
            {
                $set_listing_parameter['fields'][] = 'level';
                if ($set_listing_row['cd_plan_name'] == 'basic')
                {
                    $set_listing_row['level'] = 10;
                }
                else
                {
                    $set_listing_row['level'] = 70;
                }
            }

            $set_listing_parameter['row'][] = $set_listing_row;
        }

        $set_listing_parameter = array_merge($parameter, $set_listing_parameter);

//print_r($set_listing_parameter);
        $set_listing_result = parent::set($set_listing_parameter);
        if ($set_listing_result !== FALSE AND isset($parameter['row']))
        {
            foreach($parameter['row'] as $row_index=>&$row)
            {
                foreach($this->row as $result_row_index=>&$result_row)
                {
                    if ($row['title'] == $result_row['title'])
                    {
                        $row['friendly_url'] = $result_row['id'];
                        if (!empty($row['title'])) $row['friendly_url'] = $row['title'].' '. $row['friendly_url'];
                        $row['friendly_url'] = $this->format->file_name($row['friendly_url']);
                        $result_row['friendly_url'] = $row['friendly_url'];
                        $update_listing_row_obj = new entity_listing($result_row['id']);
                        $update_listing_row_obj->update(['friendly_url'=>$row['friendly_url']]);
                        unset($update_listing_row_obj);
                    }
                }
            }
        }


        return $this->row;
    }

    function delete($parameter = array())
    {
        $get_result = $this->get();

        if (!empty($get_result))
        {
            foreach ($get_result as $row_index=>$row)
            {
                if (!empty($row['thumb_id']))
                {
                    $image_obj = new entity_listing_image($row['thumb_id']);
                    $image_obj->delete();
                    unset($image_obj);
                }

                if (!empty($row['image_id']))
                {
                    $image_obj = new entity_listing_image($row['image_id']);
                    $image_obj->delete();
                    unset($image_obj);
                }

                if (!empty($row['banner_id']))
                {
                    $image_obj = new entity_listing_image($row['banner_id']);
                    $image_obj->delete();
                    unset($image_obj);
                }
            }
        }

        return parent::delete($parameter);
    }

    function update($value = array(), $parameter = array())
    {
        if (empty($this->id_group))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot perform delete with empty id_group';
            return array();
        }
        if (isset($value['title']))
        {
            $value['friendly_url'] = $this->format->file_name($value['title'].' '.end($this->id_group));
        }
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
                $image_obj = new entity_account_image($current_row['thumb_id']);
                $image_obj->delete();
                unset($image_obj);
            }

            if (empty($value['image']))
            {
                $value['image_id'] = 0;
                $value['thumb_id'] = 0;
            }
            else
            {
                $image_row = [
                    'width'=>500,
                    'height'=>500,
                    'type'=>'JPG',
                    'prefix'=>'sitemgr_'
                ];
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
                if (isset($value['account_id']))
                {
                    if (empty($value['account_id']))
                    {
                        $image_row['prefix'] = 'sitemgr_';
                    }
                    else
                    {
                        $image_row['prefix'] = $value['account_id'].'_';
                    }
                }
                else
                {
                    if (empty($current_row['account_id']))
                    {
                        $image_row['prefix'] = 'sitemgr_';
                    }
                    else
                    {
                        $image_row['prefix'] = $current_row['account_id'].'_';
                    }
                }

                $image_obj = new entity_listing_image();
                $image_obj->set(['row'=>[$image_row]]);

                $value['image_id'] = implode(',',$image_obj->id_group);
                unset($image_obj);
            }

            if (!empty($value['image_id']))
            {
                // Create Thumbnail Image for Business Logo
                $thumb_row = [
                    'width'=>200,
                    'height'=>200,
                    'prefix'=>$image_row['prefix']
                ];
                if ($image_row['type'] == 'PNG')
                {
                    $thumb_row['type'] = 'PNG';
                    $thumb_row_mime = 'image/png';
                }
                else
                {
                    $thumb_row['type'] = 'JPG';
                    $thumb_row_mime = 'image/jpeg';
                }
                $source_image = imagecreatefromstring(file_get_contents($value['image']));
                $target_image = imagecreatetruecolor($thumb_row['width'],$thumb_row['height']);

                imagecopyresampled($target_image,$source_image,0,0,0,0,$thumb_row['width'], $thumb_row['height'],$image_row['width'],$image_row['height']);
                imageinterlace($target_image,true);

                ob_start();
                if ($thumb_row['type'] == 'JPG') imagejpeg($target_image, NULL, 80);
                if ($thumb_row['type'] == 'PNG')
                {
                    imagesavealpha($target_image, true);
                    imagepng($target_image, NULL, 7, PNG_NO_FILTER);
                }
                $thumb_file = ob_get_contents();
                ob_get_clean();
                $thumb_row['data'] = 'data:'.$thumb_row_mime.';base64,'.base64_encode($thumb_file);

                $image_obj = new entity_listing_image();
                $image_obj->set(['row'=>[$thumb_row]]);

                $value['thumb_id'] = implode(',',$image_obj->id_group);
                unset($image_obj);

                imagedestroy($source_image);
                imagedestroy($target_image);
            }

            unset($value['image']);
        }
        if (isset($value['banner']))
        {
            if (!empty($current_row['banner_id']))
            {
                $image_obj = new entity_account_image($current_row['banner_id']);
                $image_obj->delete();
                unset($image_obj);
            }

            if (empty($value['banner']))
            {
                $value['banner_id'] = 0;
            }
            else
            {
                $image_row = [
                    'width'=>1200,
                    'height'=>200,
                    'type'=>'JPG',
                    'prefix'=>'sitemgr_'
                ];
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
                if (isset($value['account_id']))
                {
                    if (empty($value['account_id']))
                    {
                        $image_row['prefix'] = 'sitemgr_';
                    }
                    else
                    {
                        $image_row['prefix'] = $value['account_id'].'_';
                    }
                }
                else
                {
                    if (empty($current_row['account_id']))
                    {
                        $image_row['prefix'] = 'sitemgr_';
                    }
                    else
                    {
                        $image_row['prefix'] = $current_row['account_id'].'_';
                    }
                }

                $image_obj = new entity_listing_image();
                $image_obj->set(['row'=>[$image_row]]);

                $value['banner_id'] = implode(',',$image_obj->id_group);
                unset($image_obj);
            }
            unset($value['banner']);
        }
        if (isset($value['phone'])) {$value['phone'] = $this->format->phone_remove_format($value['phone']);}
        if (isset($value['alternate_phone'])) {$value['alternate_phone'] = $this->format->phone_remove_format($value['alternate_phone']);}
        if (isset($value['mobile_phone'])) {$value['mobile_phone'] = $this->format->phone_remove_format($value['mobile_phone']);}
        if (isset($value['fax'])) {$value['fax'] = $this->format->phone_remove_format($value['fax']);}
        if (isset($value['cd_plan_name']))
        {
            if ($value['cd_plan_name'] == 'basic')
            {
                $value['level'] = 10;
            }
            else
            {
                $value['level'] = 70;
            }
        }
        return parent::update($value, $parameter);

    }
}

?>