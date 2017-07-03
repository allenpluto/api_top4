<?php
// Class Object
// Name: entity_gallery
// Description: gallery, image collection

class entity_gallery extends entity
{
    var $parameter = array(
        'table' => 'Gallery',
        'relational_fields'=>[
            'image'=>[
                'table'=>'Gallery_Image',
                'primary_key'=>['gallery_id','image_id'],
                'source_id_field'=>'gallery_id',
                'target_id_field'=>'image_id'
            ],
            'listing'=>[
                'table'=>'Gallery_Item',
                'primary_key'=>['gallery_id','item_id'],
                'source_id_field'=>'gallery_id',
                'target_id_field'=>'item_id',
                'where'=>['Gallery_Item.item_type = "listing"']
            ],
        ]
    );

    function get($parameter = array())
    {
        $get_listing_parameter = ['fields' => ['id','account_id','title','image','updated','entered']];
        $get_listing_parameter = array_merge($get_listing_parameter, $parameter);
        $get_listing_result = parent::get($get_listing_parameter);

        if (empty($get_listing_result))
        {
            return $get_listing_result;
        }

        return $get_listing_result;
    }

    function fetch_value($parameter = array())
    {
        if (empty($parameter['relational_fields']))
        {
            $parameter['relational_fields'] = ['image'];
        }

        $get_listing_result = parent::get($parameter);
        if (empty($get_listing_result))
        {
            return $get_listing_result;
        }

        foreach($get_listing_result as $row_index=>&$row)
        {
            $row['image_row'] = [];
            if (!empty($row['image']))
            {
                $image_obj = new entity_gallery_image($row['image']);
                $image_row = $image_obj->get();
                if (!empty($image_row))
                {
                    $row['image_row'] = $image_row;
                }
            }
        }

        return $get_listing_result;
    }
}

?>