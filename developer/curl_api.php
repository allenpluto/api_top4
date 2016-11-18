<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14/10/2016
 * Time: 3:30 PM
 */
if (!isset($_GET['handler']))
{
    //$my_uri = $_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:'http';
    //$my_uri .= '://';
    //$my_uri .= $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']:'127.0.0.1';
    //$my_uri .= $_SERVER['REQUEST_URI']?$_SERVER['REQUEST_URI']:'/';
    //$my_uri = 'http://localhost/allen_frame_trial/json/select_business_by_uri';
    $post_value = $_GET;
    if (isset($post_value['method']))
    {
        $method = $post_value['method'];
        unset($post_value['method']);
    }
    else
    {
        $method = 'list_available_method';
    }

    if ($method == 'insert_account')
    {
        $post_value = [
            'username'=>'allen1@top4.com.au',
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
            'email'=>'shailen@top4.com.au',
            'url'=>'http://www.top4.com.au',
            'nickname'=>'sha',
            'personal_message'=>'shailendra message'
        ];
    }
    if ($method == 'insert_business')
    {
        $post_value = [
            'title'=>'Mr Shrestha Dental',
            'latitude'=>'-33.7606721',
            'longitude'=>'150.9930178',
            'category'=>'http://schema.org/Dentist',
            'abn'=>'123456',
            'address'=>'303 windsor rd',
            'address2'=>'Unit B',
            'city'=>'Castle Hill',
            'state'=>'NSW',
            'zip'=>'2154',
            'phone'=>'0431877555',
            'alternate_phone'=>'0291877553',
            'mobile_phone'=>'0431877555',
            'fax'=>'0291877554',
            'email'=>'shailen@thewebsitemarketinggroup.com.au',
            'url'=>'http://www.top4.com.au',
            'facebook_link'=>'https://www.facebook.com/bondikitchens',
            'twitter_link'=>'',
            'linkedin_link'=>'',
            'blog_link'=>'',
            'pinterest_link'=>'',
            'googleplus_link'=>'https://plus.google.com/117352402953311869532/about',
            'business_type'=>'small',
            'description'=>'shailendra shrestha\'s dental clinic',
            'long_description'=>'shailendra shrestha the man the legend, founder of Mr Shrestha Dental',
            'keywords'=>'dental
clinic'
        ];
    }

    //$handler_uri = 'http://api.top4.com.au/json/'.$method;
    $handler_uri = 'http://localhost/allen_frame_trial/json/'.$method;
    //$handler_uri = $my_uri.($_SERVER['QUERY_STRING']?'?'.$_SERVER['QUERY_STRING']:'').'&handler=true';
    //$handler_uri = 'http://api.top4.com.au/json/select_business_by_uri&dd=http://www.caroma.com.au';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $handler_uri);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //'Auth-Key: dbf5-6923-311e-367c-1a4b-14ed-aa00-f5ce'
        //'Auth-Key: 0650-2370-f1fa-24bf-019d-800f-a1b3-cf66'
        'Auth-Key: 1380-8bae-313a-5f31-e507-abe9-4e9d-9350'
    ));
    curl_setopt($ch,CURLOPT_POST, count($post_value));
    curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post_value));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $contents = curl_exec ($ch);
    //curl_close ($ch);
    //echo '<pre><h1>I\'m Caller</h1><br>';
    //print_r($_SERVER);
    print_r($contents);
}
else
{
    echo '<pre><h1>I\'m Handler</h1><br>';
    print_r($_SERVER);
    print_r(getallheaders());
}
