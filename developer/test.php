<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 20/11/2015
 * Time: 2:00 PM
 */
define('PATH_SITE_BASE','C:\\wamp\\www\\allen_frame_trial\\');
include('../system/config/config.php');
$timestamp = time();
echo '<pre>';
/*$data = 'data:image/jpeg;base64,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';
//$data = 'http://dev.top4.com.au/images/top4/profile_banner_default.jpg';
//$data = PATH_IMAGE.'/xxs/202_photo_19233.jpg';
$size = getimagesize($data, $info);
print_r($size);
//print_r($info);
file_put_contents('test.jpg', file_get_contents($data));
exit();*/
/*$id_array = array(3, 4, 5, 'a', 'b', 'c', ' 231', '%333');

$format_function = format::get_obj();
$instance = $format_function->id_group($id_array);
print_r($instance);*/

/*$listing_id = array(1,4,8,11,22);
for($i=70220;$i<70237;$i++)
{
    $listing_id[] = $i;
}
shuffle($listing_id);
print_r($listing_id);
$category_id = 88;
$index_organization = new index_organization($listing_id);
print_r($index_organization->id_group);
$index_organization->filter_by_category($category_id);
print_r($index_organization->id_group);*/

/*$listing_id = array();
$index_organization = new index_organization($listing_id);
$index_postcode = new index_postcode();
$index_postcode->filter_by_location_text('castle hill, nsw');
print_r($index_postcode);

print_r($index_organization->filter_by_suburb($index_postcode->id_group));

$view_business_summary_obj = new view_business_summary($index_organization->id_group,array('page_size'=>4,'order'=>'RAND()'));
print_r($view_business_summary_obj);*/

/*$listing_id = array();
$index_organization = new index_organization($listing_id);
$keyword_score = $index_organization->filter_by_keyword($_GET['keyword']);
print_r($keyword_score);
$location_score = $index_organization->filter_by_location($_GET['location'],array('preset_score'=>$keyword_score));
print_r($location_score);
$final_score = array();
//$index_organization->reset();
//print_r($index_organization->filter_by_keywords2($_GET['keyword']));
print_r($index_organization->id_group);
$view_business_summary = new view_business_summary($index_organization->id_group);
print_r($view_business_summary->fetch_value());*/


/*$index_organization = new index_organization();
print_r($index_organization);*/

/*$index_category = new index_category();
print_r($index_category->filter_by_listing_count());
print_r($index_category->filter_by_listing(array(10596,65667)));*/

/*$index_image_obj = new index_image();
print_r($index_image_obj->get_gallery_images(12026));
print_r($index_image_obj);
$view_image_obj = new view_image($index_image_obj->id_group);
print_r($view_image_obj->render());
$index_image_obj->reset();

print_r($index_image_obj->get_gallery_images(88,array('item_type'=>'listingcategory')));
print_r($index_image_obj);
$view_image_obj = new view_image($index_image_obj->id_group);
print_r($view_image_obj->render());*/


/*$index_category_obj = new index_category();
$index_category_obj->filter_by_active();
$index_category_obj->filter_by_listing_count();
$view_category_obj = new view_category($index_category_obj->id_group);
echo '<pre>';
print_r($index_category_obj->id_group);
print_r($view_category_obj);
exit();
$render_parameter = array(
    'template'=>PREFIX_TEMPLATE_PAGE.'master',
    'build_from_content'=>array(
        array(
            'title'=>'Find Top4 Businesses in Australia',
            'meta_description'=>'Find Top4 Businesses in Australia',
            'body'=>$view_category_obj
        )
    )
);
$view_web_page_obj = new view_web_page(null,$render_parameter);*/

/*$index_postcode_obj = new index_location();
$index_postcode_obj->filter_by_parameter('nsw/sydney')*/


//$format = format::get_obj();
//$uri_parameters = $format->uri_decoder('listing/find/plumber/nsw/sydney-region');

//$index_location_obj = new index_location();
//$index_location_obj->filter_by_location_parameter($uri_parameters);
//print_r(count($index_location_obj->id_group));
//print_r($global_message);

