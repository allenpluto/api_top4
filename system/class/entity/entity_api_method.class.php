<?php
// Class Object
// Name: entity_api_method
// Description: api method table, list all the api method name, may or may not available to current api user

class entity_api_method extends entity
{
    function __construct($value = null, $parameter = array())
    {
        if (empty($parameter['api_id']))
        {
            // Error Handling api account id not provided
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Cannot access API Account';
            return false;
        }
        $this->api_id = $parameter['api_id'];
        parent::__construct($value,$parameter);
    }

    // General Public Accessible Functions
    function list_available_method(&$parameter = array())
    {
        $row = $this->get(['where'=>'`tbl_entity_api_method`.id < 100 OR `tbl_entity_api_method`.id IN (SELECT api_method_id FROM tbl_rel_api_to_api_method WHERE api_id = :api_id)','bind_param'=>[':api_id'=>$this->api_id],'id_group'=>array()]);
        $result = [];
        foreach($row as $record_index=>$record)
        {
            $result[] = ['name'=>$record['name'],'request_uri'=>$record['friendly_uri'],'description'=>$record['description'],'field'=>($record['field']?json_decode($record['field'],true):array())];
        }
        return $result;
    }

    // Insert Functions
    function insert_account(&$parameter = array())
    {
        $entity_account_obj = new entity_account();
        $account_field_array = ['username','first_name','last_name','password','company','address','address2','city','state','zip','image','banner','latitude','longitude','phone','fax','email','url','nickname','personal_message','entered','updated'];
        $set_account_parameter = array('row'=>array());

        if (empty($parameter['option']['username']) OR empty($parameter['option']['first_name']) OR empty($parameter['option']['last_name']))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'New Account Details not provided, username, first_name and last_name are mandatory';
            return false;
        }

        $entity_account_check = new entity_account();
        $entity_account_check_param = array(
            'bind_param' => array(':username'=>$parameter['option']['username']),
            'where' => array('`username` = :username')
        );
        $entity_account_check->get($entity_account_check_param);
        if (count($entity_account_check->row) > 0)
        {
            $parameter = ['status'=>'REQUEST_DENIED','message'=>'Account already exist','username'=>$parameter['option']['username']];
            return false;
        }
        unset($entity_account_check);

