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
        $get_listing_parameter = ['fields' => ['id','title','abn','address','address2','city','state','zip_code','account_id','phone','alternate_phone','mobile_phone','fax','email','url','facebook_link','twitter_link','linkedin_link','blog_link','pinterest_link','googleplus_link','business_type','description','long_description','keywords','status','bulked','importID','thumb_id','image_id','banner_id','category','updated','entered']];
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
            $value['friendly_url'] = $this->format->file_name($value['friendly_url'].' '.end($this->id_group));
        }
        parent::update($value, $parameter);
    }
}

?>