/*print_r(parse_url('/listing/find/restaurant/?nocache=true'));
echo '<br>';
print_r(parse_url('/listing/find/restaurant/?nocache=true',PHP_URL_PATH));
echo '<br>';
$query_string = parse_url('/listing/find/restaurant/?nocache=true&test1=test+1&test2=&test3=Hello World',PHP_URL_QUERY);
print_r($query_string);
echo '<br>';
parse_str($query_string, $result);
print_r($result);

$view_business_detail_gallery_obj = new view_business_detail_gallery();
$view_business_detail_gallery_obj->get_business_gallery([12026]);
print_r($view_business_detail_gallery_obj);
$view_business_detail_gallery_image_obj = new view_business_detail_gallery_image();
$view_business_detail_gallery_image_obj->get_by_gallery([1690]);
print_r($view_business_detail_gallery_image_obj);
print_r($GLOBALS['global_message']);*/

//$entity = new entity_organization();
//$param = ['table_fields'=>['friendly_url','name']];
//$value = [['test1','Test Name 1'],['test2','Test Name 2']];
//print_r($entity->set($value,$param));

/*$param = ['table_fields'=>['friendly_url','name','address','suburb_id','email','telephone']];
$value = [
    [6,'list_6','Test List 6'],
    [11,'list_11','Test List 11'],
    [23,'list_23','Test List 23'],
    ['','list_26','Test List 26'],
    ['friendly_url'=>'new_list_8','name'=>'Test List 8','id'=>8],
    ['name'=>'New List 24','id'=>24],
    ['friendly_url'=>'new-test-url']
];
$value = [['test-lisitng-1', 'Test Insert New Listing','19 bond street', 721, 'test@top4.com.au', '02 9639 2711']];
print_r($entity->set($value,$param));
$entity->get(['where'=>'`friendly_url` LIKE :friendly_url','bind_param'=>[':friendly_url'=>'list_%']]);
print_r($entity->id_group);
print_r($entity->update(['friendly_url'=>'listing_test']));
*/
//$parameter = array('full_sync'=>true);
/*$value = [
    [10810, 'test', 'Test Listing', '4,20,21','348'],
    [10811, 'greg-james-garage-doors-10811', 'Greg James Garage Door', '141,143','349'],
    [10818, 'build-a-door-services-10818', 'Build A Door Services', '141','360'],
    ['', 'test2', 'Test Listing 2', '142,143', '']
];
$parameter = [
    //'table_fields' => ['id', 'friendly_url', 'name'],
    //'relation_fields' => ['category','gallery'],
    'fields' => ['id', 'friendly_url', 'name', 'category','gallery'],
    'row' => $value
];
$entity->set($parameter);
print_r($entity);
$entity->get(['id_group'=>[10859,11437]]);
print_r($entity);

$timestamp1 = time();
$entity->sync(['sync_type'=>'full_sync']);
print_r('Executing time full sync: '.(time() - $timestamp1).'<br>');

$timestamp2 = time();
$entity->sync(['sync_type'=>'full_sync2']);
print_r('Executing time full sync: '.(time() - $timestamp2).'<br>');*/

// TEST Account ENTITY
/*$entity_account_obj = new entity_account();
$entity_account_param = array(
    'bind_param' => array(':id_min'=>100,':id_max'=>200),
    'where' => array('`id` > :id_min AND `id` < :id_max')
);
$entity_account_obj->get($entity_account_param);*/