        $set_account_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$account_field_array))
            {
                $set_account_row[$parameter_item_index] = $parameter_item;
            }
        }
        $set_account_row['importID'] = $this->api_id;
        $set_account_row['country'] = 'Australia';

        if (!empty($parameter['option']['latitude']) AND !empty($parameter['option']['longitude']))
        {
            $entity_postcode_suburb_obj = new entity_postcode_suburb();
            $location_data = $entity_postcode_suburb_obj->get_location_from_geo(['latitude'=>$parameter['option']['latitude'],'longitude'=>$parameter['option']['longitude']]);
            if (!empty($location_data))
            {
                $api_location_log = PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_location_log.txt';
                if (!file_exists(dirname($api_location_log))) mkdir(dirname($api_location_log), 0755, true);
                file_put_contents($api_location_log,PHP_EOL.'Location Request: ['.date('D, d M Y H:i:s').']'.PHP_EOL.PHP_EOL,FILE_APPEND);

                if (empty($set_account_row['address'])) $set_account_row['address'] = $location_data['address'];
                if (!empty($set_account_row['city']))
                {
                    if (strtolower($set_account_row['city']) != strtolower($location_data['suburb']))
                    {
                        file_put_contents($api_location_log,'Suburb Inconsistent: Geocode suburb '.$location_data['suburb'].' - API Posted suburb '.$set_account_row['city'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_account_row['city'] = $location_data['suburb'];
                }

                if (!empty($set_account_row['state']))
                {
                    if (strtolower($set_account_row['state']) != strtolower($location_data['state']))
                    {
                        file_put_contents($api_location_log,'State Inconsistent: Geocode state '.$location_data['state'].' - API Posted state '.$set_account_row['state'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_account_row['state'] = $location_data['state'];
                }

                if (!empty($set_account_row['zip']))
                {
                    if (strtolower($set_account_row['zip']) != strtolower($location_data['post_code']))
                    {
                        file_put_contents($api_location_log,'Post Code Inconsistent: Geocode suburb '.$location_data['post_code'].' - API Posted suburb '.$set_account_row['zip'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_account_row['zip'] = $location_data['post_code'];
                }
            }
        }
        if (empty($parameter['option']['entered']))
        {
            $set_account_row['entered'] = date('Y-m-d H:i:s');
        }
        if (empty($parameter['option']['updated']))
        {
            $set_account_row['updated'] = date('Y-m-d H:i:s');
        }
        $set_account_parameter['row'][] = $set_account_row;

        $account_insert_result = $entity_account_obj->set($set_account_parameter);
        if ($account_insert_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        if (count($entity_account_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
            return false;
        }
        else
        {
            $record = end($entity_account_obj->row);
            $parameter = ['status'=>'OK','result'=>['id'=>$record['id'],'token'=>$record['complementary_info'],'username'=>$record['username'],'password'=>$record['password']]];
            return $parameter['result'];
        }
    }

//    function insert_account_multiple(&$parameter = array())
//    {
//        $entity_account = new entity_account();
//        $account_field_array = ['username','first_name','last_name','company','address','address2','city','state','zip','country','latitude','longitude','phone','fax','email','url','nickname','personal_message'];
//        $set_account_parameter = array('row'=>array());
//        $parameter['result'] = [];
//
//        if (!isset($parameter['row']))
//        {
//            $parameter = ['row'=>[$parameter]];
//        }
//
//        foreach ($parameter['row'] as $parameter_row_index=>$parameter_row)
//        {
//            if (empty($parameter_row['username']) OR empty($parameter_row['first_name']) OR empty($parameter_row['last_name']))
//            {
//                // Error Handling, Website uri not provided
//                //$parameter['status'] = 'INVALID_REQUEST';
//                //$parameter['message'] = 'New Account Details not provided, username, first_name and last_name are mandatory';
//                $parameter['result'][] = ['status'=>'INVALID_REQUEST','message'=>'New Account Details not provided, username, first_name and last_name are mandatory'];
//                continue;
//            }
//
//            $entity_account_check = new entity_account();
//            $entity_account_check_param = array(
//                'bind_param' => array(':username'=>$parameter_row['username']),
//                'where' => array('`username` = :username')
//            );
//            $entity_account_check->get($entity_account_check_param);
//            if (count($entity_account_check->row) > 0)
//            {
//                $parameter['result'][] = ['status'=>'REQUEST_DENIED','message'=>'Account Exists','username'=>$parameter_row['username']];
//                continue;
//            }
//            unset($entity_account_check);
//
//            $set_account_row = array();
//            foreach($parameter_row as $parameter_item_index=>$parameter_item)
//            {
//                if (in_array($parameter_item_index,$account_field_array))
//                {
//                    $set_account_row[$parameter_item_index] = $parameter_item;
//                }
//            }
//            $set_account_row['importID'] = $this->api_id;
//            $set_account_parameter['row'][] = $set_account_row;
//        }
//        $account_insert_result = $entity_account->set($set_account_parameter);
//        if ($account_insert_result === FALSE)
//        {
//            $parameter['status'] = 'SERVER_ERROR';
//            $parameter['message'] = 'Database insert request failed, try again later';
//            return false;
//        }
//
//        if (count($entity_account->row) == 0)
//        {
//            $parameter['status'] = 'ZERO_RESULTS';
//            $parameter['message'] = 'No row inserted';
//            return false;
//        }
//        else
//        {
//            foreach($entity_account->row as $record_index=>$record)
//            {
//                $parameter['result'][] = ['id'=>$record['id'],'token'=>$record['complementary_info'],'username'=>$record['username'],'password'=>$record['password']];
//            }
//        }
//        $overall_status = array();
//        foreach($parameter['result'] as $record_index=>$record)
//        {
//            if (empty($overall_status[$record['status']])) $overall_status[$record['status']] = 1;
//            else $overall_status[$record['status']]++;
//        }
//        if (empty($overall_status['OK']))
//        {
//            $parameter['status'] = 'ZERO_RESULTS';
//            $parameter['message'] = 'No row inserted';
//            return false;
//        }
//        else
//        {
//            $parameter['status'] = 'OK';
//            $parameter['message'] = $overall_status['OK'].' row(s) inserted';
//            return $parameter['result'];
//        }
//    }

    function insert_business(&$parameter = array())
    {
        $entity_listing_obj = new entity_listing();
        $listing_field_array = ['title','latitude','longitude','category','account_id','abn','address','address2','city','state','zip','image','banner','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','entered','updated'];
        if ($this->api_id == 10001 OR $this->api_id == 10003)
        {
            $listing_field_array = array_merge($listing_field_array,['cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount']);
        }

        $set_listing_parameter = array('row'=>array());

        if (empty($parameter['option']['title']) OR empty($parameter['option']['latitude']) OR empty($parameter['option']['longitude']) OR empty($parameter['option']['category']))
        {
            // Error Handling, title, category, latitude or longitude not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'New Listing Details not provided. Title, category, latitude and longitude are mandatory fields';
            return false;
        }

        $category_array = explode(',',$parameter['option']['category']);
        $category_name_array = array();
        $category_schema_array = array();
        foreach($category_array as $category_index=>$category)
        {
            if (preg_match('/^http/',$category) == 1) $category_schema_array[] = $category;
            else $category_name_array[] = $category;
        }
        $entity_category_param = array(
            'bind_param' => array(),
            'where' => array()
        );
        $category_where = array();
        if (!empty($category_schema_array))
        {
            $category_where[] = '`schema_itemtype` IN (:schema_'.implode(',:schema_',array_keys($category_schema_array)).')';
            foreach($category_schema_array as $category_schema_index=>$category_schema)
            {
                $entity_category_param['bind_param'][':schema_'.$category_schema_index] = $category_schema;
            }
        }
        if (!empty($category_name_array))
        {
            $category_where[] = '`name` IN (:name_'.implode(',:name_',array_keys($category_name_array)).')';
            foreach($category_name_array as $category_name_index=>$category_name)
            {
                $entity_category_param['bind_param'][':name_'.$category_name_index] = $category_name;
            }
        }

        if (empty($category_where))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Category provided is not in correct format, it should be either schema name or schema full url start with http://, multiple categories should be separate by comma';
            return false;
        }
        $entity_category_param['where'][] = implode(' AND ',$category_where);

        $entity_category_obj = new entity_category();
        $entity_category_obj->get($entity_category_param);
        if (empty($entity_category_obj->id_group))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Cannot find category';
            return false;
        }

        $set_listing_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$listing_field_array))
            {
                $set_listing_row[$parameter_item_index] = $parameter_item;
            }
        }

        $set_listing_row['importID'] = $this->api_id;
        $set_listing_row['status'] = 'A';
        $set_listing_row['bulked'] = 'y';
        $set_listing_row['thumb_id'] = 0;
        $set_listing_row['image_id'] = 0;
        $set_listing_row['banner_id'] = 0;
        $set_listing_row['category'] = implode($entity_category_obj->id_group);

        $entity_postcode_suburb_obj = new entity_postcode_suburb();
        $location_data = $entity_postcode_suburb_obj->get_location_from_geo(['latitude'=>$parameter['option']['latitude'],'longitude'=>$parameter['option']['longitude']]);
        if (!empty($location_data))
        {
            $api_location_log = PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_location_log.txt';
            if (!file_exists(dirname($api_location_log))) mkdir(dirname($api_location_log), 0755, true);
            file_put_contents($api_location_log,PHP_EOL.'Location Request: ['.date('D, d M Y H:i:s').']'.PHP_EOL.PHP_EOL,FILE_APPEND);

            $set_listing_row['postcode_suburb_id'] = $location_data['id'];
            if (empty($set_listing_row['address'])) $set_listing_row['address'] = $location_data['address'];
            if (!empty($set_listing_row['city']))
            {
                if (strtolower($set_listing_row['city']) != strtolower($location_data['suburb']))
                {
                    file_put_contents($api_location_log,'Suburb Inconsistent: Geocode suburb '.$location_data['suburb'].' - API Posted suburb '.$set_listing_row['city'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_listing_row['city'] = $location_data['suburb'];
            }

            if (empty($set_listing_row['region'])) $set_listing_row['region'] = $location_data['region'];

            if (!empty($set_listing_row['state']))
            {
                if (strtolower($set_listing_row['state']) != strtolower($location_data['state']))
                {
                    file_put_contents($api_location_log,'State Inconsistent: Geocode state '.$location_data['state'].' - API Posted state '.$set_listing_row['state'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_listing_row['state'] = $location_data['state'];
            }

            if (!empty($set_listing_row['zip_code']))
            {
                if (strtolower($set_listing_row['zip_code']) != strtolower($location_data['post_code']))
                {
                    file_put_contents($api_location_log,'Post Code Inconsistent: Geocode suburb '.$location_data['post_code'].' - API Posted suburb '.$set_listing_row['zip_code'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_listing_row['zip_code'] = $location_data['post_code'];
            }
        }

        if (empty($parameter['option']['entered']))
        {
            $set_listing_row['entered'] = date('Y-m-d H:i:s');
        }
        if (empty($parameter['option']['updated']))
        {
            $set_listing_row['updated'] = date('Y-m-d H:i:s');
        }

        $set_listing_parameter['row'][] = $set_listing_row;

        $listing_insert_result = $entity_listing_obj->set($set_listing_parameter);

        if ($listing_insert_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        if (count($entity_listing_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
            return false;
        }
        else
        {
            $record = end($entity_listing_obj->row);
            $parameter = ['status'=>'OK','result'=>['id'=>$record['id'],'title'=>$record['title'],'listing_page'=>'https://www.top4.com.au/business/'.$record['friendly_url']]];
            return $parameter['result'];
        }
    }

    function insert_gallery(&$parameter = array())
    {
        if (empty($parameter['option']['account_id']) OR empty($parameter['option']['listing_id']))
        {
            // Error Handling, account_id or listing_id not set
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Create New Gallery Failed. Account_id and listing_id are mandatory fields';
            return false;
        }

        $entity_listing_obj = new entity_listing($parameter['option']['listing_id']);
        if (empty($entity_listing_obj->id_group))
        {
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Create New Gallery Failed. Listing does not exist';
            return false;
        }
        $entity_listing_data = $entity_listing_obj->get(['fields'=>['id','account_id','title']]);
        if ($entity_listing_data === false)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Create New Gallery Failed. Cannot get listing data';
            return false;
        }
        $entity_listing_data = end($entity_listing_data);

        $field_array = ['account_id','title','entered','updated','listing'];

        $set_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$field_array))
            {
                $set_row[$parameter_item_index] = $parameter_item;
            }
        }

        if (empty($set_row['title']))
        {
            $set_row['title'] = $entity_listing_data['title'].' Gallery - '.date('d M, Y');
        }
        if (empty($parameter['option']['entered']))
        {
            $set_row['entered'] = date('Y-m-d H:i:s');
        }
        if (empty($parameter['option']['updated']))
        {
            $set_row['updated'] = date('Y-m-d H:i:s');
        }

        $set_row['listing'] = $parameter['option']['listing_id'];

        $entity_gallery_obj = new entity_gallery();
        $entity_gallery_result = $entity_gallery_obj->set(['row'=>[$set_row],'parameter'=>['field'=>$field_array]]);

        if ($entity_gallery_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        $entity_gallery_data = $entity_gallery_obj->get();

        if (count($entity_gallery_data) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
            return false;
        }
        else
        {
            $entity_gallery_data = end($entity_gallery_data);
            $entity_gallery_data['image'] = [];
            if (!empty($parameter['option']['image']))
            {
                $image_field_array = ['id','type','width','height','prefix','data'];

                foreach ($parameter['option']['image'] as $image_row_index=>$image_row)
                {
                    $set_row = [];
                    $relational_table_row = ['gallery_id'=>$entity_gallery_data['id'],'order'=>$image_row_index];
                    if (!empty($image_row['name']))
                    {
                        $relational_table_row['image_caption'] = $image_row['name'];
                        $relational_table_row['thumb_caption'] = $image_row['name'];
                    }
                    if (!empty($image_row['source_file']))
                    {
                        $image_size = @getimagesize($image_row['source_file']);
                        if ($image_size === false)
                        {
                            $this->message->warning =  __FILE__.'(line '.__LINE__.'): '.get_class($this).' unable to get image details for '.$image_row['source_file'];
                            continue;
                        }
                        $set_row['width'] = $image_size[0];
                        $set_row['height'] = $image_size[1];
                        if (isset($image_size['mime']))
                        {
                            switch ($image_size['mime'])
                            {
                                case 'image/gif':
                                    $set_row['type'] = 'GIF';
                                    break;
                                case 'image/png':
                                    $set_row['type'] = 'PNG';
                                    break;
                                case 'image/jpeg':
                                case 'image/pjpeg';
                                default:
                                    $set_row['type'] = 'JPG';
                            }
                        }
                        else
                        {
                            $image_size['mime'] = 'image/jpeg';
                            $set_row['type'] = 'JPG';
                        }

                        $image_file_content = file_get_contents($image_row['source_file']);
                        if (preg_match('/^data:/',$image_row['source_file']))
                        {
                            $set_row['data'] = $image_row['source_file'];
                        }
                        else
                        {
                            $set_row['data'] = 'data:'.$image_size['mime'].';base64,'.base64_encode($image_file_content);
                        }
                        $thumb_set_row = [
                            'width'=>280,
                            'height'=>233,
                            'type'=>'JPG'
                        ];
                        $source_image = imagecreatefromstring($image_file_content);
                        $target_image = imagecreatetruecolor($thumb_set_row['width'],$thumb_set_row['height']);

                        $thumb_ratio = 1.2;
                        $image_ratio = $set_row['width']/$set_row['height'];
                        $image_offset_x = 0;
                        $image_offset_y = 0;
                        $source_image_width = $set_row['width'];
                        $source_image_height = $set_row['height'];
                        if ($image_ratio > $thumb_ratio)
                        {
                            $image_offset_x = floor(($set_row['width'] - $set_row['height'] * $thumb_ratio)/2);
                            $source_image_width = $set_row['height'] * $thumb_ratio;
                        }
                        else
                        {
                            $image_offset_y = floor(($set_row['height'] - $set_row['width'] / $thumb_ratio)/2);
                            $source_image_height = $set_row['width'] / $thumb_ratio;
                        }

                        imagecopyresampled($target_image,$source_image,0,0,$image_offset_x,$image_offset_y,$thumb_set_row['width'], $thumb_set_row['height'],$source_image_width,$source_image_height);
                        imageinterlace($target_image,true);

                        ob_start();
                        imagejpeg($target_image, NULL, 80);
                        $thumb_file = ob_get_contents();
                        ob_get_clean();
                        $thumb_set_row['data'] = 'data:image/jpeg;base64,'.base64_encode($thumb_file);

                        imagedestroy($source_image);
                        imagedestroy($target_image);
                    }
                    $set_row['prefix'] = $parameter['option']['account_id'].'_';
                    $thumb_set_row['prefix'] = $parameter['option']['account_id'].'_';

                    foreach($image_row as $image_field_name=>$image_field_item)
                    {
                        if (in_array($image_field_name,$image_field_array))
                        {
                            $set_row[$image_field_name] = $image_field_item;
                        }
                    }

                    $entity_image_obj = new entity_gallery_image();
                    $set_parameter = ['row'=>[$set_row],'fields'=>array_keys($set_row)];
//print_r("\ntest point 3\n");
//$print_set_row = $set_row;
//unset($print_set_row['data']);
//print_r($set_parameter);
                    $entity_image_obj->set($set_parameter);
//print_r($entity_image_obj->message->display());exit;
                    unset($set_row);

                    if (empty($entity_image_obj->id_group))
                    {
                        $this->message->warning =  __FILE__.'(line '.__LINE__.'): '.get_class($this).' set failed';
                    }
                    else
                    {
                        $relational_table_row['image_id'] = end($entity_image_obj->id_group);
                        $entity_thumb_obj = new entity_gallery_image();
                        $set_thumb_parameter = ['row'=>[$thumb_set_row],'fields'=>array_keys($thumb_set_row)];
                        $entity_thumb_obj->set($set_thumb_parameter);
                        $relational_table_row['thumb_id'] = end($entity_thumb_obj->id_group);
                        unset($entity_thumb_obj);
                        unset($thumb_set_row);
                        $new_image_id_group[] = $relational_table_row['image_id'];
                        $set_relational_parameter = $entity_image_obj->parameter['relational_fields']['gallery'];
                        $set_relational_parameter['primary_key'] = $set_relational_parameter['source_id_field'];
                        $set_relational_parameter['fields'] = array_keys($relational_table_row);
                        $set_relational_parameter['row'] = [$relational_table_row];

                        $entity_image_obj->set($set_relational_parameter);
                    }
                }
            }
            $get_parameter = ['option'=>['id'=>$entity_gallery_data['id']]];
            $parameter['result'] = $this->select_gallery($get_parameter);
            return $parameter['result'];
        }
    }

    function insert_account_with_business(&$parameter = array())
    {
        if (empty($parameter['option']['username']) OR empty($parameter['option']['first_name']) OR empty($parameter['option']['last_name']))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'New Account Details not provided, username, first_name and last_name are mandatory';
            return false;
        }

        if (empty($parameter['option']['company']) OR empty($parameter['option']['latitude']) OR empty($parameter['option']['longitude']) OR empty($parameter['option']['category']))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'New Listing Details not provided. Title, category, latitude and longitude are mandatory fields';
            return false;
        }

        // Create Account
        $entity_account_obj = new entity_account();
        $account_field_array = ['username','first_name','last_name','password','company','address','address2','city','state','zip','image','banner','latitude','longitude','phone','fax','email','url','nickname','personal_message','entered','updated'];
        $set_account_parameter = array('row'=>array());

        $entity_account_check = new entity_account();
        $entity_account_check_param = array(
            'bind_param' => array(':username'=>$parameter['option']['username']),
            'where' => array('`username` = :username')
        );
        $entity_account_check->get($entity_account_check_param);
        if (count($entity_account_check->row) > 0)
        {
            $parameter = ['status'=>'REQUEST_DENIED','message'=>'Account already exist','username'=>$parameter['option']['username']];
            return false;
        }
        unset($entity_account_check);

        $set_account_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$account_field_array))
            {
                $set_account_row[$parameter_item_index] = $parameter_item;
            }
        }
        $set_account_row['importID'] = $this->api_id;
        $set_account_row['country'] = 'Australia';

        $entity_postcode_suburb_obj = new entity_postcode_suburb();
        $location_data = $entity_postcode_suburb_obj->get_location_from_geo(['latitude'=>$parameter['option']['latitude'],'longitude'=>$parameter['option']['longitude']]);
        if (!empty($location_data))
        {
            $api_location_log = PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_location_log.txt';
            if (!file_exists(dirname($api_location_log))) mkdir(dirname($api_location_log), 0755, true);
            file_put_contents($api_location_log,PHP_EOL.'Location Request: ['.date('D, d M Y H:i:s').']'.PHP_EOL.PHP_EOL,FILE_APPEND);

            if (empty($set_account_row['address'])) $set_account_row['address'] = $location_data['address'];
            if (!empty($set_account_row['city']))
            {
                if (strtolower($set_account_row['city']) != strtolower($location_data['suburb']))
                {
                    file_put_contents($api_location_log,'Suburb Inconsistent: Geocode suburb '.$location_data['suburb'].' - API Posted suburb '.$set_account_row['city'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_account_row['city'] = $location_data['suburb'];
            }

            if (!empty($set_account_row['state']))
            {
                if (strtolower($set_account_row['state']) != strtolower($location_data['state']))
                {
                    file_put_contents($api_location_log,'State Inconsistent: Geocode state '.$location_data['suburb'].' - API Posted state '.$set_account_row['state'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_account_row['state'] = $location_data['state'];
            }

            if (!empty($set_account_row['zip']))
            {
                if (strtolower($set_account_row['zip']) != strtolower($location_data['post_code']))
                {
                    file_put_contents($api_location_log,'Post Code Inconsistent: Geocode suburb '.$location_data['post_code'].' - API Posted suburb '.$set_account_row['zip'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_account_row['zip'] = $location_data['post_code'];
            }
        }
        if (empty($parameter['option']['entered']))
        {
            $set_account_row['entered'] = date('Y-m-d H:i:s');
        }
        if (empty($parameter['option']['updated']))
        {
            $set_account_row['updated'] = date('Y-m-d H:i:s');
        }
        $set_account_parameter['row'][] = $set_account_row;

        $account_insert_result = $entity_account_obj->set($set_account_parameter);
        if ($account_insert_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        if (count($entity_account_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
            return false;
        }
        $record_account = end($entity_account_obj->row);

        // Create Business Listing
        $entity_listing_obj = new entity_listing();
        $listing_field_array = ['latitude','longitude','category','abn','address','address2','city','state','zip','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','entered','updated'];
        if ($this->api_id == 10001 OR $this->api_id == 10003)
        {
            $listing_field_array = array_merge($listing_field_array,['cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount']);
        }
        $set_listing_parameter = array('row'=>array());

        $category_array = explode(',',$parameter['option']['category']);
        $category_name_array = array();
        $category_schema_array = array();
        foreach($category_array as $category_index=>$category)
        {
            if (preg_match('/^http/',$category) == 1) $category_schema_array[] = $category;
            else $category_name_array[] = $category;
        }
        $entity_category_param = array(
            'bind_param' => array(),
            'where' => array()
        );
        $category_where = array();
        if (!empty($category_schema_array))
        {
            $category_where[] = '`schema_itemtype` IN (:schema_'.implode(',:schema_',array_keys($category_schema_array)).')';
            foreach($category_schema_array as $category_schema_index=>$category_schema)
            {
                $entity_category_param['bind_param'][':schema_'.$category_schema_index] = $category_schema;
            }
        }
        if (!empty($category_name_array))
        {
            $category_where[] = '`name` IN (:name_'.implode(',:name_',array_keys($category_name_array)).')';
            foreach($category_name_array as $category_name_index=>$category_name)
            {
                $entity_category_param['bind_param'][':name_'.$category_name_index] = $category_name;
            }
        }

        if (empty($category_where))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Category provided is not in correct format, it should be either schema name or schema full url start with http://, multiple categories should be separate by comma';
            return false;
        }
        $entity_category_param['where'][] = implode(' AND ',$category_where);

        $entity_category_obj = new entity_category();
        $entity_category_obj->get($entity_category_param);
        if (empty($entity_category_obj->id_group))
        {
            // Error Handling, username, first_name or last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Cannot find category';
            return false;
        }

        $set_listing_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$listing_field_array))
            {
                $set_listing_row[$parameter_item_index] = $parameter_item;
            }
        }
        $set_listing_row['importID'] = $this->api_id;
        $set_listing_row['account_id'] = $record_account['id'];
        $set_listing_row['title'] = $parameter['option']['company'];
        if (isset($parameter['option']['zip'])) $set_listing_row['zip_code'] = $parameter['option']['zip'];
        $set_listing_row['status'] = 'A';
        $set_listing_row['bulked'] = 'y';
        if (!empty($parameter['option']['business_logo']))
        {
            $set_listing_row['image'] = $parameter['option']['business_logo'];
        }
        else
        {
            $set_listing_row['thumb_id'] = 0;
            $set_listing_row['image_id'] = 0;
        }
        if (!empty($parameter['option']['business_banner']))
        {
            $set_listing_row['banner'] = $parameter['option']['business_banner'];
        }
        else
        {
            $set_listing_row['banner_id'] = 0;
        }
        $set_listing_row['category'] = implode($entity_category_obj->id_group);

        if (!empty($location_data))
        {
            $set_listing_row['postcode_suburb_id'] = $location_data['id'];
            if (empty($set_listing_row['address'])) $set_listing_row['address'] = $location_data['address'];
            if (empty($set_listing_row['city'])) $set_listing_row['city'] = $location_data['suburb'];
            if (empty($set_listing_row['region'])) $set_listing_row['region'] = $location_data['region'];
            if (empty($set_listing_row['state'])) $set_listing_row['state'] = $location_data['state'];
            if (empty($set_listing_row['zip_code'])) $set_listing_row['zip_code'] = $location_data['post_code'];
        }
        if (empty($parameter['option']['entered']))
        {
            $set_listing_row['entered'] = date('Y-m-d H:i:s');
        }
        if (empty($parameter['option']['updated']))
        {
            $set_listing_row['updated'] = date('Y-m-d H:i:s');
        }
        $set_listing_parameter['row'][] = $set_listing_row;

        $listing_insert_result = $entity_listing_obj->set($set_listing_parameter);

        if ($listing_insert_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        if (count($entity_listing_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
            return false;
        }
        $record_listing = end($entity_listing_obj->row);
        $parameter['status'] = 'OK';
        $parameter['result'] = ['id'=>$record_account['id'],'token'=>$record_account['complementary_info'],'username'=>$record_account['username'],'password'=>$record_account['password'],'businesses'=>[['id'=>$record_listing['id'],'title'=>$record_listing['title'],'listing_page'=>'http://www.top4.com.au/business/'.$record_listing['friendly_url']]]];

        return $parameter['result'];
    }

    function insert_account_login_hash(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, account_id or listing_id not set
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Create New Login Failed. Id is mandatory.';
            return false;
        }
        $entity_account_obj = new entity_account($parameter['option']['id']);
        if (empty($entity_account_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account does not exist';
            return false;
        }

        $account_row = end($entity_account_obj->row);
        if ((empty($account_row['importID']) OR $account_row['importID'] != $this->api_id) AND $this->api_id != 10001)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to create login for this account';
            return false;
        }

        $set_result = $entity_account_obj->set_login_hash();
        if (empty($set_result))
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        $parameter['status'] = 'OK';
        $parameter['message'] = 'Token generated.';
        $parameter['result'] = $set_result;
        return $parameter['result'];
    }

    // Delete Functions
    function delete_account(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Account ID not provided';
            return false;
        }

        $entity_account_obj = new entity_account($parameter['option']['id']);
        if (empty($entity_account_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Account does not exist, it might have been deleted already';
            return false;
        }
        $account_row = end($entity_account_obj->row);

        if (empty($account_row['importID']) OR $account_row['importID'] != $this->api_id)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to delete this account';
            return false;
        }

        $delete_result = $entity_account_obj->delete();
        if ($delete_result === false)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database delete request failed, try again later';
            return false;
        }

        if ($delete_result == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Nothing deleted, database records not changed';
            return false;
        }
        else
        {
            $parameter['status'] = 'OK';
            $parameter['message'] = $delete_result.' record(s) deleted';
            $parameter['result'] = ['id'=>$account_row['id'],'username'=>$account_row['username']];
            return $parameter['result'];
        }
    }

    function delete_business(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Delete Business Listing ID not provided';
            return false;
        }

        $entity_listing_obj = new entity_listing($parameter['option']['id']);
        if (empty($entity_listing_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Business Listing does not exist, it might have been deleted already';
            return false;
        }
        $listing_row = end($entity_listing_obj->row);

        if (empty($listing_row['importID']) OR $listing_row['importID'] != $this->api_id)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to delete this listing';
            return false;
        }

        $delete_result = $entity_listing_obj->delete();
        if ($delete_result === false)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database delete request failed, try again later';
            return false;
        }

        if ($delete_result == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Nothing deleted, database records not changed';
            return false;
        }
        else
        {
            $parameter['status'] = 'OK';
            $parameter['message'] = $delete_result.' record(s) deleted';
            $parameter['result'] = ['id'=>$listing_row['id'],'title'=>$listing_row['title'],'listing_page'=>'http://www.top4.com.au/business/'.$listing_row['friendly_url']];
            return $parameter['result'];
        }
    }

    function delete_gallery(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Delete Gallery ID not provided';
            return false;
        }

        $entity_gallery_obj = new entity_gallery($parameter['option']['id']);
        if (empty($entity_gallery_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Gallery does not exist, it might have been deleted already';
            return false;
        }
        $entity_gallery_row = $entity_gallery_obj->get(['relational_fields'=>['image']]);
        $entity_gallery_row = end($entity_gallery_row);
        if (!empty($entity_gallery_row['image']))
        {
            $entity_image_obj = new entity_gallery_image($entity_gallery_row['image']);
            $relational_parameter = $entity_image_obj->parameter['relational_fields']['gallery'];
            $relational_parameter['primary_key'] = $relational_parameter['source_id_field'];
            $relational_parameter['relational_fields'] = [];

            $relational_result = $entity_image_obj->get($relational_parameter);

            $thumb_id_group = [];
            foreach ($relational_result as $relational_result_row_index=>$relational_result_row)
            {
                $thumb_id_group[] = $relational_result_row['thumb_id'];
            }
            $entity_thumb_obj = new entity_listing_image($thumb_id_group);
            $entity_thumb_obj->delete();

            $entity_image_obj->delete();
        }
        $delete_result = $entity_gallery_obj->delete();
        if ($delete_result === false)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database delete request failed, try again later';
            return false;
        }

        if ($delete_result == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Nothing deleted, database records not changed';
            return false;
        }
        else
        {

            $parameter['status'] = 'OK';
            $parameter['message'] = $delete_result.' record(s) deleted';
            $parameter['result'] = ['id'=>$entity_gallery_row['id'],'title'=>$entity_gallery_row['title'],'image'=>$entity_gallery_row['image']];
            return $parameter['result'];
        }

    }

    function delete_account_with_business(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Account ID not provided';
            return false;
        }

        $entity_account_obj = new entity_account($parameter['option']['id']);
        if (empty($entity_account_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Account does not exist, it might have been deleted already';
            return false;
        }
        $account_row = end($entity_account_obj->row);

        if (empty($account_row['importID']) OR $account_row['importID'] != $this->api_id)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to delete this account';
            return false;
        }

        $entity_listing_obj = new entity_listing();
        $entity_listing_param = array(
            'bind_param' => array(':account_id'=>$account_row['id']),
            'where' => array('`account_id` = :account_id','importID = '.$this->api_id)
        );
        $listing_rows = $entity_listing_obj->get($entity_listing_param);
        if (count($entity_listing_obj->id_group) > 0)
        {
            $entity_listing_obj->delete();
        }

        $delete_result = $entity_account_obj->delete();
        if ($delete_result === false)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database delete request failed, try again later';
            return false;
        }

        if ($delete_result == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Nothing deleted, database records not changed';
            return false;
        }
        else
        {
            $parameter['status'] = 'OK';
            $parameter['message'] = $delete_result.' record(s) deleted';
            $parameter['result'] = ['id'=>$account_row['id'],'username'=>$account_row['username']];
            if (!empty($listing_rows))
            {
                $parameter['result']['business'] = [];
                foreach ($listing_rows as $listing_row_index=>$listing_row)
                {
                    $parameter['result']['business'][] = ['id'=>$listing_row['id'],'title'=>$listing_row['title'],'listing_page'=>'http://www.top4.com.au/business/'.$listing_row['friendly_url']];
                }
            }
            return $parameter['result'];
        }
    }

    // Update Functions
    function update_account(&$parameter = array())
    {
        $set_account_row = array();
        $account_field_array = ['username','first_name','last_name','password','company','address','address2','city','state','zip','image','banner','latitude','longitude','phone','fax','email','url','nickname','personal_message','entered','updated'];


        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Account ID not provided';
            return false;
        }

        if (isset($parameter['option']['first_name']) AND empty($parameter['option']['first_name']))
        {
            // Error Handling, first_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account first_name is mandatory, cannot update to empty';
            return false;
        }

        if (isset($parameter['option']['last_name']) AND empty($parameter['option']['last_name']))
        {
            // Error Handling, last_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account last_name is mandatory, cannot update to empty';
            return false;
        }

        if (isset($parameter['option']['password']) AND empty($parameter['option']['password']))
        {
            // Error Handling, first_name not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account password cannot be empty';
            return false;
        }

        if (isset($parameter['option']['username']))
        {
            if (empty($parameter['option']['username']))
            {
                // Error Handling, username not provided
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Account username is mandatory, cannot update to empty';
                return false;
            }

            $entity_account_check = new entity_account();
            $entity_account_check_param = array(
                'bind_param' => array(':username'=>$parameter['option']['username'],':id'=>$parameter['option']['id']),
                'where' => array('`username` = :username','`id` != :id')
            );
            $entity_account_check->get($entity_account_check_param);
            if (count($entity_account_check->row) > 0)
            {
                $parameter = ['status'=>'REQUEST_DENIED','message'=>'Account already exist','username'=>$parameter['option']['username']];
                return false;
            }
            unset($entity_account_check);
        }

        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$account_field_array))
            {
                $set_account_row[$parameter_item_index] = $parameter_item;
            }
        }
        if (!empty($parameter['option']['latitude']) AND !empty($parameter['option']['longitude']))
        {
            $entity_postcode_suburb_obj = new entity_postcode_suburb();
            $location_data = $entity_postcode_suburb_obj->get_location_from_geo(['latitude'=>$parameter['option']['latitude'],'longitude'=>$parameter['option']['longitude']]);
            if (!empty($location_data))
            {
                $api_location_log = PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_location_log.txt';
                if (!file_exists(dirname($api_location_log))) mkdir(dirname($api_location_log), 0755, true);
                file_put_contents($api_location_log,PHP_EOL.'Location Request: ['.date('D, d M Y H:i:s').']'.PHP_EOL.PHP_EOL,FILE_APPEND);

                if (empty($set_account_row['address'])) $set_account_row['address'] = $location_data['address'];
                if (!empty($set_account_row['city']))
                {
                    if (strtolower($set_account_row['city']) != strtolower($location_data['suburb']))
                    {
                        file_put_contents($api_location_log,'Suburb Inconsistent: Geocode suburb '.$location_data['suburb'].' - API Posted suburb '.$set_account_row['city'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_account_row['city'] = $location_data['suburb'];
                }

                if (!empty($set_account_row['state']))
                {
                    if (strtolower($set_account_row['state']) != strtolower($location_data['state']))
                    {
                        file_put_contents($api_location_log,'State Inconsistent: Geocode state '.$location_data['state'].' - API Posted state '.$set_account_row['state'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_account_row['state'] = $location_data['state'];
                }

                if (!empty($set_account_row['zip']))
                {
                    if (strtolower($set_account_row['zip']) != strtolower($location_data['post_code']))
                    {
                        file_put_contents($api_location_log,'Post Code Inconsistent: Geocode suburb '.$location_data['post_code'].' - API Posted suburb '.$set_account_row['zip'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_account_row['zip'] = $location_data['post_code'];
                }
            }
        }

        $entity_account_obj = new entity_account($parameter['option']['id']);
        if (empty($entity_account_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account does not exist, please use insert_account instead';
            return false;
        }
        $account_row = end($entity_account_obj->row);

        if (empty($account_row['importID']) OR $account_row['importID'] != $this->api_id)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to update this account';
            return false;
        }
        unset($account_row);

        if (empty($parameter['option']['updated']))
        {
            $set_account_row['updated'] = date('Y-m-d H:i:s');
        }
        $account_update_result = $entity_account_obj->update($set_account_row);

        if ($account_update_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database update request failed, try again later';
            return false;
        }

        if ($account_update_result == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'All values are same as before, nothing updated';
            return false;
        }
        else
        {
            $entity_account_obj->get();
            $record = end($entity_account_obj->row);
            $parameter['status'] = 'OK';
            $parameter['message'] = $account_update_result.' record(s) updated';
            $parameter['result'] = ['id'=>$record['id'],'token'=>$record['complementary_info'],'username'=>$record['username']];
            return $parameter['result'];
        }
    }

    function update_business(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Listing ID not provided';
            return false;
        }

        if (isset($parameter['option']['title']) AND empty($parameter['option']['title']))
        {
            // Error Handling, title not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Listing title is mandatory, cannot update to empty';
            return false;
        }

        if (isset($parameter['option']['latitude']) AND empty($parameter['option']['latitude']))
        {
            // Error Handling, latitude not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Listing latitude is mandatory, cannot update to empty';
            return false;
        }

        if (isset($parameter['option']['longitude']) AND empty($parameter['option']['longitude']))
        {
            // Error Handling, longitude not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Listing longitude is mandatory, cannot update to empty';
            return false;
        }

        $listing_field_array = ['account_id','title','latitude','longitude','category','abn','address','address2','city','state','zip','image','banner','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','entered','updated'];
        if ($this->api_id == 10001 OR $this->api_id == 10003)
        {
            $listing_field_array = array_merge($listing_field_array,['cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount']);
        }
        $set_listing_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$listing_field_array) AND !isset($set_listing_row[$parameter_item_index]))
            {
                $set_listing_row[$parameter_item_index] = $parameter_item;
            }
        }

        if (isset($parameter['option']['status']) AND !in_array($parameter['option']['status'],['A','S']))
        {
            $set_listing_row['status'] = 'S';
        }

        if (!empty($parameter['option']['latitude']) AND !empty($parameter['option']['longitude']))
        {
            $entity_postcode_suburb_obj = new entity_postcode_suburb();
            $location_data = $entity_postcode_suburb_obj->get_location_from_geo(['latitude'=>$parameter['option']['latitude'],'longitude'=>$parameter['option']['longitude']]);
            if (!empty($location_data))
            {
                $api_location_log = PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_location_log.txt';
                if (!file_exists(dirname($api_location_log))) mkdir(dirname($api_location_log), 0755, true);
                file_put_contents($api_location_log,PHP_EOL.'Location Request: ['.date('D, d M Y H:i:s').']'.PHP_EOL.PHP_EOL,FILE_APPEND);

                $set_listing_row['postcode_suburb_id'] = $location_data['id'];
                if (empty($set_listing_row['address'])) $set_listing_row['address'] = $location_data['address'];
                if (!empty($set_listing_row['city']))
                {
                    if (strtolower($set_listing_row['city']) != strtolower($location_data['suburb']))
                    {
                        file_put_contents($api_location_log,'Suburb Inconsistent: Geocode suburb '.$location_data['suburb'].' - API Posted suburb '.$set_listing_row['city'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_listing_row['city'] = $location_data['suburb'];
                }

                if (empty($set_listing_row['region'])) $set_listing_row['region'] = $location_data['region'];

                if (!empty($set_listing_row['state']))
                {
                    if (strtolower($set_listing_row['state']) != strtolower($location_data['state']))
                    {
                        file_put_contents($api_location_log,'State Inconsistent: Geocode state '.$location_data['state'].' - API Posted state '.$set_listing_row['state'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_listing_row['state'] = $location_data['state'];
                }

                if (!empty($set_listing_row['zip_code']))
                {
                    if (strtolower($set_listing_row['zip_code']) != strtolower($location_data['post_code']))
                    {
                        file_put_contents($api_location_log,'Post Code Inconsistent: Geocode suburb '.$location_data['post_code'].' - API Posted suburb '.$set_listing_row['zip_code'].PHP_EOL,FILE_APPEND);
                    }
                }
                else
                {
                    $set_listing_row['zip_code'] = $location_data['post_code'];
                }
            }
        }

        $entity_listing_obj = new entity_listing($parameter['option']['id']);
        if (empty($entity_listing_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Listing does not exist, please use insert_business instead';
            return false;
        }
        $listing_row = end($entity_listing_obj->row);

        if (empty($listing_row['importID']) OR $listing_row['importID'] != $this->api_id)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to update this listing';
            return false;
        }
        unset($listing_row);

        if (isset($parameter['option']['category']))
        {
            if (empty($parameter['option']['category']))
            {
                // Error Handling, category empty
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Category is mandatory field, cannot be set to empty';
                return false;
            }
            $category_array = explode(',',$parameter['option']['category']);
            $category_name_array = array();
            $category_schema_array = array();
            foreach($category_array as $category_index=>$category)
            {
                if (preg_match('/^http/',$category) == 1) $category_schema_array[] = $category;
                else $category_name_array[] = $category;
            }
            $entity_category_param = array(
                'bind_param' => array(),
                'where' => array()
            );
            $category_where = array();
            if (!empty($category_schema_array))
            {
                $category_where[] = '`schema_itemtype` IN (:schema_'.implode(',:schema_',array_keys($category_schema_array)).')';
                foreach($category_schema_array as $category_schema_index=>$category_schema)
                {
                    $entity_category_param['bind_param'][':schema_'.$category_schema_index] = $category_schema;
                }
            }
            if (!empty($category_name_array))
            {
                $category_where[] = '`name` IN (:name_'.implode(',:name_',array_keys($category_name_array)).')';
                foreach($category_name_array as $category_name_index=>$category_name)
                {
                    $entity_category_param['bind_param'][':name_'.$category_name_index] = $category_name;
                }
            }

            if (empty($category_where))
            {
                // Error Handling, username, first_name or last_name not provided
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Category provided is not in correct format, it should be either schema name or schema full url start with http://, multiple categories should be separate by comma';
                return false;
            }
            $entity_category_param['where'][] = implode(' AND ',$category_where);

            $entity_category_obj = new entity_category();
            $entity_category_obj->get($entity_category_param);
            if (empty($entity_category_obj->id_group))
            {
                // Error Handling, category provided does not match database records
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Category does not exist or in wrong format';
                return false;
            }
            $set_listing_row['category'] = implode($entity_category_obj->id_group);
        }

        if (empty($parameter['option']['updated']))
        {
            $set_listing_row['updated'] = date('Y-m-d H:i:s');
        }
        $listing_update_result = $entity_listing_obj->update($set_listing_row);

        if ($listing_update_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return false;
        }

        if ($listing_update_result == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'All values are same as before, nothing updated';
            return false;
        }
        else
        {
            $entity_listing_obj->get();
            $record = end($entity_listing_obj->row);
            $parameter['status'] = 'OK';
            $parameter['message'] = $listing_update_result.' record(s) updated';
            $parameter['result'] = ['id'=>$record['id'],'title'=>$record['title'],'listing_page'=>'https://www.top4.com.au/business/'.$record['friendly_url']];
            return $parameter['result'];
        }
    }

    function update_gallery(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not set
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Gallery Failed. Gallery id is mandatory field';
            return false;
        }
        $entity_gallery_obj = new entity_gallery($parameter['option']['id']);
        if (empty($entity_gallery_obj->id_group))
        {
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Gallery Failed. Gallery does not exist, it might have been deleted';
            return false;
        }
        $entity_gallery_data = $entity_gallery_obj->row;
        $entity_gallery_data = end($entity_gallery_data);

        $entity_account_obj = new entity_account($entity_gallery_data['account_id']);
        $entity_account_data = $entity_account_obj->row;
        if (is_array($entity_account_data))
        {
            $entity_account_data = end($entity_account_data);
        }
//        if (empty($entity_account_data['importID']) OR $entity_account_data['importID'] != $this->api_id)
//        {
//            $parameter['status'] = 'REQUEST_DENIED';
//            $parameter['message'] = 'Current API User does not have the permission to update this account';
//            return false;
//        }

        $set_row = array();

        $field_array = ['title','entered','updated'];

        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$field_array))
            {
                $set_row[$parameter_item_index] = $parameter_item;
            }
        }

        if (empty($set_row) AND empty($parameter['option']['image']))
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'All values are same as before, nothing updated';
            return false;
        }

        if (empty($set_row['updated']))
        {
            $set_row['updated'] = date('Y-m-d H:i:s');
        }

        $entity_gallery_result = $entity_gallery_obj->update(['row'=>[$set_row],'parameter'=>['field'=>array_keys($set_row)]]);

        if (!empty($parameter['option']['image']))
        {
            $entity_gallery_data_current = $entity_gallery_obj->fetch_value();
            $entity_gallery_data_current = end($entity_gallery_data_current);

            $current_image_id_group = explode(',',$entity_gallery_data_current['image']);
            $delete_image_id_group = [];

            $sort_image = [];
            $update_image_row = [];

            if (!empty($entity_gallery_data_current['image_row']))
            {
                foreach ($entity_gallery_data_current['image_row'] as $current_image_row_index=>$current_image_row)
                {
                    $sort_image['id_'.$current_image_row['id']] =  $current_image_row['order'];
                    $update_image_row['id_'.$current_image_row['id']] = ['id'=>$current_image_row['id']];
                }
            }
            foreach ($update_image_row as $image_row_index=>$image_row)
            {
                if (!empty($image_row['delete']))
                {
                    unset($update_image_row[$image_row_index]);
                    unset($sort_image[$image_row_index]);
                    if(!empty($image_row['id'])) $delete_image_id_group[] = $image_row['id'];
                }
            }
            array_multisort($sort_image, $update_image_row);
            unset($sort_image);

            $sort_set_image_row = [];
            $set_image_row = $parameter['option']['image'];
            $set_image_row_counter = count($set_image_row)+count($update_image_row);
            foreach ($set_image_row as $image_row_index=>&$image_row)
            {
                if (!isset($image_row['order']))
                {
                    $sort_set_image_row[] = $set_image_row_counter;
                    if (!isset($image_row['id']))
                    {
                        // new image goes to the end of gallery by default
                        $image_row['order'] = $set_image_row_counter;
                    }
                    $set_image_row_counter++;
                }
                else
                {
                    $sort_set_image_row[] = $image_row['order'];
                }
            }
            array_multisort($sort_set_image_row, $set_image_row);
            unset($sort_set_image_row);

            foreach ($set_image_row as $image_row_index=>$image_row)
            {
                if (isset($image_row['order']))
                {
                    if (isset($image_row['id']) AND isset($update_image_row['id_'.$image_row['id']]))
                    {
                        unset($update_image_row['id_'.$image_row['id']]);
                    }
                    array_splice($update_image_row, $image_row['order'],0,[$image_row]);
                }
                else
                {
                    if (isset($image_row['id']) AND isset($update_image_row['id_'.$image_row['id']]))
                    {
                        $update_image_row['id_'.$image_row['id']] = $image_row;
                    }
                    else
                    {
                        $update_image_row[] = $image_row;
                    }
                }
            }

            $entity_image_obj = new entity_gallery_image($delete_image_id_group);
            $entity_image_obj->delete();

            $new_order = 0;
            foreach ($update_image_row as $image_row_index=>$image_row)
            {
                $relational_set_row = [];
                if (!empty($image_row['id']))
                {
                    if (!in_array($image_row['id'],$current_image_id_group))
                    {
                        $parameter['message'] .= PHP_EOL.'Image ['.$image_row['id'].'] is not in current gallery, cannot perform update';
                        continue;
                    }
                    $relational_set_row['image_id'] = $image_row['id'];
                }
                else
                {
                    if (empty($image_row['source_file']))
                    {
                        $parameter['message'] .= PHP_EOL.'Cannot insert new image without source_file: '.json_encode($image_row);
                        continue;
                    }
                    $image_size = @getimagesize($image_row['source_file']);
                    if ($image_size !== false)
                    {
                        $set_row = [
                            'width'=>$image_size[0],
                            'height'=>$image_size[1]
                        ];
                        if (isset($image_size['mime']))
                        {
                            switch ($image_size['mime'])
                            {
                                case 'image/gif':
                                    $set_row['type'] = 'GIF';
                                    break;
                                case 'image/png':
                                    $set_row['type'] = 'PNG';
                                    break;
                                case 'image/jpeg':
                                case 'image/pjpeg';
                                default:
                                    $set_row['type'] = 'JPG';
                            }
                        }
                        else
                        {
                            $image_size['mime'] = 'image/jpeg';
                        }
                        if (preg_match('/^data:/',$image_row['source_file']))
                        {
                            $set_row['data'] = $image_row['source_file'];
                        }
                        else
                        {
                            $image_file_content = file_get_contents($image_row['source_file']);
                            $set_row['data'] = 'data:'.$image_size['mime'].';base64,'.base64_encode($image_file_content);
                        }

                        $thumb_set_row = [
                            'width'=>280,
                            'height'=>233,
                            'type'=>'JPG'
                        ];
                        $source_image = imagecreatefromstring($image_file_content);
                        $target_image = imagecreatetruecolor($thumb_set_row['width'],$thumb_set_row['height']);

                        $thumb_ratio = 1.2;
                        $image_ratio = $set_row['width']/$set_row['height'];
                        $image_offset_x = 0;
                        $image_offset_y = 0;
                        $source_image_width = $set_row['width'];
                        $source_image_height = $set_row['height'];
                        if ($image_ratio > $thumb_ratio)
                        {
                            $image_offset_x = floor(($set_row['width'] - $set_row['height'] * $thumb_ratio)/2);
                            $source_image_width = $set_row['height'] * $thumb_ratio;
                        }
                        else
                        {
                            $image_offset_y = floor(($set_row['height'] - $set_row['width'] / $thumb_ratio)/2);
                            $source_image_height = $set_row['width'] / $thumb_ratio;
                        }

                        imagecopyresampled($target_image,$source_image,0,0,$image_offset_x,$image_offset_y,$thumb_set_row['width'], $thumb_set_row['height'],$source_image_width,$source_image_height);
                        imageinterlace($target_image,true);

                        ob_start();
                        imagejpeg($target_image, NULL, 80);
                        $thumb_file = ob_get_contents();
                        ob_get_clean();
                        $thumb_set_row['data'] = 'data:image/jpeg;base64,'.base64_encode($thumb_file);

                        imagedestroy($source_image);
                        imagedestroy($target_image);

                        $set_row['prefix'] = $entity_gallery_data['account_id'].'_';
                        $thumb_set_row['prefix'] = $entity_gallery_data['account_id'].'_';

                        $entity_image_obj = new entity_gallery_image();
                        $entity_image_obj->set(['row'=>[$set_row],'fields'=>array_keys($set_row)]);
                        if (empty($entity_image_obj->id_group))
                        {
                            $parameter['message'] .= PHP_EOL.'Insert new image failed: '.json_encode($image_row);
                            continue;
                        }
                        $entity_thumb_obj = new entity_gallery_image();
                        $set_thumb_parameter = ['row'=>[$thumb_set_row],'fields'=>array_keys($thumb_set_row)];
                        $entity_thumb_obj->set($set_thumb_parameter);

                        $relational_set_row['image_id'] = end($entity_image_obj->id_group);
                        $relational_set_row['thumb_id'] = end($entity_thumb_obj->id_group);
                        unset($set_row);
                    }
                }

                $entity_image_obj = new entity_gallery_image();

                $set_relational_parameter = $entity_image_obj->parameter['relational_fields']['gallery'];
                $set_relational_parameter['primary_key'] = $set_relational_parameter['source_id_field'];

                if (!empty($image_row['name']))
                {
                    $relational_set_row['image_caption'] = $image_row['name'];
                    $relational_set_row['thumb_caption'] = $image_row['name'];
                }
                $relational_set_row['order'] = $new_order;

                if (!empty($image_row['id']))
                {
                    $entity_image_obj->id_group = $this->format->id_group($relational_set_row['image_id']);
                    $entity_image_obj->get();

                    $set_relational_parameter['where'] = ['gallery_id = '.$entity_gallery_data['id']];
                    $set_relational_parameter['fields'] = array_keys($relational_set_row);
                    $result = $entity_image_obj->update($relational_set_row, $set_relational_parameter);
                }
                else
                {
                    $relational_set_row['gallery_id'] = $entity_gallery_data['id'];
                    $set_relational_parameter['fields'] = array_keys($relational_set_row);
                    $set_relational_parameter['row'] = [$relational_set_row];
                    $result = $entity_image_obj->set($set_relational_parameter);
                }

                $new_order++;
            }
        }


        $get_parameter = ['option'=>['id'=>$entity_gallery_data['id']]];
        $parameter['result'] = $this->select_gallery($get_parameter);
        return $parameter['result'];
    }

    function update_account_with_business(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Account ID not provided';
            return false;
        }

        foreach(['username','first_name','last_name','company','latitude','longitude','category'] as $index=>$mandatory_field)
        {
            if (isset($parameter['option'][$mandatory_field]) AND empty($parameter['option'][$mandatory_field]))
            {
                // Error Handling, mandatory field not provided
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Listing '.$mandatory_field.' is mandatory, cannot update to empty';
                return false;
            }
        }

        if (isset($parameter['option']['username']))
        {
            $entity_account_check = new entity_account();
            $entity_account_check_param = array(
                'bind_param' => array(':username'=>$parameter['option']['username'],':id'=>$parameter['option']['id']),
                'where' => array('`username` = :username','`id` != :id')
            );
            $entity_account_check->get($entity_account_check_param);
            if (count($entity_account_check->row) > 0)
            {
                $parameter = ['status'=>'REQUEST_DENIED','message'=>'Account already exist','username'=>$parameter['option']['username']];
                return false;
            }
            unset($entity_account_check);
        }

        $account_field_array = ['username','first_name','last_name','password','company','address','address2','city','state','zip','image','banner','latitude','longitude','phone','fax','email','url','nickname','personal_message','entered','updated'];

        $set_account_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$account_field_array))
            {
                $set_account_row[$parameter_item_index] = $parameter_item;
            }
        }
        $entity_postcode_suburb_obj = new entity_postcode_suburb();
        $location_data = $entity_postcode_suburb_obj->get_location_from_geo(['latitude'=>$parameter['option']['latitude'],'longitude'=>$parameter['option']['longitude']]);
        if (!empty($location_data))
        {
            $api_location_log = PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_location_log.txt';
            if (!file_exists(dirname($api_location_log))) mkdir(dirname($api_location_log), 0755, true);
            file_put_contents($api_location_log,PHP_EOL.'Location Request: ['.date('D, d M Y H:i:s').']'.PHP_EOL.PHP_EOL,FILE_APPEND);

            if (empty($set_account_row['address'])) $set_account_row['address'] = $location_data['address'];
            if (!empty($set_account_row['city']))
            {
                if (strtolower($set_account_row['city']) != strtolower($location_data['suburb']))
                {
                    file_put_contents($api_location_log,'Suburb Inconsistent: Geocode suburb '.$location_data['suburb'].' - API Posted suburb '.$set_account_row['city'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_account_row['city'] = $location_data['suburb'];
            }

            if (!empty($set_account_row['state']))
            {
                if (strtolower($set_account_row['state']) != strtolower($location_data['state']))
                {
                    file_put_contents($api_location_log,'State Inconsistent: Geocode state '.$location_data['suburb'].' - API Posted state '.$set_account_row['state'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_account_row['state'] = $location_data['state'];
            }

            if (!empty($set_account_row['zip']))
            {
                if (strtolower($set_account_row['zip']) != strtolower($location_data['post_code']))
                {
                    file_put_contents($api_location_log,'Post Code Inconsistent: Geocode suburb '.$location_data['post_code'].' - API Posted suburb '.$set_account_row['zip'].PHP_EOL,FILE_APPEND);
                }
            }
            else
            {
                $set_account_row['zip'] = $location_data['post_code'];
            }
        }

        $entity_account_obj = new entity_account($parameter['option']['id']);
        if (empty($entity_account_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account does not exist, please use insert_account instead';
            return false;
        }
        $record_account = end($entity_account_obj->row);
        if (empty($record_account['importID']) OR $record_account['importID'] != $this->api_id)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Current API User does not have the permission to update this account';
            return false;
        }
        if (empty($parameter['option']['updated']))
        {
            $set_account_row['updated'] = date('Y-m-d H:i:s');
        }
        $account_update_result = $entity_account_obj->update($set_account_row);

        if ($account_update_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database account update request failed, try again later';
            return false;
        }

        if ($account_update_result == 0)
        {
            $parameter['message'] = 'Account record is not updated, values are same as before. ';
        }
        else
        {
            $parameter['message'] = $account_update_result.' account record(s) updated. ';
            $entity_account_obj->get();
            $record_account = end($entity_account_obj->row);
        }

        $entity_listing_obj = new entity_listing();
        $entity_listing_param = array(
            'bind_param' => array(':account_id'=>$record_account['id']),
            'where' => array('`account_id` = :account_id','importID = '.$this->api_id)
        );
        $entity_listing_obj->get($entity_listing_param);
        if (count($entity_listing_obj->row) == 0)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'This account has no listing imported. Please use insert_listing function instead.';
            return false;
        }
        if (count($entity_listing_obj->row) > 1)
        {
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'This account has more than 1 listing imported. Please use update_listing function instead.';
            return false;
        }

        $listing_field_array = ['latitude','longitude','abn','address','address2','city','state','zip','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','entered','updated'];
        if ($this->api_id == 10001 OR $this->api_id == 10003)
        {
            $listing_field_array = array_merge($listing_field_array,['cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount']);
        }
        $set_listing_row = array();
        foreach($parameter['option'] as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$listing_field_array))
            {
                $set_listing_row[$parameter_item_index] = $parameter_item;
            }
        }
        if (isset($parameter['option']['zip'])) $set_listing_row['zip_code'] = $parameter['option']['zip'];
        if (isset($parameter['option']['business_logo']))
        {
            $set_listing_row['image'] = $parameter['option']['business_logo'];
        }
        if (isset($parameter['option']['business_banner']))
        {
            $set_listing_row['banner'] = $parameter['option']['business_banner'];
        }

        if (isset($parameter['option']['status']) AND !in_array($parameter['option']['status'],['A','S']))
        {
            $set_listing_row['status'] = 'S';
        }
        if (isset($parameter['option']['company']))
        {
            $set_listing_row['title'] = $parameter['option']['company'];
        }
        if (isset($parameter['option']['category']))
        {
            if (empty($parameter['option']['category']))
            {
                // Error Handling, category empty
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Category is mandatory field, cannot be set to empty';
                return false;
            }
            $category_array = explode(',',$parameter['option']['category']);
            $category_name_array = array();
            $category_schema_array = array();
            foreach($category_array as $category_index=>$category)
            {
                if (preg_match('/^http/',$category) == 1) $category_schema_array[] = $category;
                else $category_name_array[] = $category;
            }
            $entity_category_param = array(
                'bind_param' => array(),
                'where' => array()
            );
            $category_where = array();
            if (!empty($category_schema_array))
            {
                $category_where[] = '`schema_itemtype` IN (:schema_'.implode(',:schema_',array_keys($category_schema_array)).')';
                foreach($category_schema_array as $category_schema_index=>$category_schema)
                {
                    $entity_category_param['bind_param'][':schema_'.$category_schema_index] = $category_schema;
                }
            }
            if (!empty($category_name_array))
            {
                $category_where[] = '`name` IN (:name_'.implode(',:name_',array_keys($category_name_array)).')';
                foreach($category_name_array as $category_name_index=>$category_name)
                {
                    $entity_category_param['bind_param'][':name_'.$category_name_index] = $category_name;
                }
            }

            if (empty($category_where))
            {
                // Error Handling, username, first_name or last_name not provided
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Category provided is not in correct format, it should be either schema name or schema full url start with http://, multiple categories should be separate by comma';
                return false;
            }
            $entity_category_param['where'][] = implode(' AND ',$category_where);

            $entity_category_obj = new entity_category();
            $entity_category_obj->get($entity_category_param);
            if (empty($entity_category_obj->id_group))
            {
                // Error Handling, category provided does not match database records
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Category does not exist or in wrong format';
                return false;
            }
            $set_listing_row['category'] = implode($entity_category_obj->id_group);
        }
        if (!empty($location_data))
        {
            $set_listing_row['postcode_suburb_id'] = $location_data['id'];
            if (empty($set_listing_row['address'])) $set_listing_row['address'] = $location_data['address'];
            if (empty($set_listing_row['city'])) $set_listing_row['city'] = $location_data['suburb'];
            if (empty($set_listing_row['region'])) $set_listing_row['region'] = $location_data['region'];
            if (empty($set_listing_row['state'])) $set_listing_row['state'] = $location_data['state'];
            if (empty($set_listing_row['zip_code'])) $set_listing_row['zip_code'] = $location_data['post_code'];
        }
        if (empty($parameter['option']['updated']))
        {
            $set_listing_row['updated'] = date('Y-m-d H:i:s');
        }
        $listing_update_result = $entity_listing_obj->update($set_listing_row);

        if ($listing_update_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database update listing request failed, try again later';
            return false;
        }

        if ($listing_update_result == 0)
        {
            if ($account_update_result == 0)
            {
                $parameter['status'] = 'ZERO_RESULTS';
                $parameter['message'] = 'All values are same as before, nothing updated';
                return false;
            }
            else
            {
                $parameter['message'] .= 'Business listing record is not updated, values are same as before.';
            }
        }
        else
        {
            $parameter['message'] .= $listing_update_result.' business listing record(s) updated. ';
        }
        $entity_listing_obj->get();
        $record_listing = end($entity_listing_obj->row);
        $parameter['status'] = 'OK';
        $parameter['result'] = ['id'=>$record_account['id'],'token'=>$record_account['complementary_info'],'username'=>$record_account['username'],'businesses'=>[['id'=>$record_listing['id'],'title'=>$record_listing['title'],'listing_page'=>'http://www.top4.com.au/business/'.$record_listing['friendly_url']]]];
        return $parameter['result'];
    }


    // Select Functions
    function select_account(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Update Account ID not provided';
            return false;
        }
        $entity_account_obj = new entity_account($parameter['option']['id']);
        if (empty($entity_account_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Account not available';
            return false;
        }
        $record = $entity_account_obj->get();
        $record = end($record);
        $parameter['status'] = 'OK';
        $parameter['result'] = ['id'=>$record['id'],'token'=>$record['complementary_info']];

        $return_field_list = ['username','first_name','last_name','company','address','address2','city','state','zip','image','banner','latitude','longitude','phone','fax','email','url','nickname','personal_message'];
        foreach ($record as $field_name=>$field_value)
        {
            if (in_array($field_name,$return_field_list))
            {
                $parameter['result'][$field_name] = $field_value;
            }
        }
        return $parameter['result'];
    }

    function select_business(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Select Listing ID not provided';
            return false;
        }

        $entity_listing_obj = new entity_listing($parameter['option']['id']);
        if (empty($entity_listing_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Listing not available';
            return false;
        }
        $get_parameter = [];
        if ($this->api_id == 10001 OR $this->api_id == 10003)
        {
            $get_parameter['fields'] = ['id','title','friendly_url','abn','address','address2','city','region','state','zip_code','account_id','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','bulked','importID','thumb_id','image_id','banner_id','category','updated','entered','cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount'];
        }

        $record = $entity_listing_obj->get($get_parameter);
        $record = end($record);

        $parameter['status'] = 'OK';
        $parameter['result'] = [];

        $return_field_list = ['id','title','latitude','longitude','category','account_id','abn','address','address2','city','state','zip','image','banner','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords'];
        if ($this->api_id == 10001 OR $this->api_id == 10003)
        {
            $return_field_list = array_merge($return_field_list,['cd_plan_name','cd_plan_period','cd_plan_transaction_id','cd_plan_transaction_amount','status']);
        }

        foreach ($record as $field_name=>$field_value)
        {
            if (in_array($field_name,$return_field_list))
            {
                $parameter['result'][$field_name] = $field_value;
            }
        }
        $parameter['result']['listing_page'] = URI_SITE_BASE.'business/'.$record['friendly_url'];
        return $parameter['result'];
    }

    function select_gallery(&$parameter = array())
    {
        if (empty($parameter['option']['id']))
        {
            // Error Handling, id not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Select Gallery ID not provided';
            return false;
        }

        $entity_gallery_obj = new entity_gallery($parameter['option']['id']);
        if (empty($entity_gallery_obj->id_group))
        {
            // Error Handling, category provided does not match database records
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Gallery does not exist, it might have been deleted';
            return false;
        }
        $record = $entity_gallery_obj->get(['relational_fields'=>['image']]);
        $record = end($record);

        $parameter['status'] = 'OK';
        $parameter['result'] = [];
        $return_field_list = ['id','account_id','title'];
        foreach ($record as $field_name=>$field_value)
        {
            if (in_array($field_name,$return_field_list))
            {
                $parameter['result'][$field_name] = $field_value;
            }
        }

        $parameter['result']['image'] = [];
        if (!empty($record['image']))
        {
            $entity_image_obj = new entity_gallery_image($record['image']);
            $entity_image_data = $entity_image_obj->get();

            $relational_parameter = $entity_image_obj->parameter['relational_fields']['gallery'];
            $relational_parameter['primary_key'] = $relational_parameter['source_id_field'];
            $relational_parameter['relational_fields'] = [];

            $relational_result = $entity_image_obj->get($relational_parameter);

            if (!empty($relational_result))
            {
                foreach ($relational_result as $relational_result_row_index=>$relational_result_row)
                {
                    $result_image_row = [
                        'id'=>$relational_result_row['image_id'],
                        'name'=>$relational_result_row['image_caption'],
                        'thumb_id'=>$relational_result_row['thumb_id'],
                        'image_uri'=>$entity_image_data['id_'.$relational_result_row['image_id']]['file_uri']
                    ];

                    if (!empty($result_image_row['thumb_id']) AND !empty($result_image_row['image_uri']))
                    {
                        $result_image_row['thumb_uri'] = str_replace($result_image_row['id'],$result_image_row['thumb_id'],$result_image_row['image_uri']);
                    }
                    $parameter['result']['image'][$relational_result_row['order']] = $result_image_row;
                }
                ksort($parameter['result']['image']);
            }
        }
        return $parameter['result'];
    }

    function select_account_by_username(&$parameter = array())
    {
        if (empty($parameter['option']['username']))
        {
            // Error Handling, username not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'username not provided';
            return false;
        }

        $entity_account_obj = new entity_account();
        $entity_account_param = array(
            'bind_param' => array(':username'=>$parameter['option']['username']),
            'where' => array('`username` = :username')
        );
        $result_row = $entity_account_obj->get($entity_account_param);
        if (empty($result_row))
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Account not available';
            return false;
        }
        $record = end($result_row);
        $parameter['status'] = 'OK';
        $parameter['result'] = ['id'=>$record['id'],'token'=>$record['complementary_info']];
        $return_field_list = ['username','first_name','last_name','company','address','address2','city','state','zip','image','banner','country','latitude','longitude','phone','fax','email','url','nickname','personal_message'];
        foreach ($record as $field_name=>$field_value)
        {
            if (in_array($field_name,$return_field_list))
            {
                $parameter['result'][$field_name] = $field_value;
            }
        }
        return $parameter['result'];

    }

    function select_account_by_token(&$parameter = array())
    {
        if (empty($parameter['option']['token']))
        {
            // Error Handling, username not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Token not provided';
            return false;
        }

        $entity_account_obj = new entity_account();
        $entity_account_param = array(
            'bind_param' => array(':complementary_info'=>$parameter['option']['token']),
            'where' => array('`complementary_info` = :complementary_info')
        );
        $result_row = $entity_account_obj->get($entity_account_param);
        if (empty($result_row))
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Account not available';
            return false;
        }
        $record = end($result_row);
        $parameter['status'] = 'OK';
        $parameter['result'] = ['id'=>$record['id'],'token'=>$record['complementary_info']];
        $return_field_list = ['username','first_name','last_name','company','address','address2','city','state','zip','image','banner','country','latitude','longitude','phone','fax','email','url','nickname','personal_message'];
        foreach ($record as $field_name=>$field_value)
        {
            if (in_array($field_name,$return_field_list))
            {
                $parameter['result'][$field_name] = $field_value;
            }
        }
        return $parameter['result'];
    }

    function select_business_by_uri(&$parameter = array())
    {
        if (empty($parameter['option']['uri']))
        {
            // Error Handling, Website uri not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Website uri not provided';
            return false;
        }
        $index_organization_obj = new index_organization();
        if ($index_organization_obj->filter_by_uri($parameter['option']['uri']) === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database search request failed, try again later';
            return false;
        }
        $view_organization_obj = new view_organization($index_organization_obj->id_group);
        $view_organization_obj->fetch_value();
        $parameter['result'] = [];
        if (count($view_organization_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Cannot find any businesses match the given uri';
        }
        else
        {
            $parameter['status'] = 'OK';
            foreach($view_organization_obj->row as $record_index=>$record)
            {
                $parameter['result'][] = ['name'=>$record['name'],'friendly_url'=>$record['friendly_url'],'address'=>$record['street_address'].' '.$record['suburb'].', '.$record['state'].' '.$record['post'],'phone'=>$record['phone'],'category'=>$record['category_name'],'description'=>$record['description'],'image'=>$record['image_src'],'website'=>$record['website']];
            }
        }
        return $parameter['result'];
    }

    function select_account_index(&$parameter = array())
    {
        $entity_account_obj = new entity_account();
        $entity_account_param = array(
            'bind_param' => array(':import_id'=>$this->api_id),
            'where' => array('`importID` = :import_id')
        );
        if (isset($parameter['option']['page_size']))
        {
            $entity_account_param['limit'] = intval($parameter['option']['page_size']);
            if ($entity_account_param['limit'] > 1000) $entity_account_param['limit'] = 1000;
            if ($entity_account_param['limit'] < 1) $entity_account_param['limit'] = 1;
        }
        else
        {
            $entity_account_param['limit'] = 100;
        }
        if (isset($parameter['option']['page_number']))
        {
            $entity_account_param['offset'] = intval($parameter['option']['page_number'])*$entity_account_param['limit'];
            if ($entity_account_param['offset'] < 0) $entity_account_param['offset'] = 0;
        }
        else
        {
            $entity_account_param['offset'] = 0;
        }

        $entity_account_obj->get($entity_account_param);
        if (count($entity_account_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Account not available';
            return false;
        }
        foreach($entity_account_obj->row as $record_account_index=>$record_account)
        {
            $processed_result_row = ['id'=>$record_account['id'],'token'=>$record_account['complementary_info'],'username'=>$record_account['username']];

            $entity_listing_obj = new entity_listing();
            $entity_listing_param = array(
                'bind_param' => array(':account_id'=>$processed_result_row['id']),
                'where' => array('`account_id` = :account_id')
            );
            $entity_listing_obj->get($entity_listing_param);

            if (count($entity_listing_obj->row) > 0)
            {
                $processed_result_row['businesses'] = [];
                foreach($entity_listing_obj->row as $record_listing_index=>$record_listing)
                {
                    $processed_result_row['businesses'][] = ['id'=>$record_listing['id'],'title'=>$record_listing['title'],'listing_page'=>'http://www.top4.com.au/business/'.$record_listing['friendly_url'],'accessible'=>($record_listing['importID'] == $this->api_id?'true':'false')];
                }
            }

            $parameter['result'][] = $processed_result_row;
        }

        return $parameter['result'];
    }

    function select_business_index(&$parameter = array())
    {
        $entity_listing_obj = new entity_listing();
        $entity_listing_param = array(
            'bind_param' => array(':import_id'=>$this->api_id),
            'where' => array('`importID` = :import_id')
        );
        if (isset($parameter['option']['page_size']))
        {
            $entity_listing_param['limit'] = intval($parameter['option']['page_size']);
            if ($entity_listing_param['limit'] > 1000) $entity_listing_param['limit'] = 1000;
            if ($entity_listing_param['limit'] < 1) $entity_listing_param['limit'] = 1;
        }
        else
        {
            $entity_listing_param['limit'] = 100;
        }
        if (isset($parameter['option']['page_number']))
        {
            $entity_listing_param['offset'] = intval($parameter['option']['page_number'])*$entity_listing_param['limit'];
            if ($entity_listing_param['offset'] < 0) $entity_listing_param['offset'] = 0;
        }
        else
        {
            $entity_listing_param['offset'] = 0;
        }

        $entity_listing_obj->get($entity_listing_param);
        if (count($entity_listing_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Listing not available';
            return false;
        }
        foreach($entity_listing_obj->row as $record_listing_index=>$record_listing)
        {
            $processed_result_row = ['id'=>$record_listing['id'],'account_id'=>$record_listing['account_id'],'title'=>$record_listing['title'],'listing_page'=>URI_SITE_BASE.'business/'.$record_listing['friendly_url']];

            $parameter['result'][] = $processed_result_row;
        }

        return $parameter['result'];
    }

}

?>