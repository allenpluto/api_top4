<?php
// Class Object
// Name: index_organization
// Description: organization's main index table, include all possible search fields

class index_organization extends index
{
    function __construct($value = Null, $parameter = array())
    {
        parent::__construct($value, $parameter);

        return $this;
    }

    function filter_by_active($parameter = array())
    {
        $filter_parameter = array(
            'where' => 'status = \'A\''
        );

        $filter_parameter = array_merge($filter_parameter, $parameter);
        return parent::get($filter_parameter);
    }

    function filter_by_featured($parameter = array())
    {
        $filter_parameter = array(
            'primary_key' => 'listing_id',
            'table' => 'ListingFeatured'
        );

        $filter_parameter = array_merge($filter_parameter, $parameter);
        return parent::get($filter_parameter);
    }

    function filter_by_service_area($parameter = array())
    {
        if (empty($parameter['postcode_suburb_id'])) return false;
        if (!is_array($parameter['postcode_suburb_id']))
        {
            $parameter['postcode_suburb_id'] = [$parameter['postcode_suburb_id']];
        }

        $filter_parameter = array(
            'primary_key' => 'listing_id',
            'table' => 'Listing_ServiceArea',
            'where' => [
                'date_end >= CURDATE()',
                'postcode_suburb_id IN ('.implode(',',$parameter['postcode_suburb_id']).')'
            ]
        );

        $filter_parameter = array_merge($filter_parameter, $parameter);

        return parent::get($filter_parameter);
    }

    // Exact Match Search
    function filter_by_category($value, $parameter = array())
    {
        $format = format::get_obj();
        $category_id_group = $format->id_group(array('value'=>$value,'key_prefix'=>':category_id_'));
        if ($category_id_group === false)
        {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' invalid category id(s)';
            return false;
        }

        $filter_parameter = array(
            'primary_key' => 'listing_id',
            'table' => 'Listing_Category',
            'where' => 'category_id IN ('.implode(',',array_keys($category_id_group)).')',
        );

        $filter_parameter = array_merge($filter_parameter, $parameter);
        if (!isset($filter_parameter['bind_param'])) $filter_parameter['bind_param'] = array();
        $filter_parameter['bind_param'] = array_merge($filter_parameter['bind_param'], $category_id_group);

        return parent::get($filter_parameter);
    }

    function filter_by_suburb($value, $parameter = array())
    {
        $format = format::get_obj();
        $suburb_id_group = $format->id_group(array('value'=>$value,'key_prefix'=>':suburb_id_'));
        if ($suburb_id_group === false)
        {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' invalid postcode_suburb id(s)';
            return false;
        }

        $filter_parameter = array(
            'where' => 'postcode_suburb_id IN ('.implode(',',array_keys($suburb_id_group)).')',
        );
        $filter_parameter = array_merge($filter_parameter, $parameter);
        if (!isset($filter_parameter['bind_param'])) $filter_parameter['bind_param'] = array();
        $filter_parameter['bind_param'] = array_merge($filter_parameter['bind_param'], $suburb_id_group);

        return parent::get($filter_parameter);
    }

    // Fuzzy Search
    function filter_by_keyword($value, $parameter = array())
    {
        $original_id_group = $this->id_group;
        $original_initialized = $this->_initialized;
        $filter_parameter = array(
            'value'=> $value,
            'special_pattern'=>'',
            'fulltext_index_key'=>'fulltext_category'
        );
        $filter_parameter = array_merge($filter_parameter,$parameter);
        if (count($this->id_group) > 0)
        {
            return $this->full_text_search($filter_parameter);
        }
        else
        {
            $this->id_group = $original_id_group;
            $this->_initialized = $original_initialized;
        }
        $filter_parameter = array(
            'value'=> $value,
            'special_pattern'=>'\&',
            'fulltext_index_key'=>'fulltext_keyword'
        );
        $filter_parameter = array_merge($filter_parameter,$parameter);
        return $this->full_text_search($filter_parameter);
    }

    function filter_by_location($value, $parameter = array())
    {
        $filter_parameter = array(
            'value'=> $value,
            'fulltext_index_key'=>'fulltext_location'
        );
        $filter_parameter = array_merge($filter_parameter,$parameter);
        return $this->full_text_search($filter_parameter);
    }

    function filter_by_uri($value, $parameter = array())
    {
        $value = trim(preg_replace('/^(https?:\/\/)?(www\.)?/','',$value),'/');
        if (empty($value))
        {
            // Error Handling, empty uri string
            $this->message->notice = 'filter_by_uri, input string is not in correct URI format';
            return false;
        }

        return $this->get(['where'=>'url LIKE CONCAT("%/",:url,"%") OR url LIKE CONCAT("%.",:url,"%")','bind_param'=>[':url'=>$value]]);
    }


}

?>