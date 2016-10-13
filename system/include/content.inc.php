<?php
// Include Class Object
// Name: content
// Description: web page content functions

// Render template, create html page view...

class content {
    protected $request = array();
    protected $content = array();

    public $message;
    public $format;
    public $preference;


    //protected $cache = 0;
    //public $parameter = array();
    //public $content = array();

    function __construct($parameter = array())
    {
        $this->request = array();
        $this->message = message::get_instance();
        $this->format = format::get_obj();
        $this->preference = preference::get_instance();

        // Analyse uri structure and validate input variables, store separate input parts into $request
        if ($this->request_decoder($parameter) === false)
        {
            // TODO: Error Log, error during reading input uri and parameters
            return false;
        }
print_r('request_decoder: <br>');
print_r($this);

        // Generate the necessary components for the content, store separate component parts into $content
        // Read data from database (if applicable), only generate raw data from db
        // If any further complicate process required, leave it to render
        if ($this->build_content() === false)
        {
            // TODO: Error Log, error during building data object
            return false;
        }
print_r('build_content: <br>');
print_r($this);

        // Processing file, database and etc (basically whatever time consuming, process it here)
        // As some rendering methods may only need the raw data without going through all the file copy, modify, generate processes
        return $this->render();
    }

    private function request_decoder($value)
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
            unset($_GET);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            // default post request with json format Input and Output
            $option = array_merge($option,$_POST);
            if (!isset($option['data_type'])) $option['data_type'] = 'json';
            unset($_POST);
        }

        $preference = preference::get_instance();
        $message = message::get_instance();

        $request_uri = trim(preg_replace('/^[\/]?'.FOLDER_SITE_BASE.'[\/]/','',$value),'/');
        $request_path = explode('/',$request_uri);

        $type = ['css','image','js','json'];
        $request_path_part = array_shift($request_path);
        if (in_array($request_path_part,$type))
        {
            $this->request['data_type'] = $request_path_part;
            $this->request['file_uri'] = URI_ASSET.$this->request['data_type'].'/';
        }
        else
        {
            $this->request['data_type'] = 'html';
            $this->request['file_uri'] = URI_SITE_BASE;
        }
        $this->request['file_path'] = PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR;

        // HTML Page uri structure decoder
        switch ($this->request['data_type'])
        {
            case 'css':
            case 'js':
                if (empty($request_path))
                {
                    // TODO: css/js folder forbid direct access
                    return false;
                }
                $file_extension = ['min'];
                $file_name = array_pop($request_path);
                $file_part = explode('.',$file_name);
                $this->request['document'] = array_shift($file_part);
                if (!empty($file_part)) $this->request['file_type'] = array_pop($file_part);
                $this->request['extension'] = [];
                while (in_array(end($file_part),$file_extension))
                {
                    $this->request['extension'][] = array_pop($file_part);
                }
                asort($this->request['extension']);
                if (!empty($file_part))
                {
                    // Put the rest part that is not an extension back to document name, e.g. jquery-1.11.8.min.js
                    $this->request['document'] .= '.'.implode('.',$file_part);
                }
                unset($file_part);

                $this->request['sub_path'] = $request_path;

                if (!empty($this->request['sub_path']))
                {
                    $this->request['file_path'] .= implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR;
                    $this->request['file_uri'] .= implode('/',$this->request['sub_path']).'/';
                }
                $this->request['file_path'] .= $file_name;
                $this->request['file_uri'] .= $file_name;


                unset($file_name);
                break;
            case 'html':
                //$request_path_part = array_shift($request_path);
                $module = ['listing','business','business-amp'];
                if (in_array($request_path_part,$module))
                {
                    $this->request['module'] = $request_path_part;
                    $request_path_part = array_shift($request_path);
                }
                else
                {
                    $this->request['module'] = '';
                }

                switch ($this->request['module'])
                {
                    case 'listing':
                        $method = ['search','find',''];
                        if (in_array($request_path_part,$method))
                        {
                            $this->request['method'] = $request_path_part;
                            $request_path_part = array_shift($request_path);
                        }
                        else
                        {
                            $this->request['method'] = end($method);
                        }

                        switch ($this->request['method'])
                        {
                            case 'search':
                                $this->request['option'] = array('keyword'=> $request_path_part);
                                if (count($request_path)>=2)
                                {
                                    $option = ['where','screen','sort'];
                                    $path_max = floor(count($request_path)/2);
                                    for ($i=0; $i<$path_max; $i++)
                                    {
                                        if (!in_array( $request_path[$i*2],$option))
                                        {
                                            $message->error = __FILE__.'(line '.__LINE__.'): Construction Fail, unknown option ['.$request_path[$i*2].'] for '.$this->request['data_type'];
                                            break 2;
                                        }
                                        $this->request['option'][$request_path[$i*2]] = $request_path[$i*2+1];
                                    }
                                }
                                break;
                            case 'find':
                                $this->request['option'] = array('category'=> $request_path_part);
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
                                    $this->request['option'][$location[$request_path_part_index]] = $request_path_part;
                                }
                                if (count($request_path)>=2)
                                {
                                    $path_max = floor(count($request_path)/2);
                                    for ($i=0; $i<$path_max; $i++)
                                    {
                                        if (!in_array( $request_path[$i*2],$option))
                                        {
                                            $message->error = __FILE__.'(line '.__LINE__.'): Construction Fail, unknown option ['.$request_path[$i*2].'] for '.$this->request['data_type'];
                                            break 2;
                                        }
                                        $this->request['option'][$request_path[$i*2]] = $request_path[$i*2+1];
                                    }
                                }
                                break;
                            default:
                                //$this->request['document'] = $request_path_part;
                        }
                        break;
                    default:
                        $this->request['document'] = $request_path_part;
                }

                break;
            case 'image':
                if (empty($request_path))
                {
                    // TODO: image folder forbid direct access
                    return false;
                }
                $image_size = array_keys($this->preference->image['size']);;
                $file_name = array_pop($request_path);
                $file_part = explode('.',$file_name);
                $this->request['document'] = array_shift($file_part);
                if (!empty($file_part)) $this->request['file_type'] = array_pop($file_part);
                $this->request['extension'] = [];
                if (in_array(end($file_part),$image_size))
                {
                    $this->request['image_size'] = array_pop($file_part);
                }
                if (!empty($file_part))
                {
                    // Put the rest part that is not an extension back to document name, e.g. jquery-1.11.8.min.js
                    $this->request['document'] .= '.'.implode('.',$file_part);
                }
                unset($file_part);

                if (!empty($request_path))
                {
                    $this->request['file_path'] .= implode(DIRECTORY_SEPARATOR,$request_path).DIRECTORY_SEPARATOR;
                    $this->request['file_uri'] .= implode('/',$request_path).'/';

                    $this->request['sub_path'] = $request_path;
                }

                $this->request['file_path'] .= $file_name;
                $this->request['file_uri'] .= $file_name;
                break;
            case 'json':
                break;
        }

        $option_preset = ['data_type','module','document','field','template','render'];
        foreach($option as $key=>$item)
        {
            // Options from GET, POST overwrite ones decoded from uri
            if (in_array($key,$option_preset))
            {
                $this->request[$key] = $item;
                unset($option[$key]);
            }
        }
        // dump the rest custom/unrecognized input variables into $request['option']
        $this->request['option'] = $option;
    }

    private function build_content()
    {
        $format = format::get_obj();
        $preference = preference::get_instance();

        if (!isset($this->request['format'])) $this->content['format'] = 'default';
        else $this->content['format'] = $this->request['format'];

        switch($this->request['data_type'])
        {
            case 'css':
            case 'js':
                if (isset($this->request['source_file']))
                {
                    $this->content['source_file'] = ['file'=>$this->request['source_file']];
                }
                else
                {
                    $source_file = $this->request['document'].'.'.$this->request['file_type'];
                    if (!empty($this->request['sub_path'])) $source_file = implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$source_file;
                    if (!empty($this->request['extension']))
                    {
                        // If requiring for file with extension, e.g. minimized js or css as .min.js/.min.css, first check if the original version is in the cache folder
                        if (file_exists(PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$source_file))
                        {
                            $source_file = PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$source_file;
                            $this->content['source_file'] = ['file'=>$source_file];
                        }
                    }

                    if (!isset($this->content['source_file']))
                    {
                        $this->content['source_file'] = ['file'=>PATH_CONTENT.$this->request['data_type'].DIRECTORY_SEPARATOR.$source_file];
                        if (!file_exists($this->content['source_file']['file']))
                        {
                            // TODO: Error Handling, last ditch failed, source file does not exist in content folder either
                            return false;
                        }
                    }

                    $this->content['source_file']['source'] = 'local_file';
                }

                if (isset($this->request['source_file_type']))
                {
                    $this->content['source_file']['source'] = $this->request['source_file_type'];
                }
                else
                {
                    if ((strpos($this->content['source_file']['file'],URI_SITE_BASE) == FALSE)  AND (preg_match('/^http/',$this->content['source_file']['file']) == 1))
                    {
                        // If source_file is not relative uri and not start with current site uri base, it is an external (cross domain) source file
                        $this->content['source_file']['source'] = 'remote_file';
                    }
                    else
                    {
                        $this->request['source_file']['file'] = str_replace(URI_SITE_BASE,PATH_SITE_BASE,$this->request['source_file']['file']);
                        $this->content['source_file']['source'] = 'local_file';
                    }
                }

                break;
            case 'image':
                $default_file = $this->request['document'];
                if (!empty($this->request['sub_path'])) $default_file = implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$default_file;
                if (isset($this->request['image_size']))
                {
                    $this->content['target_file'] = [
                        'path'=>PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$default_file.'.'.$this->request['image_size'].'.'.$this->request['file_type']
                    ];
                }
                $this->content['default_file'] = [
                    'path'=>PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$default_file.'.'.$this->request['file_type']
                ];
                $this->content['source_file'] = [];

                if (file_exists($this->content['default_file']['path']))
                {
                    // If default version exists in cache folder, ignore the rest of analysis, set default_file as source_file
                    $this->content['source_file'] = [
                        'source'=>'local_file',
                        'path'=>$this->content['default_file']['path']
                    ];
                }
                else
                {
                    // Try to locate the source image
                    if (isset($this->request['source_file']))
                    {
                        // Set source_file if it is directly provided through request, normally for server side request or ajax request
                        if (preg_match('/^http/',$this->request['source_file']) == 1)
                        {
                            $this->content['source_file']['uri'] = $this->request['source_file'];
                            if (strpos($this->request['source_file']['file'],URI_SITE_BASE) == FALSE)
                            {
                                $this->content['source_file']['source'] = 'remote_file';
                                $this->content['source_file']['path'] = PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$this->request['document'].'.src.'.$this->request['file_type'];;
                            }
                            else
                            {
                                // Request local file through URI, change it to absolute path
                                $this->content['source_file']['source'] = 'local_file';
                                $this->content['source_file']['path'] = str_replace(URI_SITE_BASE,PATH_SITE_BASE,$this->content['source_file']['uri']);
                            }
                        }
                    }
                    else
                    {
                        // Nothing is set, have to guess source_file here, start with local file in content folder
                        $content_folder_path = PATH_CONTENT.$this->request['data_type'].DIRECTORY_SEPARATOR;
                        if (!empty($this->request['sub_path'])) $content_folder_path .= implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR;
                        if (file_exists($content_folder_path.$this->request['document'].'.'.$this->request['file_type']))
                        {
                            $this->content['source_file'] = [
                                'source'=>'local_file',
                                'path'=>$content_folder_path.$this->request['document'].'.'.$this->request['file_type']
                            ];
                        }
                        else
                        {
                            // If image doesn't exist in content folder, try database
                            $this->content['source_file']['source'] = 'local_data';
                            $document_name_part = explode('-',$this->request['document']);
                            $document_id = end($document_name_part);
                            if (empty($document_id) OR !is_numeric($document_id))
                            {
                                // TODO: Error Handling, fail to get source file from database, last part of file name is not a valid id
                                return false;
                            }
                            print_r($document_id);
                            $entity_image_obj = new entity_image($document_id);
                            if (empty($entity_image_obj->row))
                            {
                                // TODO: Error Handling, fail to get source file from database, cannot find matched record
                                return false;
                            }
                            //$entity_image_obj->generate_cache_file();
                            $this->content['source_file']['object'] = &$entity_image_obj;
                        }
                    }
                }
                break;
            case 'json':
                break;
            case 'html':
            default:
                $resources_loader = [];
                $resources_loader[] = ['value'=>'/js/jquery-1.11.3.min.js','option'=>['source_file_type'=>'local_file','render'=>'source_uri']];
                $resources_loader[] = ['value'=>'/js/default.min.js','option'=>['source_file_type'=>'local_file','render'=>'source_uri']];
                $resource_render = [];
                foreach($resources_loader as $resource_index=>$resource)
                {
                    $resource_render[] = new content($resource);
                }
                print_r($resource_render);



                //$this->content['script'][] = array('type'=>'local_file', 'file_name'=>'jquery-1.11.3');
                //$this->content['script'][] = array('type'=>'local_file', 'file_name'=>'default');

                switch($this->request['module'])
                {
                    case 'listing':
                        break;
                    case '':
                    default:
                        if (!isset($this->request['document']))
                        {
                            include(PATH_SITE_BASE.'404.php');
                            //header("HTTP/1.0 404 Not Found");
                            //header('Location: '.URI_SITE_BASE.'404.php');
                        }
                        $page_obj = new view_web_page($this->request['document']);
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
                            // TODO: Error Handling, cannot fetch page content
                            include(PATH_SITE_BASE.'404.php');
                        }
                        else
                        {
                            $this->request['field'] = $page_fetched_value[0];
                            $this->request['template'] = 'page_default';
                        }

                }
        }

        //print_r($page_field);
        //print_r($GLOBALS['global_message']->display());
        return true;














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

    function render($parameter = array())
    {
        $format = format::get_obj();
        $preference = preference::get_instance();

        switch($this->request['data_type'])
        {
            case 'css':
            case 'js':
                switch ($this->request['render'])
                {
                    case 'source_uri':
                        return $this->request['file_uri'];
                        break;
                    default:
                        if ($this->request['source_file_type'] == 'remote_file')
                        {
                            // External source file
                            $file_header = @get_headers($this->request['source_file'],true);
                            if (strpos( $file_header[0], '200 OK' ) === false) {
                                // TODO: Error Handling, fail to get external source file header
                                return false;
                            }
                            if (isset($file_header['Last-Modified'])) {
                                $this->request['source_file_update'] = strtotime($file_header['Last-Modified']);
                            } else {
                                if (isset($file_header['Expires'])) {
                                    $this->request['source_file_update'] = strtotime($file_header['Expires']);
                                } else {
                                    if (isset($file_header['Date'])) $this->request['source_file_update'] = strtotime($file_header['Date']);
                                    else $this->request['source_file_update'] = ('+1 day');
                                }
                            }
                            if (isset($file_header['Content-Length']))
                            {
                                $this->request['source_file_size'] = $file_header['Content-Length'];
                                if ($this->request['source_file_size'] > 10485760)
                                {
                                    // TODO: Error Handling, source file too big
                                    return false;
                                }
                            }
                        }
                        else
                        {
                            if (!file_exists($this->request['source_file']))
                            {
                                // TODO: Error Handling, fail to get local source file
                                return false;
                            }
                            $this->request['source_file_update'] = filemtime($this->request['source_file']);
                            $this->request['source_file_size'] = filesize($this->request['source_file']);
                        }

                        $target_file_path = PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR;
                        if (!empty($this->request['sub_path'])) $target_file_path .= implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR;
                        if (!file_exists($target_file_path)) mkdir($target_file_path, 0755, true);

                        $source_file = $target_file_path.$this->request['document'].'.src.'.$this->request['file_type'];
                        copy($this->request['source_file'],$source_file);

                        $target_file = $target_file_path.$this->request['document'];
                        if (!empty($this->request['extension'])) $target_file = $target_file.'.'.implode('.',$this->request['extension']);
                        if (!empty($this->request['file_type'])) $target_file = $target_file.'.'.$this->request['file_type'];

                        if (file_exists($target_file))
                        {
                            // Requested file probably already exist due to request uri is different from the target uri
                            if ($this->request['source_file_update'] > filemtime($target_file))
                            {
                                // TODO: Force regenerate cache file if source file has been updated
                            }
                        }

                        if (in_array('min',$this->request['extension']))
                        {
                            // Yuicompressor 2.4.8 does not support output as Windows absolute path start with Driver
                            $start_time = microtime(true);
                            exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar "'.$source_file.'" -o "'.preg_replace('/^\w:/','',$target_file).'"', $result);
                            print_r('Yuicompressor Execution Time: '. (microtime(true) - $start_time) . '<br>');

                        }

                        if (!file_exists($target_file))
                        {
                            // If fail to generate minimized file, copy the source file
                            copy($source_file, $target_file);
                        }
                        else
                        {
                            if (filesize($target_file) > $this->request['source_file_size'])
                            {
                                // If file getting bigger, original file probably already minimized with better algorithm (e.g. google's js files, just use the original file)
                                copy($source_file, $target_file);
                            }
                        }

                        if (end($this->request['extension']) == 'min')
                        {
                            $start_time = microtime(true);
                            file_put_contents($target_file,minify_content(file_get_contents($target_file),$this->request['data_type']));
                            print_r('PHP Minifier Execution Time: '. (microtime(true) - $start_time) . '<br>');
                        }

                        // remove the copied source file
                        unlink($source_file);

                        // TODO: On Direct Rendering from HTTP REQUEST, if request_uri is different from target file_uri, do 301 redirect

                        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                        header('Content-Length: '.filesize($this->request['file_path']));

                        switch ($this->request['data_type'])
                        {
                            case 'css':
                                header("Content-Type: text/css");
                                break;
                            case 'js':
                                header("Content-Type: application/javascript");
                                break;
                            default:
                        }

                        readfile($this->request['file_path']);
                }
                break;
            case 'html':
                print_r(render_html($this->request['field'],$this->request['template']));
                break;
            case 'image':
                switch ($this->content['format'])
                {
                    case 'source_uri':
                        return $this->request['file_uri'];
                        break;
                    default:
                        // default to request for image file
                        switch($this->content['source_file']['type'])
                        {
                            case 'local_file':
                                if (!isset($this->content['source_file']['path']) OR !file_exists($this->content['source']['path']))
                                {
                                    // TODO: Error Handling, fail to get local source file on rendering
                                    $this->message->error = 'Rendering: Local Source File path not set or file does not exist';
                                    return false;
                                }
                                $this->content['source_file']['update'] = filemtime($this->content['source']['path']);
                                $this->content['source_file']['size'] = filesize($this->content['source']['path']);
                                $source_image_size = getimagesize($this->content['source']['path']);
                                $this->content['source_file']['width'] = $source_image_size[0];
                                $this->content['source_file']['height'] = $source_image_size[1];
                                if (isset($source_image_size['mime'])) $this->content['source_file']['mime'] = $source_image_size['mime'];
                                break;
                            case 'remote_file':
                                // External source file
                                if (!isset($this->content['source_file']['uri']))
                                {
                                    // TODO: Error Handling, fail to get remote source file on rendering
                                    $this->message->error = 'Rendering: Remote Source File uri not set';
                                    return false;
                                }
                                $file_header = @get_headers($this->content['source_file']['uri'],true);
                                if (strpos( $file_header[0], '200 OK' ) === false) {
                                    // TODO: Error Handling, fail to get remote source file on rendering
                                    $this->message->error = 'Rendering: Fail to retrieve remote file ['.$file_header[0].']';
                                    return false;
                                }
                                if (isset($file_header['Last-Modified'])) {
                                    $this->content['source_file']['update'] = strtotime($file_header['Last-Modified']);
                                } else {
                                    if (isset($file_header['Expires'])) {
                                        $this->content['source_file']['update'] = strtotime($file_header['Expires']);
                                    } else {
                                        if (isset($file_header['Date'])) $this->content['source_file']['update'] = strtotime($file_header['Date']);
                                        else $this->content['source_file']['update'] = date('+1 day');
                                    }
                                }
                                if (isset($file_header['Content-Length']))
                                {
                                    $this->content['source_file']['size'] = $file_header['Content-Length'];
                                    if ($this->content['source_file']['size'] > 10485760)
                                    {
                                        // TODO: Error Handling, source file too big
                                        $this->message->error = 'Rendering: Fail to retrieve remote file, remote file size too big ['.$this->content['source_file']['size'].']';
                                        return false;
                                    }
                                }
                                if (isset($file_header['Content-Type']))
                                {
                                    $this->content['source_file']['mime'] = strtolower($file_header['Content-Type']);
                                    $image_mime_type = ['image/png','image/gif','image/jpg','image/jpeg'];
                                    if (!in_array($this->content['source_file']['mime'],$image_mime_type))
                                    {
                                        // TODO: Error Handling, source file type not acceptable
                                        $this->message->error = 'Rendering: Fail to retrieve remote file, remote file type not acceptable ['.$this->content['source_file']['mime'].']';
                                        return false;
                                    }
                                }
                                copy($this->content['source_file']['uri'],$this->content['source_file']['path']);
                                if (!isset($this->content['source_file']['size'])) $this->content['source_file']['size'] = filesize($this->content['source_file']['path']);
                                $source_image_size = getimagesize($this->content['source']['path']);
                                $this->content['source_file']['width'] = $source_image_size[0];
                                $this->content['source_file']['height'] = $source_image_size[1];
                                if (!isset($this->content['source_file']['mime']) AND isset($source_image_size['mime'])) $this->content['source_file']['mime'] = $source_image_size['mime'];

                                break;
                            case 'local_data':
                                if (!isset($this->content['source_file']['entity']))
                                {
                                    // TODO: Error Handling, fail to get file from database on rendering
                                    $this->message->error = 'Rendering: Local Database Source File entity not set';
                                    return false;
                                }
                                $entity_image_obj = &$this->content['source_file']['entity'];
                                if (empty($entity_image_obj->row))
                                {
                                    // TODO: Error Handling, fail to get source file from database, cannot find matched record
                                    $this->message->error = 'Rendering: Local Database Source File entity set, but data row is not set in entity';
                                    return false;
                                }
                                // Generate default image from db data
                                $entity_image_obj->generate_cache_file();

                                if ($this->content['default_file']['path'] != $entity_image_obj->row[0]['file'])
                                {
                                    // TODO: Error Handling, request file_path is not consistent with entity_image file_path
                                    $this->message->notice = 'Rendering: Local Database generate image path is different from request image path [Request:'.$this->content['default_file']['path'].';Generated:'.$entity_image_obj->row[0]['file'].']';
                                    $this->content['default_file']['path'] = $entity_image_obj->row[0]['file'];
                                }

                                if (!file_exists($this->content['default_file']['path']))
                                {
                                    // TODO: Error Handling, fail to get local default file from db
                                    $this->message->error = 'Rendering: Local Database fail to generate default size image ['.$this->content['default_file']['path'].']';
                                    return false;
                                }

                                $this->content['source_file']['path'] = $this->content['default_file']['path'];
                                $this->content['source_file']['size'] = filesize($this->request['source_file']['path']);
                                $this->content['source_file']['update'] = $entity_image_obj->row[0]['update_time'];
                                $this->content['source_file']['width'] = $entity_image_obj->row[0]['width'];
                                $this->content['source_file']['height'] = $entity_image_obj->row[0]['height'];
                                $this->content['source_file']['mime'] = $entity_image_obj->row[0]['mime'];
                                break;
                            default:
                        }

                        switch ($this->content['source_file']['mime']) {
                            case 'image/png':
                                $source_image = imagecreatefrompng($this->content['source_file']['path']);
                                break;
                            case 'image/gif':
                                $source_image = imagecreatefromgif($this->content['source_file']['path']);
                                break;
                            case 'image/jpg':
                            case 'image/jpeg':
                                $source_image = imagecreatefromjpeg($this->content['source_file']['path']);
                                break;
                            default:
                                $source_image = imagecreatefromstring($this->content['source_file']['path']);
                        }
                        if ($source_image === FALSE) {
                            // TODO: Error Handling, fail to create image
                            return false;
                        }

                        if ($this->content['source_file']['type'] == 'remote_file')
                        {
                            // Create local default from remote file first
                            if ($this->content['source_file']['width'] > max($this->preference->image['size']))
                            {
                                // If source image too large, try to resize it before save to default
                                $this->content['default_file']['width'] = max($this->preference->image['size']);
                                $this->content['default_file']['height'] = $this->content['source_file']['height'] / $this->content['source_file']['width'] * $this->content['default_file']['width'];
                                $default_image = imagecreatetruecolor($this->content['default_file']['width'],  $this->content['default_file']['height']);
                                imagecopyresampled($default_image,$source_image,0,0,0,0,$this->content['default_file']['width'], $this->content['default_file']['height'],$this->content['source_file']['width'],$this->content['source_file']['height']);
                            }
                            else
                            {
                                $default_image = $source_image;
                                $this->content['default_file']['width'] = $this->content['source_file']['width'];
                                $this->content['default_file']['height'] = $this->content['source_file']['height'];
                            }
                            // Save Default Image with Max Quality Set
                            $image_quality = $this->preference->image['quality']['max'];
                            switch($this->content['source_file']['mime'])
                            {
                                case 'image/png':
                                    imagesavealpha($default_image, true);
                                    imagepng($default_image, $this->content['default_file']['path'], $image_quality['image/png'][0], $image_quality['image/png'][1]);
                                    break;
                                case 'image/gif':
                                    // Resampled gif will lose animation, so save it as jpeg instead
                                    // imagegif($default_image, $default_image_path);
                                    // break;
                                case 'image/jpg':
                                case 'image/jpeg':
                                default:
                                    imagejpeg($default_image, $this->content['default_file']['path'], $image_quality['image/jpeg']);
                            }
                            $this->content['default_file']['size'] = filesize($this->request['default_file']['path']);
                            if ($this->content['default_file']['size'] > $this->content['source_file']['size'])
                            {
                                // If somehow resized image getting bigger in size, just overwrite it with original file
                                copy($this->request['source_file']['path'],$this->request['default_file']['path']);
                            }
                            unlink($this->request['source_file']['path']);
                        }


                        if (file_exists(PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$this->request['document'].'.'.$this->request['file_type']))
                        {
                            $source_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$this->request['document'].'.'.$this->request['file_type'];
                            $source_image_size = getimagesize($source_image_path);
                            if ($source_image_size === false)
                            {
                                // TODO: Error Handling, fail to get source image size
                                return false;
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
                                return false;
                                //header('Location: ' . URI_SITE_BASE . '/content/image/img_listing_default_280_280.jpg');
                                //exit();
                            }
                        }
                        else
                        {
                            $default_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$this->request['document'].'.'.$this->request['file_type'];
                            // Check whether image file with same name exists in content folder
                            if (file_exists(PATH_CONTENT_IMAGE.implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$this->request['document'].'.'.$this->request['file_type'])) {
                                $source_image_path = PATH_CONTENT_IMAGE . implode(DIRECTORY_SEPARATOR, $this->request['sub_path']) . DIRECTORY_SEPARATOR . $this->request['document'] . '.' . $this->request['file_type'];
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
                                $document_name_part = explode('_',$this->request['document']);
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
                                // consider the image is already optimized before saved into database, directly write the data stream into physical file
                                file_put_contents($default_image_path, $entity_image_obj->row[0]['data']);
                                unset($default_image_path);
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
                            if (!file_exists(dirname($default_image_path))) mkdir(dirname($default_image_path), 0755, true);

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
                            unset($default_image_path);
                        }

                        if (isset($this->request['size']))
                        {
                            if (!in_array($this->request['size'],array_keys($preference->image['size'])))
                            {
                                // TODO: Error Handling, image size is not defined in global preference
                                break;
                            }
                            $target_image_path = PATH_IMAGE.implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$this->request['size'].DIRECTORY_SEPARATOR.$this->request['document'].'.'.$this->request['file_type'];
                            if (!file_exists(dirname($target_image_path))) mkdir(dirname($target_image_path), 0755, true);

                            $target_image_size = array(
                                $preference->image['size'][$this->request['size']],
                                round($source_image_size[1] / $source_image_size[0] * $preference->image['size'][$this->request['size']])
                            );
                            $target_image = imagecreatetruecolor($target_image_size[0], $target_image_size[1]);
                            imagecopyresampled($target_image,$source_image,0,0,0,0,$target_image_size[0], $target_image_size[1],$source_image_size[0],$source_image_size[1]);

                            imageinterlace($target_image,true);

                            // default display image generate with fast speed
                            $image_quality = $preference->image['quality']['spd'];
                            if (!empty($this->request['extension']))
                            {
                                if (in_array(end($this->request['extension']),$preference->image['quality']))
                                {
                                    $image_quality = $preference->image['quality'][end($this->request['extension'])];
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
                                    imagejpeg($target_image, $target_image_path, $image_quality['image/jpeg']);
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

                }

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


    function render_old($parameter = array())
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