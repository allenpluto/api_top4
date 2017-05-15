<?php
// Class Object
// Name: entity_listing_image
// Description: Image table (under top4_domain1), stores all listing related images

class entity_listing_image extends entity
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

    function get($parameter = array())
    {
        $get_result = parent::get();
        if ($get_result === false) return false;

        foreach ($get_result as $row_index=>&$row)
        {
            $row['file_uri'] = 'https://www.top4.com.au/custom/domain_1/image_files/'.$row['prefix'].'photo_'.$row['id'].'.'.strtolower($row['type']);
        }

        return $get_result;
    }
}

?>