<?php
// Class Object
// Name: entity_gallery_image
// Description: Image table (under top4_domain1), stores all listing related images

class entity_gallery_image extends entity
{
    var $parameter = array(
        'table' => '`Image`',
        'primary_key' => 'id',
        'relational_fields'=>[
            'gallery'=>[
                'table'=>'Gallery_Image',
                'primary_key'=>['gallery_id','image_id'],
                'source_id_field'=>'image_id',
                'target_id_field'=>'gallery_id',
                'extra_field'=>[
                    'name'=>'Gallery_Image.image_caption',
                    'thumb_id'=>'Gallery_Image.thumb_id',
                    'order'=>'Gallery_Image.order'
                ]
            ]
        ],
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
if (!empty($GLOBALS['debug_log']))
{
    file_put_contents($GLOBALS['debug_log'],"entity_gallery_image get\n",FILE_APPEND);
}
        if (empty($parameter['table_fields']))
        {
            $parameter['table_fields'] = [
                'id'=>'id',
                'type'=>'type',
                'width'=>'width',
                'height'=>'height',
                'prefix'=>'prefix'
            ];
        }
        $get_result = parent::get($parameter);
        if ($get_result === false) return false;

        foreach ($get_result as $row_index=>&$row)
        {
            if (isset($row['prefix']) AND isset($row['id']) AND isset($row['type']))
            {
                $row['file_uri'] = 'https://www.top4.com.au/custom/domain_1/image_files/'.$row['prefix'].'photo_'.$row['id'].'.'.strtolower($row['type']);
            }
        }
if (!empty($GLOBALS['debug_log']))
{
    file_put_contents($GLOBALS['debug_log'],"get_result\n".print_r($get_result,true)."\n",FILE_APPEND);
}
        return $get_result;
    }
}

?>