// TEST API Method ENTITY
// insert_account method
/*$entity = new entity_api_method();
$value = [
    'username'=>'shailen@top4.com.au',
    'first_name'=>'Shailendra',
    'last_name'=>'Shrestha',
    'company'=>'top4',
    'address'=>'303 windsor rd',
    'address2'=>'Unit B',
    'city'=>'Castle Hill',
    'state'=>'NSW',
    'zip'=>'2154',
    'country'=>'Australia',
    'latitude'=>'-33.7606721',
    'longitude'=>'150.9930178',
    'phone'=>'0431877555',
    'fax'=>'0431877554',
    'email'=>'shailen@thewebsitemarketinggroup.com.au',
    'url'=>'http://www.top4.com.au',
    'nickname'=>'sha',
    'personal_message'=>'shailendra message'
];
$api_parameter = ['row'=>[$value]];
$entity->insert_account($api_parameter);
print_r($global_message);*/
// select_account_by_username method
/*$entity = new entity_api_method();
$value = ['username'=>'shailen@top4.com.au'];
$entity->api_id = 10003;
$result = $entity->select_account_by_username($value);
echo '<pre>';
print_r($value);
print_r($global_message);*/

// select_account_by_token method
/*$entity = new entity_api_method();
$value = ['token'=>'145d243fc763c2ad4c09ba2b250a60e7'];
$entity->api_id = 10003;
$result = $entity->select_account_by_token($value);
echo '<pre>';
print_r($value);
print_r($global_message);*/


// API Method INPUT
// insert_account
$input_parameter = [
    [
        'name'=>'username',
        'type'=>'String',
        'length'=>100,
        'mandatory'=>'true',
        'description'=>'user unique identification, Email address, e.g. allen@top4.com.au'
    ],
    [
        'name'=>'first_name',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'true',
        'description'=>'user first name, e.g. Allen'
    ],
    [
        'name'=>'last_name',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'true',
        'description'=>'user last name, e.g. Shrestha'
    ],
    [
        'name'=>'password',
        'type'=>'String',
        'length'=>20,
        'mandatory'=>'false',
        'description'=>'user password to login, if not provided, system will automatically generate a random password of 8 characters string'
    ],
    [
        'name'=>'company',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'company name for user'
    ],
    [
        'name'=>'address',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'street address, e.g. 339 Windsor Rd'
    ],
    [
        'name'=>'address2',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'street address additional info, unit number, level, subpremise, e.g. Unit 2'
    ],
    [
        'name'=>'city',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'suburb, e.g. Baulkham Hills'
    ],
    [
        'name'=>'state',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'state, e.g. NSW'
    ],
    [
        'name'=>'zip',
        'type'=>'String',
        'length'=>4,
        'mandatory'=>'false',
        'description'=>'postcode, e.g. 2153'
    ],
    [
        'name'=>'latitude',
        'type'=>'Decimal',
        'length'=>'11,8',
        'mandatory'=>'false',
        'description'=>'geo location latitude, e.g. -34.56314822'
    ],
    [
        'name'=>'longitude',
        'type'=>'Decimal',
        'length'=>'11,8',
        'mandatory'=>'false',
        'description'=>'geo location longitude, e.g. 150.47264858'
    ],
    [
        'name'=>'phone',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'personal contact number, e.g. 0412499255'
    ],
    [
        'name'=>'fax',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'personal contact fax, e.g. 0296552722'
    ],
    [
        'name'=>'email',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'personal contact email, e.g. example@gmail.com'
    ],
    [
        'name'=>'url',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'personal website, e.g. www.mywebsite.com'
    ],
    [
        'name'=>'nickname',
        'type'=>'String',
        'length'=>50,
        'mandatory'=>'false',
        'description'=>'nickname, user alias on top4, e.g. Brilliant Scientist'
    ],
    [
        'name'=>'personal_message',
        'type'=>'String',
        'length'=>500,
        'mandatory'=>'false',
        'description'=>'self introduction, e.g. Dr Shrestha has worked in digital marketing industry for over 15 years.'
    ]
];
print_r(json_encode($input_parameter));

// TEST IMAGE ENTITY
/*$entity = new entity_image();
$value = [
    ['name'=>'Image Data URL','image_src'=>'data:image/jpeg;base64,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'],
    ['name'=>'Remote File','image_src'=>'http://dev.top4.com.au/images/top4/profile_banner_default.jpg'],
    ['name'=>'Local File','image_src'=>PATH_IMAGE.'/xxs/202_photo_19233.jpg']
];
$value = [['name'=>'CHIARO Bathroom Package','image_src'=>'http://www.top4.com.au/custom/domain_1/image_files/466_photo_287895.jpg']];
$parameter = [
    'fields' => ['name', 'width', 'height', 'mime','data'],
    'row' => $value
];
$entity->set($parameter);
print_r($entity);
$entity->get();
foreach($entity->row as $record_index=>$record)
{
    file_put_contents('test_'.$record_index.'.jpg', $record['data']);
}
$entity = new entity_image('construction-services-4');
print_r($entity);
print_r(message::get_instance()->display());*/


