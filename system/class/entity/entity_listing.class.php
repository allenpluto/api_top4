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
        $get_listing_parameter = ['fields' => ['id','title','friendly_url','abn','address','address2','city','state','zip_code','account_id','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','bulked','importID','thumb_id','image_id','banner_id','category','updated','entered']];
        $get_listing_parameter = array_merge($get_listing_parameter, $parameter);
        $get_listing_result = parent::get($get_listing_parameter);

        if (count($this->row) == 0)
        {
            // Error Handling, ZERO_RESULTS
            $this->message->notice = 'No listing fits the get conditions';
            return false;
        }
        return $this->row;
    }

    function set($parameter = array())
    {
//print_r($parameter);
        $set_listing_parameter = ['fields' => ['id','title','abn','address','address2','city','state','zip_code','latitude','longitude','account_id','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','bulked','importID','thumb_id','image_id','banner_id','category','updated','entered'],'row'=>[]];
        if (isset($parameter['row']))
        {
            foreach ($parameter['row'] as $row_index=>&$row)
            {
                $set_listing_row = $row;
                if (isset($set_listing_row['image']))
                {
                    $image_row = [];
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
                    if (!isset($image_row['type'])) $image_row['type'] = 'JPG';
                    $image_row['prefix'] = $set_listing_row['account_id'].'_';
                    $image_obj = new entity_account_image();
                    $image_obj->set(['row'=>[$image_row]]);

                    $set_listing_row['image_id'] = implode(',',$image_obj->id_group);
                    unset($image_obj);

                    if (!empty($set_listing_row['image_id']))
                    {
                        // Create Thumbnail Image for Business Logo
                        $thumb_row = [
                            'width'=> 200,
                            'height'=>200,
                            'type'=>$image_row['type'],
                            'prefix'=>$image_row['prefix']
                        ];
                        $source_image = imagecreatefromstring($set_listing_row['image']);
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
                        $thumb_row['data'] = ob_get_contents();
                        ob_get_clean();
                        $image_obj = new entity_account_image();
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
                    $image_row = [];
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
                    if (!isset($image_row['type'])) $image_row['type'] = 'JPG';
                    $image_row['prefix'] = $set_listing_row['account_id'].'_';
                    $image_obj = new entity_account_image();
                    $image_obj->set(['row'=>[$image_row]]);

                    $set_listing_row['banner_id'] = implode(',',$image_obj->id_group);
                    unset($image_obj);
                    unset($set_listing_row['banner']);
                }


                $set_listing_parameter['row'][] = $set_listing_row;
            }
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
        return parent::update($value, $parameter);
    }
}

?>