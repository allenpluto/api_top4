<?php
// Include Class Object
// Name: content
// Description: web page content functions

// Render template, create html page view...

class content {
    protected $construct = array();
    protected $cache = 0;
    public $parameter = array();
    public $content = array();

    function __construct($parameter = array())
    {
        $this->construct = array();
        // Analyse uri structure and validate input variables
        if ($this->uri_decoder($parameter) === false)
        {
            return false;
        }
    }

    function build_content()
    {
        $format = format::get_obj();
        $preference = preference::get_instance();

        switch($this->construct['data_type'])
        {
            case 'css':
                $file_header = @get_headers($row['file_path']);
                if (strpos( $file_header[0], '200 OK' ) !== false) {
                    $file_header_array = array();
                    foreach ($file_header as $file_header_index => $file_header_item) {
                        preg_match('/^(?:\s)*(.+?):(?:\s)*(.+)(?:\s)*$/', $file_header_item, $matches);
                        if (count($matches) >= 3) {
                            $file_header_array[trim($matches[1])] = trim($matches[2]);
                        }
                    }
                    unset($file_header);
                    if (isset($file_header_array['Last-Modified'])) {
                        $file_version = strtolower(date('dMY', strtotime($file_header_array['Last-Modified'])));
                    } else {
                        if (isset($file_header_array['Expires'])) {
                            $file_version = strtolower(date('dMY', strtotime($file_header_array['Expires'])));
                        } else {
                            if (isset($file_header_array['Date'])) $file_version = strtolower(date('dMY', strtotime($file_header_array['Date'])));
                            else $file_version = strtolower(date('dMY'), strtotime('+1 day'));
                        }

                    }
                    unset($file_header_array);
                }
                $file_version = strtolower(date('dMY', filemtime(PATH_CONTENT_JS.$row['file_name'].'.js')));
                $file_path = $this->construct['document'];
                if (!empty($this->construct['sub_path'])) $file_path = explode(DIRECTORY_SEPARATOR,$this->construct['sub_path']).DIRECTORY_SEPARATOR.$file_path;
                if (!file_exists(PATH_CSS)) mkdir(PATH_CSS, 0755, true);
                exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar '.PATH_CONTENT_CSS.$file_path.'.'.$this->construct['extension'].' -o '.PATH_CSS.$file_path.'.min.'.$this->construct['extension'], $result);
                // further minify css, remove comments
                if (file_exists(PATH_CSS.$file_path.'.'.$file_version.'.min.css'))
                {
                    $min_file = $format->minify_css(file_get_contents(PATH_CSS.$file_path.'.'.$file_version.'.min.css'));
                    // replace all relative path to absolute path in css as file location changes
                    $min_file = str_replace('../',URI_CONTENT,$min_file);
                    // update min file
                    file_put_contents(PATH_CACHE_CSS.$file_path.'.'.$file_version.'.min.css',$min_file);
                    // release memory from the temp file
                    unset($min_file);
                }

                break;
            case 'image':
                // Try to locate the source image
                // Check whether image file with original size exists in cache folder
                if (file_exists(PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->construct['sub_path']).DIRECTORY_SEPARATOR.$this->construct['document'].'.'.$this->construct['file_type']))
                {
                    $source_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->construct['sub_path']).DIRECTORY_SEPARATOR.$this->construct['document'].'.'.$this->construct['file_type'];
                    $source_image_size = getimagesize($source_image_path);
                    if ($source_image_size === false)
                    {
                        // TODO: Error Handling, fail to get source image size
                        break;
                    }
                    switch ($source_image_size['mime']) {
                        case 'image/png':
                            $source_image = imagecreatefrompng($source_image_path);
                            break;
                        case 'image/gif':
                            $source_image = imagecreatefromgif($source_image_path);
                            break;
                        case 'image/jpg':
                        case 'image/jpeg':
                            $source_image = imagecreatefromjpeg($source_image_path);
                            break;
                        default:
                            $source_image = imagecreatefromstring($source_image_path);
                    }
                    if ($source_image === FALSE) {
                        // TODO: Error Handling, fail to create image
                        header('Location: ' . URI_SITE_BASE . '/content/image/img_listing_default_280_280.jpg');
                        exit();
                    }
                }
                else
                {
                    $default_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->construct['sub_path']).DIRECTORY_SEPARATOR.$this->construct['document'].'.'.$this->construct['file_type'];
                    // Check whether image file with same name exists in content folder
                    if (file_exists(PATH_CONTENT_IMAGE.implode(DIRECTORY_SEPARATOR,$this->construct['sub_path']).DIRECTORY_SEPARATOR.$this->construct['document'].'.'.$this->construct['file_type'])) {
                        $source_image_path = PATH_CONTENT_IMAGE . implode(DIRECTORY_SEPARATOR, $this->construct['sub_path']) . DIRECTORY_SEPARATOR . $this->construct['document'] . '.' . $this->construct['file_type'];
                        $source_image_size = getimagesize($source_image_path);
                        if ($source_image_size === false) {
                            // TODO: Error Handling, fail to get source image size
                            break;
                        }
                        switch ($source_image_size['mime']) {
                            case 'image/png':
                                $source_image = imagecreatefrompng($source_image_path);
                                break;
                            case 'image/gif':
                                $source_image = imagecreatefromgif($source_image_path);
                                break;
                            case 'image/jpg':
                            case 'image/jpeg':
                                $source_image = imagecreatefromjpeg($source_image_path);
                                break;
                            default:
                                $source_image = imagecreatefromstring($source_image_path);
                        }
                        if ($source_image === FALSE) {
                            // TODO: Error Handling, fail to create image
                            header('Location: ' . URI_SITE_BASE . '/content/image/img_listing_default_280_280.jpg');
                            exit();
                        }
                    }
                    else
                    {
                        // If file does not exist in content folder either, check if it is stored in database
                        $document_name_part = explode('_',$this->construct['document']);
                        $document_id = end($document_name_part);
                        unset($document_name_part);
                        if (!is_numeric($document_id))
                        {
                            // TODO: Error Handling, fail to get source image from database, file name does not end with image id
                            break;
                        }
                        $entity_image_obj = new entity_image($document_id);
                        unset($document_id);
                        if (empty($entity_image_obj->id_group))
                        {
                            // TODO: Error Handling, fail to get source image from database, id does not exist or database error
                            break;
                        }
                        $entity_image_obj->get();
                        if (empty($entity_image_obj->row[0]['data']))
                        {
                            // TODO: Error Handling, fail to get source image from database, image row exists but image data are not saved in database
                            break;
                        }
                        $entity_image_default_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$entity_image_obj->row[0]['sub_path']).DIRECTORY_SEPARATOR.$entity_image_obj->row[0]['document'].'.'.$entity_image_obj->row[0]['file_type'];
                        if ($entity_image_default_image_path != $default_image_path)
                        {
                            // TODO: Error Handling, source image retrieved from database, but default path is different, (the image might have been renamed), 301 redirect or 403 Error Image
                            break;
                        }
                        $source_image = imagecreatefromstring($entity_image_obj->row[0]['data']);
                        $source_image_size = array(
                            $entity_image_obj->row[0]['width'],
                            $entity_image_obj->row[0]['height'],
                            'mime'=>$entity_image_obj->row[0]['mime']
                        );
                    }
                }

                if (empty($source_image))
                {
                    // TODO: Error Handling, source image can not be located or fail to create php image resource object
                    break;
                }

                if (isset($default_image_path))
                {
                    // If default size image does not exist, create default image cache first
                    if ($source_image_size[0] > end($preference->image['size']))
                    {
                        // if source image is too big, resize it before save as default image cache
                        $default_image_size = array(
                            end($preference->image['size']),
                            round($source_image_size[1] / $source_image_size[0] * end($preference->image['size']))
                        );
                        $default_image = imagecreatetruecolor($default_image_size[0], $default_image_size[1]);
                        imagecopyresampled($default_image,$source_image,0,0,0,0,$default_image_size[0], $default_image_size[1],$source_image_size[0],$source_image_size[1]);

                        // default image generate with the best quality
                        $image_quality = $preference->image['quality']['max'];
                        switch($source_image_size['mime'])
                        {
                            case 'image/png':
                                imagesavealpha($default_image, true);
                                imagepng($default_image, $default_image_path, $image_quality['image/png'][0], $image_quality['image/png'][1]);
                                break;
                            case 'image/gif':
                                // Resampled gif will lose animation, so save it as jpeg
                                // imagegif($default_image, $default_image_path);
                                // break;
                            case 'image/jpg':
                            case 'image/jpeg':
                            default:
                                imagejpeg($default_image, $default_image_path, $image_quality['image/jpeg']);
                        }
                        unset($image_quality);
                        unset($default_image_size);
                    }
                    else
                    {
                        // If source image is in proper size, directly copy the file, php resize might lose quality and make the file size bigger
                        copy($source_image_path,$default_image_path);
                    }
                }

                if (isset($this->construct['size']))
                {
                    $target_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->construct['sub_path']).DIRECTORY_SEPARATOR.$this->construct['document'].'.'.$this->construct['file_type'];
                    if (!in_array($this->construct['size'],array_keys($preference->image['size'])))
                    {
                        // TODO: Error Handling, image size is not defined in global preference
                        break;
                    }
                    $target_image_size = array(
                        $preference->image['size'][$this->construct['size']],
                        round($source_image_size[1] / $source_image_size[0] * $preference->image['size'][$this->construct['size']])
                    );
                    $target_image = imagecreatetruecolor($target_image_size[0], $target_image_size[1]);
                    imagecopyresampled($target_image,$source_image,0,0,0,0,$target_image_size[0], $target_image_size[1],$source_image_size[0],$source_image_size[1]);

                    imageinterlace($target_image,true);

                    // default display image generate with fast speed
                    $image_quality = $preference->image['quality']['spd'];
                    if (!empty($this->construct['extension']))
                    {
                        if (in_array(end($this->construct['extension']),$preference->image['quality']))
                        {
                            $image_quality = $preference->image['quality'][end($this->construct['extension'])];
                        }
                    }

                    switch($source_image_size['mime'])
                    {
                        case 'image/png':
                            imagesavealpha($target_image, true);
                            imagepng($target_image, $target_image_path, $image_quality['image/png'][0], $image_quality['image/png'][1]);
                            break;
                        case 'image/gif':
                            if ($source_image_size[0] <= $target_image_size[0])
                            {
                                copy($source_image_path,$target_image_path);
                                break;
                            }
                            // Resampled gif will lose animation, so save it as jpeg
                            // imagegif($default_image, $default_image_path);
                            // break;
                        case 'image/jpg':
                        case 'image/jpeg':
                        default:
                            imagejpeg($target_image, $target_image_path, ['image/jpeg']);
                    }
                    unset($target_image_size);
                }
                else
                {
                    $target_image_path = $default_image_path?$default_image_path:$source_image_path;
                }
                header('Content-type: '.$source_image_size['mime']);
                header('Content-Length: '.filesize($target_image_path));
                if (!file_exists($target_image_path))
                {
                    // TODO: Error Handling, image file still not exist, probably due to folder not writable
                    break;
                }
                readfile($target_image_path);

                break;
            case 'js':
                break;
            case 'json':
                break;
            case 'html':
            default:
                $this->content['script'][] = array('type'=>'local_file', 'file_name'=>'jquery-1.11.3');
                $this->content['script'][] = array('type'=>'local_file', 'file_name'=>'default');
                switch($this->construct['module'])
                {
                    case 'listing':
                        break;
                    case '':
                    default:
                        if (!isset($this->construct['document']))
                        {
                            include(PATH_SITE_BASE.'404.php');
                            //header("HTTP/1.0 404 Not Found");
                            //header('Location: '.URI_SITE_BASE.'404.php');
                        }
                        $page_obj = new view_web_page($this->construct['document']);
                        if (empty($page_obj->id_group))
                        {
                            include(PATH_SITE_BASE.'404.php');
                            // If page does not exist in database
                            //header("HTTP/1.0 404 Not Found");
                            //header('Location: '.URI_SITE_BASE.'404.php');
                        }
                        if (count($page_obj->id_group) > 1)
                        {
                            $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): multiple web page resources loaded '.implode(',',$page_obj->id_group);
                        }
                        $page_fetched_value = $page_obj->fetch_value(['page_size'=>1]);
                        if (empty($page_fetched_value))
                        {
                            // SQL Error? Page id doesn't exist in database any more?
                            $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): unknown error cannot load desired page';
                            $page_fetched_value = array();
                        }
                        else
                        {
                            $field = $page_fetched_value[0];
                            print_r(render_html($field,'page_default'));
                        }

                }
        }

        //print_r($page_field);
        print_r($GLOBALS['global_message']->display());
        exit();














        $this->content['script'][] = array('type'=>'local_file', 'file_name'=>'jquery-1.11.3');
        $this->content['script'][] = array('type'=>'local_file', 'file_name'=>'default');
        // Google Analytics Tracking
        if ($GLOBALS['global_preference']->ga_tracking_id)
        {
            $this->content['script'][] = array('type'=>'remote_file', 'file_path'=>'http://www.google-analytics.com/analytics.js','file_name'=>'analytics');
            $this->content['script'][] = array('type'=>'text_content', 'content'=>'window[\'GoogleAnalyticsObject\'] = \'ga\';window[\'ga\'] = window[\'ga\'] || function() {(window[\'ga\'].q = window[\'ga\'].q || []).push(arguments)}, window[\'ga\'].l = 1 * new Date();ga(\'create\', \''.$GLOBALS['global_preference']->ga_tracking_id.'\', \'auto\');ga(\'send\', \'pageview\');');
        }
        // Google Map Service
        if ($GLOBALS['global_preference']->google_api_credential_browser)
        {
            $this->content['script'][] = array('type'=>'local_file', 'file_name'=>'google-map-api-handler');
            $this->content['script'][] = array('type'=>'remote_file', 'file_path'=>'https://maps.googleapis.com/maps/api/js?key='.$GLOBALS['global_preference']->google_api_credential_browser.'&libraries=places&callback=google_map_api_callback','file_name'=>'google-map-api');
        }

        $this->content['style'][] = array('type'=>'local_file', 'file_name'=>'default');

        switch ($this->parameter['namespace'])
        {
            case 'asset':
                $this->content['script'] = array();
                $this->content['style'] = array();
                switch ($this->parameter['instance'])
                {
                    case 'image':
                        if (!file_exists(PATH_IMAGE . $this->parameter['image_size'] . '/' . $this->parameter['image_file']))
                        {
                            if (empty($this->parameter['image_size']))
                            {
                                header('Location: '.URI_IMAGE.'default/'.$this->parameter['image_file']);
                                exit();
                            }
                            if ($this->parameter['image_size'] == 'default')
                            {
                                $source_image_path = URI_IMAGE_EXTERNAL.$this->parameter['image_file'];
                                unset($target_width);
                            }
                            else
                            {
                                $default_image_path = PATH_IMAGE . 'default/' . $this->parameter['image_file'];
                                if (file_exists($default_image_path))
                                {
                                    $source_image_path = $default_image_path;
                                    $default_image_exists = true;
                                }
                                else
                                {
                                    $source_image_path = URI_IMAGE_EXTERNAL.$this->parameter['image_file'];
                                    $default_image_exists = false;
                                }
                                $target_width = $this->parameter['image_width'];
                            }
                            if (isset($this->parameter['image_source'])) $source_image_path = $this->parameter['image_source'];

                            $source_image_size = getimagesize($source_image_path);

                            if (!empty($source_image_size[0]))
                            {
                                switch($source_image_size['mime'])
                                {
                                    case 'image/png':
                                        $source_image = imagecreatefrompng($source_image_path);
                                        break;
                                    case 'image/gif':
                                        $source_image = imagecreatefromgif($source_image_path);
                                        break;
                                    case 'image/jpg':
                                    case 'image/jpeg':
                                        $source_image = imagecreatefromjpeg($source_image_path);
                                        break;
                                    default:
                                        $source_image = imagecreatefromstring($source_image_path);
                                }

                                if ($source_image === FALSE)
                                {
                                    header('Location: '.URI_SITE_BASE.'/content/image/img_listing_default_280_280.jpg');
                                    exit();
                                }


                                if (!isset($target_width)) $target_width = min($source_image_size[0],  $GLOBALS['global_preference']->image_size_xxl);
                                $target_height = $source_image_size[1] / $source_image_size[0] *  $target_width;
                                $target_image = imagecreatetruecolor($target_width, $target_height);

                                imagecopyresized($target_image, $source_image,0,0,0,0,$target_width, $target_height,$source_image_size[0], $source_image_size[1]);

                                if (!$default_image_exists)
                                {
                                    $default_image_width = min($source_image_size[0],  $GLOBALS['global_preference']->image_size_xxl);
                                    $default_image_height = $source_image_size[1] / $source_image_size[0] *  $default_image_width;
                                    $default_image = imagecreatetruecolor($default_image_width, $default_image_height);

                                    imagecopyresized($default_image, $source_image,0,0,0,0,$default_image_width, $default_image_height,$source_image_size[0], $source_image_size[1]);
                                }

                                if (!file_exists(PATH_IMAGE. $this->parameter['image_size'] . '/'))
                                {
                                    mkdir(PATH_IMAGE. $this->parameter['image_size'] . '/', 0755, true);
                                }
                                if (!file_exists(PATH_IMAGE. 'default/'))
                                {
                                    mkdir(PATH_IMAGE. 'default/', 0755, true);
                                }

                                if (!isset($source_image_size['mime'])) $source_image_size['mime'] = 'image/jpeg';
                                $target_image_path = PATH_IMAGE. $this->parameter['image_size'] . '/' . $this->parameter['image_file'];
                                switch($source_image_size['mime'])
                                {
                                    case 'image/png':
                                        if (!$default_image_exists)
                                        {
                                            imagepng($default_image, $default_image_path, 0, PNG_NO_FILTER);
                                        }
                                        imageinterlace($target_image,true);
                                        imagepng($target_image, $target_image_path, 9, PNG_ALL_FILTERS);
                                        break;
                                    case 'image/gif':
                                        if (!$default_image_exists)
                                        {
                                            imagegif($default_image, $default_image_path);
                                        }
                                        imageinterlace($target_image,true);
                                        imagegif($target_image, $target_image_path);
                                        break;
                                    case 'image/jpg':
                                    case 'image/jpeg':
                                    default:
                                        if (!$default_image_exists)
                                        {
                                            imagejpeg($default_image, $default_image_path, 100);
                                        }
                                        imageinterlace($target_image,true);
                                        imagejpeg($target_image, $target_image_path, 75);
                                }
                                imagedestroy($source_image);
                                imagedestroy($target_image);
                                if (!$default_image_exists)
                                {
                                    imagedestroy($default_image);
                                }
                                header('Content-type: '.$source_image_size['mime']);
                                header('Content-Length: '.filesize($target_image_path));
                                readfile($target_image_path);
                            }
                        }
                        break;
                }
                break;
            case 'business':
                $this->cache = 3;
                $view_business_detail_obj = new view_business_detail($this->parameter['instance']);
                $view_business_detail_value = $view_business_detail_obj->fetch_value();

                if (count($view_business_detail_value) == 0) header('Location: '.URI_SITE_BASE.'404');
                if ($view_business_detail_value[0]['friendly_url'] != $this->parameter['instance'])
                {
                    header('Location: '.URI_SITE_BASE.$this->parameter['namespace'].'/'.$view_business_detail_value[0]['friendly_url']);
                }

                $render_parameter = array(
                    'build_from_content'=>array(
                        array(
                            'name'=>htmlspecialchars($view_business_detail_value[0]['name']),
                            'description'=>htmlspecialchars($view_business_detail_value[0]['description']),
                            'amp_uri'=>URI_SITE_BASE.'business-amp/'.$this->parameter['instance'],
                            'robots'=>'index, nofollow',
                            'body'=>$view_business_detail_obj
                        )
                    )
                );
                $render_parameter = array_merge($this->parameter, $render_parameter);
                $view_web_page_obj = new view_web_page(null, $render_parameter);
                $this->content['html'] = $view_web_page_obj->render();
                break;
            case 'business-amp':
                $this->cache = 3;

                $this->content['style'] = [];
                $this->content['style'][] = array('type'=>'local_file', 'file_name'=>'amp');
                $this->content['script'] = [];

                $view_business_detail_obj = new view_business_amp_detail($this->parameter['instance']);
                $view_business_detail_value = $view_business_detail_obj->fetch_value();

                if (count($view_business_detail_value) == 0) header('Location: '.URI_SITE_BASE.'404');
                if ($view_business_detail_value[0]['friendly_url'] != $this->parameter['instance'])
                {
                    header('Location: '.URI_SITE_BASE.$this->parameter['namespace'].'/'.$view_business_detail_value[0]['friendly_url']);
                }

                $render_parameter = array(
                    'build_from_content'=>array(
                        array(
                            'name'=>htmlspecialchars($view_business_detail_value[0]['name']),
                            'description'=>htmlspecialchars($view_business_detail_value[0]['description']),
                            'default_uri'=>URI_SITE_BASE.'business/'.$this->parameter['instance'],
                            'body'=>$view_business_detail_obj
                        )
                    )
                );
                $this->content['script'][] = array('type'=>'json-ld','content'=>['@id'=>URI_SITE_BASE.'business/'.$this->parameter['instance'],'url'=>URI_SITE_BASE.'business/'.$this->parameter['instance']]);
                $render_parameter = array_merge($this->parameter, $render_parameter);
                $view_web_page_obj = new view_web_page(null, $render_parameter);
                $this->content['html'] = $view_web_page_obj->render();
                break;
            case 'listing':
                $page_parameter = $format->pagination_param($this->parameter);
                if ($page_parameter === false) $page_parameter = array();
                switch ($this->parameter['instance'])
                {
                    case '':
                        $this->cache = 1;
                        $index_category_obj = new index_category();
                        $index_category_obj->filter_by_active();
                        $index_category_obj->filter_by_listing_count();
                        $view_category_obj = new view_category($index_category_obj->id_group);

                        $inline_script = json_encode(array('object_type'=>'business_category','data_encode_type'=>$GLOBALS['global_preference']->ajax_data_encode,'id_group'=>array_values($view_category_obj->id_group),'page_size'=>$view_category_obj->parameter['page_size'],'page_number'=>$view_category_obj->parameter['page_number'],'page_count'=>$view_category_obj->parameter['page_count']));
                        if ($GLOBALS['global_preference']->ajax_data_encode == 'base64')
                        {
                            $inline_script = '$.parseJSON(atob(\'' . base64_encode($inline_script) . '\'))';
                        }
                        $this->content['script'][] = array('type'=>'text_content', 'content'=>'$(document).ready(function(){$(\'.ajax_loader_container\').ajax_loader('.$inline_script.');});');
                        unset($inline_script);

                        $view_web_page_element_obj_body = new view_web_page_element(null, array(
                            'template'=>'element_body_section',
                            'build_from_content'=>array(
                                array(
                                    'id'=>'category_container',
                                    'class_extra'=>' category_block_wrapper',
                                    'title'=>'<h1>Popular Categories</h1>',
                                    'content'=>'<div class="column_container ajax_loader_container">'.$view_category_obj->render().'<div class="clear"></div></div>'
                                ),
                            )
                        ));
                        $render_parameter = array(
                            'build_from_content'=>array(
                                array(
                                    'name'=>'Top4 Businesses Australian Local Listings',
                                    'description'=>'Find restaurants, hotels, plumbers, accountants and all kinds of local businesses with The New Australian Social Media Top4 Business and Brand Directory.',
                                    'meta_keywords'=>'business category, local services, social directory, top4',
                                    'body'=>$view_web_page_element_obj_body
                                )
                            )
                        );
                        $render_parameter = array_merge($this->parameter, $render_parameter);
                        $view_web_page_obj = new view_web_page(null,$render_parameter);
                        $this->content['html'] = $view_web_page_obj->render();

                        break;
                    case 'ajax-load':
                        $this->content['script'] = array();
                        $this->content['style'] = array();
                        if (!isset($_POST['object_type']))
                        {
                            $_POST['object_type'] = 'business';
                        }
                        if (!isset($_POST['id_group']))
                        {
                            $this->content['html'] = '';
                            break;
                        }
                        if (isset($_POST['system_debug']))
                        {
                            $this->parameter['debug_mode'] = $_POST['system_debug']?true:false;
                        }

                        switch($_POST['object_type'])
                        {
                            case 'business_category':
                                $view_category_obj = new view_category($_POST['id_group'],array('page_size'=>$_POST['page_size'],'page_number'=>$_POST['page_number']));
                                $this->content['html'] = $view_category_obj->render();
                                break;
                            case 'business':
                                $view_business_summary_obj = new view_business_summary($_POST['id_group'],array('page_size'=>$_POST['page_size'],'page_number'=>$_POST['page_number']));
                                $this->content['html'] = $view_business_summary_obj->render();
                                break;
                            default:
                                // unknown object type
                                $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.$this->parameter['namespace'].' '.$this->parameter['instance'].' unknown type';

                        }

                        break;
                    case 'find':
                        $this->cache = 1;
                        $index_organization_obj = new index_organization();
                        if (empty($this->parameter['category']))
                        {
                            header('Location: /'.URI_SITE_PATH.$this->parameter['namespace'].'/');
                            exit();
                        }

                        $view_category_obj = new view_category($this->parameter['category']);
                        if (count($view_category_obj->id_group) == 0)
                        {
                            header('Location: /'.URI_SITE_PATH.$this->parameter['namespace'].'/');
                            exit();
                        }
                        $index_organization_obj->filter_by_category($view_category_obj->id_group);

                        if (!empty($this->parameter['state']))
                        {
                            $index_location_obj = new index_location();
                            $index_location_obj->filter_by_location_parameter($this->parameter);
                            if (count($index_location_obj->id_group) == 1)
                            {
                                $index_service_area_organization_obj = new index_organization($index_organization_obj->id_group);
                                $service_area_organization_id_group = $index_service_area_organization_obj->filter_by_service_area(['postcode_suburb_id'=>$index_location_obj->id_group]);
                                unset($index_service_area_organization_obj);
                            }

                            $index_organization_obj->filter_by_suburb($index_location_obj->id_group);
                        }
                        if (!empty($service_area_organization_id_group))
                        {
                            $index_organization_obj->id_group = array_merge($service_area_organization_id_group,$index_organization_obj->id_group);
                        }
                        $view_business_summary_obj = new view_business_summary($index_organization_obj->id_group, $page_parameter);
                        if (count($view_business_summary_obj->id_group) > 0)
                        {
                            $content = '<div id="search_result_listing_block_wrapper" class="listing_block_wrapper block_wrapper ajax_loader_container">'.$view_business_summary_obj->render().'<div class="clear"></div></div>';

                            $inline_script = json_encode(array('data_encode_type'=>$GLOBALS['global_preference']->ajax_data_encode,'id_group'=>array_values($view_business_summary_obj->id_group),'page_size'=>$view_business_summary_obj->parameter['page_size'],'page_number'=>$view_business_summary_obj->parameter['page_number'],'page_count'=>$view_business_summary_obj->parameter['page_count']));
                            if ($GLOBALS['global_preference']->ajax_data_encode == 'base64')
                            {
                                $inline_script = '$.parseJSON(atob(\'' . base64_encode($inline_script) . '\'))';
                            }
                            $this->content['script'][] = array('type'=>'text_content', 'content'=>'$(document).ready(function(){$(\'#search_result_listing_block_wrapper\').ajax_loader('.$inline_script.');});');
                            unset($inline_script);
                        }
                        else
                        {
                            $content = '<div class="section_container container article_container"><div class="section_title"><h2>Here\'s how we can help you find what you\'re looking for:</h2></div><div class="section_content"><ul><li>Check the spelling and try again.</li><li>Try a different suburb or region.</li><li>Try a more general search.</li></ul></div></div>';
                        }

                        $category_row = $view_category_obj->fetch_value();
                        $long_title = htmlspecialchars('Top 4 '.$category_row[0]['page_title']);

                        $view_web_page_element_obj_body = new view_web_page_element(null, array(
                            'template'=>'element_body_section',
                            'build_from_content'=>array(
                                array(
                                    'id'=>'listing_search_result_container',
                                    'title'=>'<h1>'.$long_title.'</h1>',
                                    'content'=>$content
                                )
                            )
                        ));

                        $render_parameter = array(
                            'template'=>PREFIX_TEMPLATE_PAGE.'default',
                            'build_from_content'=>array(
                                array(
                                    'name'=>$long_title,
                                    'description'=>$long_title,
                                    'body'=>$view_web_page_element_obj_body
                                )
                            )
                        );
                        $render_parameter = array_merge($this->parameter, $render_parameter);
                        $view_web_page_obj = new view_web_page(null,$render_parameter);
                        $this->content['html'] = $view_web_page_obj->render();

                        break;
                    case 'search':
                        $index_organization_obj = new index_organization();
                        if (!isset($this->parameter['extra_parameter']))
                        {
                            $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' URL pointing to search without search terms';
                            header('Location: /'.URI_SITE_PATH.$this->parameter['namespace'].'/');
                            exit();
                        }
                        $ulr_part = $format->split_uri($this->parameter['extra_parameter']);

                        if (empty($ulr_part[0]) OR $ulr_part[0] == 'empty')
                        {
                            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' illegal search term';
                            header('Location: /'.URI_SITE_PATH.$this->parameter['namespace'].'/');
                            exit();
                        }
                        $what =  trim(html_entity_decode(strtolower($ulr_part[0])));
                        $score = $index_organization_obj->filter_by_keyword($ulr_part[0]);
                        $where = '';
                        $content = '';
                        if (isset($ulr_part[2]))
                        {
                            $where = trim(html_entity_decode(strtolower($ulr_part[2])));
                            if (strtolower($ulr_part[1]) == 'where' AND $where != 'empty')
                            {
                                $index_place_suburb = new index_place_suburb();
                                $suburb_search_result = $index_place_suburb->filter_by_location_text($where);
                                switch ($suburb_search_result['status'])
                                {
                                    case 'exact_match':
                                        $index_organization_obj->filter_by_suburb($index_place_suburb->id_group);
                                        break;
                                    case 'text_match':
                                        $high_relevance_score = 0.9;
                                        $high_relevance_suburb = array();
                                        foreach($suburb_search_result['score'] as $suburb_id=>$score)
                                        {
                                            if ($score <= $high_relevance_score)
                                            {
                                                break;
                                            }
                                            $high_relevance_suburb[] = $suburb_id;
                                        }
                                        if (count($high_relevance_suburb) == 1)
                                        {
                                            $index_organization_obj->filter_by_suburb($index_place_suburb->id_group);
                                            break;
                                        }
                                        if (count($high_relevance_suburb) > 1)
                                        {
                                            // TODO: Multiple matched results, suggest "Search instead for"
                                            $view_place_suburb = new view_place_suburb($index_place_suburb->id_group);
                                            $view_place_suburb->fetch_value(['table_fields'=>['id'=>'id','formatted_address'=>'formatted_address'],'page_size'=>$GLOBALS['global_preference']->max_relevant_suburb]);
                                            $view_place_suburb->parameter['base_uri'] = URI_SITE_BASE;
                                            $view_place_suburb->parameter['search_what'] = $what;
                                            $content .= '<div class="section_container container article_container"><div class="section_title"><h2>Ambiguous Location Results</h2></div><div class="section_content"><p>Search Instead For: </p><ul>';
                                            $content .= $view_place_suburb->render(['template'=>'view_place_suburb_ambiguous']);
                                            $content .= '</ul></div></div>';

                                            $index_organization_obj->filter_by_suburb($index_place_suburb->id_group);
                                        }
                                        else
                                        {
                                            $view_place_suburb = new view_place_suburb([$high_relevance_suburb[0]]);
                                            $view_place_suburb->fetch_value(['table_fields'=>['id'=>'id','formatted_address'=>'formatted_address']]);
                                            $view_place_suburb->parameter['base_uri'] = URI_SITE_BASE;
                                            $view_place_suburb->parameter['search_what'] = $what;
                                            $content .= '<div class="section_container container article_container"><div class="section_title"><h2>Similar Location Results Found</h2></div><div class="section_content">';
                                            $content .= '<p>Showing Results for </p><ul>'.$view_place_suburb->render(['template'=>'view_place_suburb_ambiguous']).'</ul><p>Search Instead For: </p><ul>';
                                            $view_place_suburb->id_group = array_slice($index_place_suburb->id_group,1,$GLOBALS['global_preference']->max_relevant_suburb);
                                            $view_place_suburb->fetch_value(['table_fields'=>['id'=>'id','formatted_address'=>'formatted_address']]);
                                            $content .= $view_place_suburb->render(['template'=>'view_place_suburb_ambiguous']);
                                            $content .= '</ul></div></div>';

                                            $index_organization_obj->filter_by_suburb([$high_relevance_suburb[0]]);
                                        }
                                        break;
                                    case 'fail':
                                    default:
                                        $content .= '<div class="section_container container article_container"><div class="section_title"><h2>Here\'s how we can help you find what you\'re looking for:</h2></div><div class="section_content"><ul><li>Check the spelling and try again.</li><li>Try a different suburb or region.</li><li>Try a more general search.</li></ul></div></div>';
                                }
                                echo '<pre>';
                                print_r($suburb_search_result);
                                $view_place_suburb = new view_place_suburb($index_place_suburb->id_group);
                                $view_place_suburb->fetch_value();
                                print_r($view_place_suburb);
                                exit();
                            }
                        }
                        $view_business_summary_obj = new view_business_summary($index_organization_obj->id_group, $page_parameter);
                        if (count($view_business_summary_obj->id_group) > 0)
                        {
                            $content .= '<div id="search_result_listing_block_wrapper" class="listing_block_wrapper block_wrapper ajax_loader_container">'.$view_business_summary_obj->render().'<div class="clear"></div></div>';

                            $inline_script = json_encode(array('data_encode_type'=>$GLOBALS['global_preference']->ajax_data_encode,'id_group'=>array_values($view_business_summary_obj->id_group),'page_size'=>$view_business_summary_obj->parameter['page_size'],'page_number'=>$view_business_summary_obj->parameter['page_number'],'page_count'=>$view_business_summary_obj->parameter['page_count']));
                            if ($GLOBALS['global_preference']->ajax_data_encode == 'base64')
                            {
                                $inline_script = '$.parseJSON(atob(\'' . base64_encode($inline_script) . '\'))';
                            }
                            $this->content['script'][] = array('type'=>'text_content', 'content'=>'$(document).ready(function(){$(\'.ajax_loader_container\').ajax_loader('.$inline_script.');});');
                            unset($inline_script);
                        }
                        else
                        {
                            $content .= '<div class="section_container container article_container"><div class="section_title"><h2>Here\'s how we can help you find what you\'re looking for:</h2></div><div class="section_content"><ul><li>Check the spelling and try again.</li><li>Try a different suburb or region.</li><li>Try a more general search.</li></ul></div></div>';
                        }


                        $long_title = htmlspecialchars('Search '.($what?html_entity_decode($what):'Business').' in '.($where?$where:'Australia'));
                        $this->parameter['search_what'] = $what;
                        $this->parameter['search_where'] = $where;

                        $view_web_page_element_obj_body = new view_web_page_element(null, array(
                            'template'=>'element_body_section',
                            'build_from_content'=>array(
                                array(
                                    'id'=>'listing_search_result_container',
                                    'title'=>'<h1>'.$long_title.'</h1>',
                                    'content'=>$content
                                )
                            )
                        ));

                        $render_parameter = array(
                            'template'=>PREFIX_TEMPLATE_PAGE.'default',
                            'build_from_content'=>array(
                                array(
                                    'name'=>$long_title,
                                    'description'=>$long_title,
                                    'robots'=>'noindex, follow',
                                    'body'=>$view_web_page_element_obj_body
                                )
                            ),
                            'parameter'=>array(
                                'search_what'=>$what,
                                'search_where'=>$where
                            )

                        );
                        $render_parameter = array_merge($this->parameter, $render_parameter);
                        $view_web_page_obj = new view_web_page(null,$render_parameter);
                        $this->content['html'] = $view_web_page_obj->render();

                        break;
                    default:
                        header('Location: /'.URI_SITE_PATH.$this->parameter['namespace'].'/');
                }
                break;
            case 'member':
                session_start();
                switch ($this->parameter['instance'])
                {


                }
                break;
            default:
                switch ($this->parameter['instance'])
                {
                    case 'home':
                        $this->cache = 1;
                        $index_organization_obj = new index_organization();
                        $view_business_summary_obj = new view_business_summary($index_organization_obj->filter_by_featured(),array('page_size'=>4,'order'=>'RAND()'));
                        $inline_script = json_encode(array('data_encode_type'=>$GLOBALS['global_preference']->ajax_data_encode,'id_group'=>array_values($view_business_summary_obj->id_group),'page_size'=>$view_business_summary_obj->parameter['page_size'],'page_number'=>$view_business_summary_obj->parameter['page_number'],'page_count'=>$view_business_summary_obj->parameter['page_count']));
                        if ($GLOBALS['global_preference']->ajax_data_encode == 'base64')
                        {
                            $inline_script = '$.parseJSON(atob(\'' . base64_encode($inline_script) . '\'))';
                        }
                        $this->content['script'][] = array('type'=>'text_content', 'content'=>'$(document).ready(function(){$(\'.ajax_loader_container\').ajax_loader('.$inline_script.');});');
                        unset($inline_script);

                        $view_web_page_element_obj_body = new view_web_page_element(null, array(
                            'template'=>'element_body_section',
                            'build_from_content'=>array(
                                array(
                                    'id'=>'home_featured_listing_container',
                                    'title'=>'<h2>Featured</h2>',
                                    'content'=>'<div class="listing_block_wrapper block_wrapper ajax_loader_container">'.$view_business_summary_obj->render().'<div class="clear"></div></div>'
                                ),
                                /*array(
                                    'id'=>'home_listing_category_container',
                                    'title'=>'Category',
                                    'content'=>'Some Category here...'
                                )*/
                            )
                        ));

                        $render_parameter = array(
                            'build_from_content'=>array(
                                array(
                                    'name'=>'Top4 - The New Australian Social Media Business and Brand Directory',
                                    'description'=>'Top4 is the new Australian Social Media Business and Brand Directory designed to help Australians find and connect with any business, product, brand, job or person nearest their location.',
                                    'meta_keywords'=>'social directory, australian business brand',
                                    'body'=>$view_web_page_element_obj_body
                                )
                            )
                        );
                        $render_parameter = array_merge($this->parameter, $render_parameter);
                        $view_web_page_obj = new view_web_page(null, $render_parameter);
                        //$doc = new DOMDocument();
                        //$doc->loadHTML($view_web_page_obj->render());
                        $this->content['html'] = $view_web_page_obj->render();
                        break;
                    case '404':
                        header("HTTP/1.0 404 Not Found");
                        $this->content['html'] = '404 Not Found';
                        break;
                    default:
                        $this->cache = 10;
                        $view_web_page_obj = new view_web_page($this->parameter['instance'],$this->parameter);
                        if (count($view_web_page_obj->id_group) == 0)
                        {
                            header('Location: '.URI_SITE_BASE.'404');
                        }
                        $this->content['html'] = $view_web_page_obj->render();
                }
        }
    }

    private function uri_decoder($value)
    {
        if (is_array($value))
        {
            if (!empty($value['value']))
            {
                extract($value);
            }
            else
            {
                $option = $value;
                $value = '';
            }
        }
        if (empty($value))
        {
            $value = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        if (!isset($option)) $option = array();

        if (!empty($_GET))
        {
            $option = array_merge($option,$_GET);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            // default post request with json format Input and Output
            $option = array_merge($option,$_POST);
            if (!isset($option['data_type'])) $option['data_type'] = 'json';
        }

        $preference = preference::get_instance();
        $message = message::get_instance();

        $request_uri = trim(preg_replace('/^[\/]?'.FOLDER_SITE_BASE.'[\/]/','',$value),'/');
        $request_path = explode('/',$request_uri);

        $type = ['css','image','js','json','html'];
        $request_path_part = array_shift($request_path);
        if (in_array($request_path_part,$type))
        {
            $this->construct['data_type'] = $request_path_part;
        }
        else
        {
            $this->construct['data_type'] = end($type);
        }

        // HTML Page uri structure decoder
        switch ($this->construct['data_type'])
        {
            case 'css':
            case 'js':
                if (empty($request_path))
                {
                    // TODO: css/js folder forbid direct access
                    break;
                }
                $file_name = array_pop($request_path);
                $file_part = explode('.',$file_name);
                $this->construct['document'] = array_shift($file_part);
                if (!empty($file_part)) $this->construct['file_type'] = array_pop($file_part);
                $this->construct['extension'] = $file_part;
                unset($file_name);
                unset($file_part);

                $this->construct['sub_path'] = $request_path;
                break;
            case 'html':
                $request_path_part = array_shift($request_path);
                $module = ['listing','business','business-amp',''];
                if (in_array($request_path_part,$module))
                {
                    $this->construct['module'] = $request_path_part;
                    $request_path_part = array_shift($request_path);
                }
                else
                {
                    $this->construct['module'] = end($module);
                }

                switch ($this->construct['module'])
                {
                    case 'listing':
                        $method = ['search','find',''];
                        if (in_array($request_path_part,$method))
                        {
                            $this->construct['method'] = $request_path_part;
                            $request_path_part = array_shift($request_path);
                        }
                        else
                        {
                            $this->construct['method'] = end($method);
                        }

                        switch ($this->construct['method'])
                        {
                            case 'search':
                                $this->construct['option'] = array('keyword'=> $request_path_part);
                                if (count($request_path)>=2)
                                {
                                    $option = ['where','screen','sort'];
                                    $path_max = floor(count($request_path)/2);
                                    for ($i=0; $i<$path_max; $i++)
                                    {
                                        if (!in_array( $request_path[$i*2],$option))
                                        {
                                            $message->error = __FILE__.'(line '.__LINE__.'): Construction Fail, unknown option ['.$request_path[$i*2].'] for '.$this->construct['data_type'];
                                            break 2;
                                        }
                                        $this->construct['option'][$request_path[$i*2]] = $request_path[$i*2+1];
                                    }
                                }
                                break;
                            case 'find':
                                $this->construct['option'] = array('category'=> $request_path_part);
                                $location = ['state','region','suburb'];
                                $option = ['keyword','where','screen','sort'];
                                foreach($request_path as $request_path_part_index=>$request_path_part)
                                {
                                    // If it is not option key
                                    if (in_array($request_path_part,$option))
                                    {
                                        $request_path = array_slice($request_path,$request_path_part_index);
                                        break;
                                    }
                                    $this->construct['option'][$location[$request_path_part_index]] = $request_path_part;
                                }
                                if (count($request_path)>=2)
                                {
                                    $path_max = floor(count($request_path)/2);
                                    for ($i=0; $i<$path_max; $i++)
                                    {
                                        if (!in_array( $request_path[$i*2],$option))
                                        {
                                            $message->error = __FILE__.'(line '.__LINE__.'): Construction Fail, unknown option ['.$request_path[$i*2].'] for '.$this->construct['data_type'];
                                            break 2;
                                        }
                                        $this->construct['option'][$request_path[$i*2]] = $request_path[$i*2+1];
                                    }
                                }
                                break;
                            default:
                                //$this->construct['document'] = $request_path_part;
                        }
                        break;
                    default:
                        $this->construct['document'] = $request_path_part;
                }

                break;
            case 'image':
                $file_name = array_pop($request_path);
                $file_part = explode('.',$file_name);
                $this->construct['document'] = array_shift($file_part);
                if (!empty($file_part)) $this->construct['file_type'] = array_pop($file_part);
                $this->construct['extension'] = $file_part;
                unset($file_name);
                unset($file_part);

                $image_size = array_keys($preference->image['size']);

                if (!empty($request_path))
                {
                    if (in_array(end($request_path),$image_size))
                    {
                        $this->construct['size'] = array_pop($request_path);
                    }
                    $this->construct['sub_path'] = $request_path;

                    // If more uri parts available, do something here
                    //$this->construct['option'] = $request_path;
                }
                break;
            case 'json':
                break;
        }
    }

    function get_cache()
    {
        if (file_exists($this->parameter['cache_path'].'/index.html') AND $this->parameter['page_cache'])
        {
            $cached_page_content = file_get_contents($this->parameter['cache_path'].'/index.html');
            preg_match_all('/\<\!\-\-(\{.*\})\-\-\>/', $cached_page_content, $matches, PREG_OFFSET_CAPTURE);
            $cached_page_parameter = array();
            foreach($matches[1] as $key=>$value)
            {
                $json_decode_result = json_decode($value[0],true);
                if (is_array($json_decode_result)) $cached_page_parameter = array_merge($cached_page_parameter, $json_decode_result);
            }

            if (isset($cached_page_parameter['Expire']) AND strtotime($cached_page_parameter['Expire']) >= strtotime('now'))
            {
                preg_replace('/\<\!\-\-\{.*\}\-\-\>/', '', $cached_page_content);
                $this->content['html'] = $cached_page_content;
                return true;
            }
            else
            {
                unlink($this->parameter['cache_path'].'/index.html');
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    function set_cache()
    {
        if ($this->cache > 0)
        {
            $expire_time = strtotime('+'.$this->cache.' day');
            $cache_parameter = array('Expire'=>date('d M, Y', $expire_time));
            if (!file_exists($this->parameter['cache_path']))
            {
                mkdir($this->parameter['cache_path'], 0755, true);
            }
            $result = file_put_contents($this->parameter['cache_path'].'/index.html',$this->content['html'].'<!--'.json_encode($cache_parameter).'-->');
            return $result;
        }
        else
        {
            return false;
        }
    }

    function render($parameter = array())
    {
        header('Content-Type: text/html; charset=utf-8');

        if (!$this->get_cache())
        {
            $this->build_content();
            $format = format::get_obj();

            // Minify HTML
            if ($GLOBALS['global_preference']->minify_html)
            {
                $this->content['html'] = $format->minify_html($this->content['html']);
            }

            if (strpos($this->content['html'], '[[+script]]') !== false)
            {
                // Minify JS
                $page_script = '';
                if ($GLOBALS['global_preference']->minify_js)
                {
                    $json_ld = array();
                    foreach ($this->content['script'] as $row_index=>$row)
                    {
                        if (isset($row['type']))
                        {
                            switch ($row['type'])
                            {
                                case 'local_file':
                                    if (file_exists(PATH_CONTENT_JS.$row['file_name'].'.js'))
                                    {
                                        $file_version = strtolower(date('dMY', filemtime(PATH_CONTENT_JS.$row['file_name'].'.js')));
                                        if (!file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                        {
                                            if (!file_exists(PATH_CACHE_JS)) mkdir(PATH_CACHE_JS, 0755, true);
                                            exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar '.PATH_CONTENT_JS.$row['file_name'].'.js -o '.PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js', $result);
                                            // further minify js, remove comments
                                            if (file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                            {
                                                $min_file = $format->minify_js(file_get_contents(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'));
                                                file_put_contents(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js',$min_file);
                                                // release memory from the temp file
                                                unset($min_file);
                                            }
                                        }
                                        // Double check if min.js is generated successfully
                                        if (file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                        {
                                            $row['content'] = $format->minify_js(file_get_contents(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'));
                                        }
                                        else
                                        {
                                            $row['src'] = URI_CONTENT_JS.$row['file_name'].'.js';
                                            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): load minified js script ['.PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js] failed';
                                        }
                                    }
                                    else
                                    {
                                        $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): load source js script ['.PATH_CONTENT_JS.$row['file_name'].'.js] failed';
                                    }
                                    break;
                                case 'remote_file':
                                    $file_header = @get_headers($row['file_path']);
                                    if (strpos( $file_header[0], '200 OK' ) !== false)
                                    {
                                        $file_header_array = array();
                                        foreach($file_header as $file_header_index=>$file_header_item)
                                        {
                                            preg_match('/^(?:\s)*(.+?):(?:\s)*(.+)(?:\s)*$/', $file_header_item, $matches);
                                            if (count($matches) >= 3)
                                            {
                                                $file_header_array[trim($matches[1])] = trim($matches[2]);
                                            }
                                        }
                                        unset($file_header);
                                        if (isset($file_header_array['Last-Modified']))
                                        {
                                            $file_version = strtolower(date('dMY', strtotime($file_header_array['Last-Modified'])));
                                        }
                                        else
                                        {
                                            if (isset($file_header_array['Expires']))
                                            {
                                                $file_version = strtolower(date('dMY', strtotime($file_header_array['Expires'])));
                                            }
                                            else
                                            {
                                                if (isset($file_header_array['Date'])) $file_version = strtolower(date('dMY', strtotime($file_header_array['Date'])));
                                                else $file_version  = strtolower(date('dMY'), strtotime('+1 day'));
                                            }

                                        }
                                        unset($file_header_array);
                                        if (!file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.js'))
                                        {
                                            if (!file_exists(PATH_CACHE_JS)) mkdir(PATH_CACHE_JS, 0755, true);

                                            copy($row['file_path'], PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.js');

                                            exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar '.PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.js -o '.PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js', $result);
                                            // further minify js, remove comments
                                            if (file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                            {
                                                $min_file = $format->minify_js(file_get_contents(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'));
                                                file_put_contents(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js',$min_file);
                                                // release memory from the temp file
                                                unset($min_file);
                                            }
                                        }
                                        // Double check if min.js is generated successfully
                                        if (file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                        {
                                            $row['content'] = $format->minify_js(file_get_contents(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'));
                                        }
                                        else
                                        {
                                            $row['src'] = $row['file_path'];
                                            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): load minified js script ['.PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js] failed';
                                        }
                                    }
                                    break;
                                case 'text_content':
                                    $row['content'] = $format->minify_js($row['content']);
                                    break;
                                case 'json-ld':
                                    $json_ld = array_merge($json_ld, $row['content']);
                                    unset($row['content']);
                                default:
                            }
                        }
                        if (isset($row['src'])) $page_script .= '</script><script type="text/javascript" src="'.$row['src'].'">';
                        if (isset($row['content'])) $page_script .= $row['content'];
                    }
                    if (!empty($page_script)) $page_script = '<script type="text/javascript">'.$page_script.'</script>';
                    $page_script = str_replace('<script type="text/javascript"></script>','',$page_script);
                    if ($json_ld) $page_script .= '<script type="application/ld+json">'.$format->minify_js(json_encode($json_ld)).'</script>';
                }
                else
                {
                    $json_ld = array();
                    foreach ($this->content['script'] as $row_index=>$row)
                    {
                        if (isset($row['type']))
                        {
                            switch ($row['type'])
                            {
                                case 'local_file':
                                    if (file_exists(PATH_CONTENT_JS.$row['file_name'].'.js'))
                                    {
                                        $file_version = strtolower(date('dMY', filemtime(PATH_CONTENT_JS.$row['file_name'].'.js')));
                                        if (!file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                        {
                                            if (!file_exists(PATH_CACHE_JS)) mkdir(PATH_CACHE_JS, 0755, true);
                                            exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar '.PATH_CONTENT_JS.$row['file_name'].'.js -o '.PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js', $result);
                                            // further minify js, remove comments
                                            if (file_exists(PATH_CACHE_JS.$row['file_name'].'.'.$file_version.'.min.js'))
                                            {
                                                $min_file = $format->minify_js(file_get_contents(PATH_CACHE_JS . $row['file_name'] . '.' . $file_version . '.min.js'));
                                                file_put_contents(PATH_CACHE_JS . $row['file_name'] . '.' . $file_version . '.min.js', $min_file);
                                                unset($min_file);   // release memory from the temp file
                                            }
                                        }
                                        $row['src'] = URI_CONTENT_JS.$row['file_name'].'.js';
                                    }
                                    else
                                    {
                                        $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): load source js script ['.PATH_CONTENT_JS.$row['file_name'].'.js] failed';
                                    }
                                    break;
                                case 'remote_file':
                                    $file_header = @get_headers($row['file_path']);
                                    if (strpos( $file_header[0], '200 OK' ) !== false)
                                    {
                                        $row['src'] = $row['file_path'];
                                        unset($file_header);
                                    }
                                    break;
                                case 'text_content':
                                    break;
                                case 'json-ld':
                                    $json_ld = array_merge($json_ld, $row['content']);
                                    unset($row['content']);
                                default:
                            }
                        }
                        if (isset($row['src']))
                        {
                            $page_script .= '
<script type="text/javascript" src="'.$row['src'].'">';
                        }
                        else
                        {
                            $page_script .= '
<script type="text/javascript">';
                        }
                        if (isset($row['content'])) $page_script .= $row['content'];
                        $page_script .= '</script>';
                    }
                    $page_script = str_replace('<script type="text/javascript"></script>','',$page_script);
                    if ($json_ld) $page_script .= '<script type="application/ld+json">'.json_encode($json_ld).'</script>';
                }
                $this->content['html'] = str_replace('[[+script]]',$page_script,$this->content['html']);
                unset($page_script);
            }

            $page_style = '';
            if ($GLOBALS['global_preference']->minify_css)
            {
                foreach ($this->content['style'] as $row_index=>$row)
                {
                    if (isset($row['type']))
                    {
                        switch ($row['type'])
                        {
                            case 'local_file':
                                if (file_exists(PATH_CONTENT_CSS.$row['file_name'].'.css'))
                                {
                                    $file_version = strtolower(date('dMY', filemtime(PATH_CONTENT_CSS.$row['file_name'].'.css')));
                                    if (!file_exists(PATH_CONTENT_CSS.$row['file_name'].'.'.$file_version.'.min.css'))
                                    {
                                        if (!file_exists(PATH_CACHE_CSS)) mkdir(PATH_CACHE_CSS, 0755, true);
                                        exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar '.PATH_CONTENT_CSS.$row['file_name'].'.css -o '.PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css', $result);
                                        // further minify css, remove comments
                                        if (file_exists(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css'))
                                        {
                                            $min_file = $format->minify_css(file_get_contents(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css'));
                                            // replace all relative path to absolute path in css as file location changes
                                            $min_file = str_replace('../',URI_CONTENT,$min_file);
                                            // update min file
                                            file_put_contents(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css',$min_file);
                                            // release memory from the temp file
                                            unset($min_file);
                                        }
                                    }
                                    // Double check if min.css is generated successfully
                                    if (file_exists(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css'))
                                    {
                                        $row['content'] = file_get_contents(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css');
                                    }
                                    else
                                    {
                                        $row['src'] = URI_CONTENT_CSS.$row['file_name'].'.css';
                                        $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): load minified css script ['.PATH_CONTENT_CSS.$row['file_name'].'.'.$file_version.'.min.css] failed';
                                    }
                                }
                                else
                                {
                                    $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): load source css script ['.PATH_CONTENT_CSS.$row['file_name'].'.css] failed';
                                }
                                break;
                            case 'remote_file':
                                // cross domain css is normally forbidden
                                break;
                            case 'text_content':
                                $row['content'] = $format->minify_css($row['content']);
                                break;
                            default:
                        }
                    }
                    if (isset($row['src'])) $page_style .= '<link href="'.$row['src'].'" rel="stylesheet" type="text/css">';
                    if (isset($row['content'])) $page_style .= '<style>'.$format->minify_css($row['content']).'</style>';
                }
                $page_style = str_replace('</style><style>','',$page_style);
            }
            else
            {
                foreach ($this->content['style'] as $row_index=>$row)
                {
                    if (isset($row['type']))
                    {
                        switch ($row['type'])
                        {
                            case 'local_file':
                                if (file_exists(PATH_CONTENT_CSS.$row['file_name'].'.css'))
                                {
                                    $file_version = strtolower(date('dMY', filemtime(PATH_CONTENT_CSS.$row['file_name'].'.css')));
                                    if (!file_exists(PATH_CONTENT_CSS.$row['file_name'].'.'.$file_version.'.min.css'))
                                    {
                                        if (!file_exists(PATH_CACHE_CSS)) mkdir(PATH_CACHE_CSS, 0755, true);
                                        exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar '.PATH_CONTENT_CSS.$row['file_name'].'.css -o '.PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css', $result);
                                        // further minify css, remove comments
                                        if (file_exists(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css'))
                                        {
                                            $min_file = $format->minify_css(file_get_contents(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css'));
                                            // replace all relative path to absolute path in css as file location changes
                                            $min_file = str_replace('../',URI_CONTENT,$min_file);
                                            // update min file
                                            file_put_contents(PATH_CACHE_CSS.$row['file_name'].'.'.$file_version.'.min.css',$min_file);
                                            // release memory from the temp file
                                            unset($min_file);
                                        }
                                    }
                                    $row['src'] = URI_CONTENT_CSS.$row['file_name'].'.css';
                                }
                                else
                                {
                                    $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): load source css script ['.PATH_CONTENT_CSS.$row['file_name'].'.css] failed';
                                }
                                break;
                            case 'remote_file':
                                // cross domain css is normally forbidden
                                break;
                            case 'text_content':
                                $row['content'] = $format->minify_css($row['content']);
                                break;
                            default:
                        }
                    }
                    if (isset($row['src'])) $page_style .= '
<link href="'.$row['src'].'" rel="stylesheet" type="text/css">';
                    if (isset($row['content'])) $page_style .= '
<style>'.$row['content'].'</style>';
                }
                $page_style = str_replace('</style>
<style>','',$page_style);
            }
            if (substr($this->parameter['namespace'], strlen($this->parameter['namespace'])-4) == '-amp')
            {
                $page_style = str_replace('<style>','<style amp-custom>',$page_style);
            }
            $this->content['html'] = str_replace('[[+style]]',$page_style,$this->content['html']);
            unset($page_style);

            $this->set_cache();
        }

        if ($this->parameter['instance'] == 'ajax-load')
        {
            if ($this->parameter['debug_mode'] == true)
            {
                $this->content['system_debug'] = $GLOBALS['global_message']->display();
            }
            $ajax_result = json_encode($this->content);
            if (isset($_POST['data_encode_type']))
            {
                if ($_POST['data_encode_type'] == 'base64')
                {
                    $ajax_result = base64_encode($ajax_result);
                }
            }
            print_r($ajax_result);
            return true;
        }
        else
        {
            if ($this->parameter['debug_mode'] == true)
            {
                echo '<div class="system_debug wrapper"><div class="system_debug_row container">';
                print_r(json_encode($GLOBALS['global_message']->display()));
                echo '</div></div>';
            }
            print_r($this->content['html']);
            return true;
        }
    }
}