// TEST CREATE API ACCOUNT AND KEY
// Top4 - top4#2016, Haystack - needle#2016, Crazy Domain - dream#2016
/*$entity = new entity_api(10003);
$entity->update(['password'=>'dream#2016']);
$entity_key = new entity_api_key();
$entity_key->generate_api_key(10003);
print_r(message::get_instance()->display());*/

/*$parameter = array();
$parameter['sync_table'] = str_replace('entity','index',$entity->parameter['table']);
$parameter['update_fields'] = array(
    'id' => 'tbl_entity_place.id',
    'suburb' => 'tbl_entity_place.name',
    'suburb_alt' => 'tbl_entity_place.alternate_name',
    'state' => 'place_state.name',
    'state_alt' => 'place_state.alternate_name',
    'post' => 'tbl_entity_place.post',
    'enter_time' => 'tbl_entity_place.enter_time',
    'update_time' => 'tbl_entity_place.update_time',
    'latitude' => 'tbl_entity_place.latitude',
    'longitude' => 'tbl_entity_place.longitude',
    'bounds_northeast_latitude' => 'tbl_entity_place.bounds_northeast_latitude',
    'bounds_northeast_longitude' => 'tbl_entity_place.bounds_northeast_longitude',
    'bounds_southwest_latitude' => 'tbl_entity_place.bounds_southwest_latitude',
    'bounds_southwest_longitude' => 'tbl_entity_place.bounds_southwest_longitude'
);

$parameter['join'] = array(
    'JOIN tbl_entity_place place_state ON tbl_entity_place.parent_id = place_state.id'
);

$parameter['where'] = array(
    'tbl_entity_place.type = "suburb"'
);
$parameter['fulltext_key'] = array(
    'fulltext_location' => array('suburb','suburb_alt','state,state_alt','country','post')
);

$entity->full_sync($parameter);

$entity = new entity_place_suburb();
$entity->sync();

print_r($global_message->display());
print_r('Executing time: '.(time() - $timestamp).'<br>');
$format = format::get_obj();
print_r($format->minify_css(".column_container:after {display: block; height:1px;margin-top:-1px;clear:both;content: '     i\'d like to test: content : with semi-column '; font-family:'Font Awesome'; }"));
print_r($format->minify_css(".column_container > .column {display: block; float:left;min-height:1px ;}"));*/

/*$test_uri = 'http://mobile.top4.com.au/listing/find/plumbers/nsw/sydney-region/pyrmont';
$test_uri = 'http://mobile.top4.com.au/listing/search/'.urlencode('Plumbing services & gas fitter').'/where/'.urlencode('Pyrmont NSW, 2106');
$uri_part = parse_url($test_uri);
if (!isset($uri_part['path'])) return false;

$uri_path_part = explode('/',$uri_part['path']);
array_walk($uri_path_part,function(&$item, $key){$item=urldecode($item);});
print_r($_SERVER);
print_r($uri_part);
print_r($uri_path_part);
exit();

$result['namespace'] = isset($uri_path_part[0])?$uri_path_part[0]:'default';
$result['instance'] = isset($uri_path_part[1])?$uri_path_part[1]:'home';
$sub_uri = array_slice($uri_path_part, 2);

$uri_query_part = array();
if (isset($uri_part['query']))
{
    parse_str($uri_part['query'],$uri_query_part);
}
$sub_uri = array_merge($uri_query_part, $sub_uri);
*/

/* // TEST GET File Header
$file_header = @get_headers('http://localhost/allen_frame_trial/css/default.min.css',true);
print_r($file_header);*/