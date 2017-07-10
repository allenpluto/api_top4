<?php
// Class Object
// Name: entity_postcode_suburb
// Description: Postcode_Suburb table (under top4_main), top4 location hierarchy, read_only

class entity_postcode_suburb extends entity
{
    var $parameter = array(
        'table' => '`Postcode_Suburb`',
        'primary_key' => 'id',
        'table_fields' => [
            'id'=>'id',
            'post_code'=>'post_code',
            'suburb'=>'suburb',
            'region'=>'region',
            'state'=>'state'
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
        return false;
    }

    function get_location_from_geo($parameter = array())
    {
if (!empty($GLOBALS['debug_log']))
{
    file_put_contents($GLOBALS['debug_log'],'get_location_from_geo start'.print_r($parameter,true).PHP_EOL,FILE_APPEND);
}
        if (is_string($parameter))
        {
            $parameter = implode(',',$parameter);
        }
        if (!$parameter['latitude'])
        {
            $parameter['latitude'] = floatval($parameter[0]);
        }
        if (!$parameter['longitude'])
        {
            $parameter['longitude'] = floatval($parameter[1]);
        }
        if ($parameter['longitude'] == 0 AND $parameter['latitude'] == 0)
        {
            return false;
        }
        if ($parameter['latitude'] < -90 or $parameter['latitude'] > 90)
        {
            return false;
        }
        if ($parameter['longitude'] < -180 or $parameter['longitude'] > 180)
        {
            return false;
        }

        $get_parameter = [
            'where'=>[
                'bounds_ne_lat > '.$parameter['latitude'],
                'bounds_ne_lng > '.$parameter['longitude'],
                'bounds_sw_lat < '.$parameter['latitude'],
                'bounds_sw_lng < '.$parameter['longitude']
            ]
        ];
if (!empty($GLOBALS['debug_log']))
{
    file_put_contents($GLOBALS['debug_log'],'get_location_from_geo get'.print_r($parameter,true).PHP_EOL,FILE_APPEND);
}
        $this->get($get_parameter);
        if (count($this->row) == 0)
        {
            $this->message->notice = 'Cannot find related suburb with '.$parameter['latitude'].','.$parameter['longitude'];
            return false;
        }

        $google_place_result = [];

        $google_place_geocode = json_decode(file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$parameter['latitude'].','.$parameter['longitude'].'&key='.$this->preference->google_api_credential_server),true);
        if (!empty($google_place_geocode['results']))
        {
            foreach ($google_place_geocode['results'] as $google_place_index=>$google_place)
            {
                foreach ($google_place['address_components'] as $google_place_address_component_index=>$google_place_address_component)
                {
                    switch ($google_place_address_component['types'][0])
                    {
                        case 'administrative_area_level_1':
                            $google_place_result[$google_place_address_component['types'][0]] = $google_place_address_component['short_name'];
                            break;
                        case 'locality':
                            if (!isset($google_place_result[$google_place_address_component['types'][0]]))
                            {
                                $google_place_result[$google_place_address_component['types'][0]] = $google_place_address_component['long_name'];
                            }
                            break;
                        default:
                            $google_place_result[$google_place_address_component['types'][0]] = $google_place_address_component['long_name'];
                    }
                }
            }
        }

        $location_result = [
            'address' => $google_place_result['route'],
            'suburb' => $google_place_result['locality'],
            'state' => $google_place_result['administrative_area_level_1'],
            'post_code' => $google_place_result['postal_code']
        ];

if (!empty($GLOBALS['debug_log']))
{
    file_put_contents($GLOBALS['debug_log'],'get_row'.print_r($this->row,true).PHP_EOL,FILE_APPEND);
}

        if (count($this->row) == 1)
        {
            $row = end($this->row);
            $location_result = array_merge($location_result,$row);
        }
        else
        {
            foreach ($this->row as $row_index=>$row)
            {
                if (strtolower($row['suburb']) == strtolower($location_result['suburb']))
                {
                    $location_result = array_merge($location_result, $row);
                }
            }
            if (empty($location_result['region']))
            {
                $location_result['region'] = end($this->row)['region'];
            }
        }
if (!empty($GLOBALS['debug_log']))
{
    file_put_contents($GLOBALS['debug_log'],'location_result'.print_r($location_result,true).PHP_EOL,FILE_APPEND);
}

        return $location_result;
    }

}

?>