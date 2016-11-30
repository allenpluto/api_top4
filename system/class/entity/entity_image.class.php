<?php
// Class Object
// Name: entity_image
// Description: Image Source File, store image in big size, for gallery details, source file to crop... All variation of images (different size thumbs) goes to image_variation table.

// image_id in image_object reference to source image. One source image may have zero to multiple thumbnail (cropped versions) for different scenario. Only source image may save exifData, any thumbnail can be regenerated using source image exifData and 
class entity_image extends entity
{
    function __construct($value = Null, $parameter = array())
    {
        $default_parameter = [
            'store_data'=>true
        ];
        $parameter = array_merge($default_parameter, $parameter);
        parent::__construct($value, $parameter);

        if (!$this->parameter['store_data'])
        {
            unset($this->parameter['table_fields']['data']);
        }

        return $this;
    }

    function get($parameter = array())
    {
        $format = format::get_obj();
        $preference = preference::get_instance();
        if(parent::get($parameter) === false) return false;

        if (!empty($this->row))
        {
            foreach($this->row as $record_index=>&$record)
            {
                $record['document'] = $this->format->file_name((!empty($record['friendly_url'])?$record['friendly_url']:$record['name']).'-'.$record['id']);
                switch($record['mime'])
                {
                    case 'image/gif':
                        $record['file_type'] = 'gif';
                        break;
                    case 'image/png':
                        $record['file_type'] = 'png';
                        break;
                    case 'image/jpeg':
                    case 'image/pjpeg';
                    default:
                        $record['file_type'] = 'jpg';
                }
                $record['sub_path'] = [];
                $sub_path_index = floor($record['id'] / 1000);
                while($sub_path_index > 0)
                {
                    $sub_path_remain = $sub_path_index % 1000;
                    $sub_path_index = floor($sub_path_index / 1000);
                    $record['sub_path'][] = $sub_path_remain;
                }
            }
        }
    }


    function set($parameter = array())
    {
        if (isset($parameter['row']))
        {
            foreach($parameter['row'] as $record_index => &$record)
            {
                if (isset($record['image_src']))
                {
                    $image_size = getimagesize($record['image_src']);
                    if ($image_size !== false)
                    {
                        $record['width'] = $image_size[0];
                        $record['height'] = $image_size[1];
                        if (isset($image_size['mime'])) $record['mime'] = $image_size['mime'];
                    }
                    unset($image_size);

                    $image_data = file_get_contents($record['image_src']);
                    if ($image_data !== false)
                    {
                        $record['data'] = $image_data;
                    }
                    unset($image_data);
                }
            }
        }

        $result = parent::set($parameter);
        if ($result === false) return false;

        $this->generate_cache_file();
        //$listing_image = explode(',', $_POST['listing_logo_thumb']);

        //file_put_contents($file_path,  base64_decode($listing_image[count($listing_image)-1]));

    }

    function generate_cache_file($parameter = array())
    {
        if (!empty($this->row))
        {
            $format = format::get_obj();
            $preference = preference::get_instance();

            foreach($this->row as $record_index=>&$record)
            {
                if (isset($record['data']))
                {
                    $file_name = $format->file_name((!empty($record['friendly_url'])?$record['friendly_url']:$record['name']).'-'.$record['id']);
                    // Generate re-sized thumbnail
                    // if (!empty($parameter['size'])) $file_name .= '.'.$parameter['size'];
                    switch($record['mime'])
                    {
                        case 'image/gif':
                            $file_name .= '.gif';
                            break;
                        case 'image/png':
                            $file_name .= '.png';
                            break;
                        case 'image/jpeg':
                        case 'image/pjpeg';
                        default:
                            $file_name .= '.jpg';
                    }
                    // Create sub path in "Little Endian" structure
                    $sub_path = '';
                    $sub_path_index = floor($record['id'] / 1000);
                    while($sub_path_index > 0)
                    {
                        $sub_path_remain = $sub_path_index % 1000;
                        $sub_path_index = floor($sub_path_index / 1000);
                        $sub_path .= $sub_path_remain.DIRECTORY_SEPARATOR;
                    }
                    $record['file_path'] = PATH_IMAGE.$sub_path;
                    $record['file_name'] = $file_name;
                    $record['file'] = $record['file_path'].$record['file_name'];

                    if (!file_exists(PATH_IMAGE.$sub_path)) mkdir($record['file_path'], 0755, true);
                    file_put_contents($record['file'],  $record['data']);
                }
            }
        }
    }
}