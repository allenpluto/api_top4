<?php
// Include Class Object
// Name: content
// Description: web page content functions

// Render template, create html page view...

class content {
    public $status;

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
            $this->message->error = 'Fail: Error during request_decoder';
            $this->status = 'fail';
            return $this;
        }
//print_r('request_decoder: <br>');
//print_r($this);

        // Generate the necessary components for the content, store separate component parts into $content
        // Read data from database (if applicable), only generate raw data from db
        // If any further complicate process required, leave it to render
        if ($this->build_content() === false)
        {
            // TODO: Error Log, error during building data object
            $this->message->error = 'Fail: Error during build_content';
            $this->status = 'fail';
            return $this;
        }
//print_r('build_content: <br>');
//print_r($this);

        // Processing file, database and etc (basically whatever time consuming, process it here)
        // As some rendering methods may only need the raw data without going through all the file copy, modify, generate processes
        if ($this->render() === false)
        {
            // TODO: Error Log, error during rendering
            $this->message->error = 'Fail: Error during render';
            $this->status = 'fail';
            return $this;
        }

        $this->status = 'OK';
    }

    private function request_decoder($value = '')
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

                $file_name = array_pop($request_path);
                $file_part = explode('.',$file_name);
                $this->request['document'] = array_shift($file_part);
                if (!empty($file_part)) $this->request['file_type'] = array_pop($file_part);
                $this->request['extension'] = [];
                $file_extension = ['min'];
                if (in_array(end($file_part),$file_extension))
                {
                    $this->request['extension']['minify'] = array_pop($file_part);
                }
                //asort($this->request['extension']);
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
                                            $this->message->error = __FILE__.'(line '.__LINE__.'): Construction Fail, unknown option ['.$request_path[$i*2].'] for '.$this->request['data_type'];
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
                                            $this->message->error = __FILE__.'(line '.__LINE__.'): Construction Fail, unknown option ['.$request_path[$i*2].'] for '.$this->request['data_type'];
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
                    $this->request['extension']['image_size'] = array_pop($file_part);
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

        $option_preset = ['data_type','document','file_type','extension','module','template','render'];
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
        if (!isset($this->request['format'])) $this->content['format'] = 'default';
        else $this->content['format'] = $this->request['format'];

        switch($this->request['data_type'])
        {
            case 'css':
            case 'js':
                if (isset($this->request['source_file']))
                {
                    $this->content['source_file'] = [
                        'file'=>$this->request['source_file']
                    ];
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
                            $this->content['source_file'] = [
                                'file'=>$source_file
                            ];
                        }
                    }

                    if (!isset($this->content['source_file']))
                    {
                        $this->content['source_file'] = [
                            'file'=>PATH_CONTENT.$this->request['data_type'].DIRECTORY_SEPARATOR.$source_file
                        ];
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
                        $this->content['source_file']['file'] = str_replace(URI_SITE_BASE,PATH_SITE_BASE,$this->content['source_file']['file']);
                        $this->content['source_file']['source'] = 'local_file';
                    }
                }

                break;
            case 'image':
                $default_file = $this->request['document'];
                if (!empty($this->request['sub_path'])) $default_file = implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR.$default_file;
                $this->content['default_file'] = [
                    'path'=>PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$default_file.'.'.$this->request['file_type']
                ];
                if (count($this->request['extension']) > 0)
                {
                    $this->content['target_file'] = [
                        'path'=>PATH_ASSET.$this->request['data_type'].DIRECTORY_SEPARATOR.$default_file.'.'.implode('.',$this->request['extension']).'.'.$this->request['file_type'],
                    ];
                    if (isset($this->request['extension']['image_size']))
                    {
                        if (!in_array($this->request['extension']['image_size'],array_keys($this->preference->image['size'])))
                        {
                            // TODO: Error Handling, image size is not defined in global preference
                            break;
                        }

                        $this->content['target_file']['width'] = $this->preference->image['size'][$this->request['extension']['image_size']];
                    }
                }
                else
                {
                    $this->content['target_file'] = [
                        'path'=>$this->content['default_file']['path']
                    ];

                }
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
                if (isset($_SERVER['HTTP_AUTH_KEY']))
                {
                    $entity_api_key_obj = new entity_api_key();
                    $auth_id = $entity_api_key_obj->validate_api_key($_SERVER['HTTP_AUTH_KEY']);
                    if ($auth_id === false)
                    {

                    }
                }
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
    }

    function render()
    {
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
                        // if content format not provided, default to request for image file

                        // copy/create source/default image files according to source_file type
                        switch($this->content['source_file']['source'])
                        {
                            case 'local_file':
                                if (!isset($this->content['source_file']['path']) OR !file_exists($this->content['source_file']['path']))
                                {
                                    // TODO: Error Handling, fail to get local source file on rendering
                                    $this->message->error = 'Rendering: Local Source File path not set or file does not exist';
                                    return false;
                                }
                                $this->content['source_file']['update'] = filemtime($this->content['source_file']['path']);
                                $this->content['source_file']['size'] = filesize($this->content['source_file']['path']);
                                $source_image_size = getimagesize($this->content['source_file']['path']);
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
                                $source_image_size = getimagesize($this->content['source_file']['path']);
                                $this->content['source_file']['width'] = $source_image_size[0];
                                $this->content['source_file']['height'] = $source_image_size[1];
                                if (!isset($this->content['source_file']['mime']) AND isset($source_image_size['mime'])) $this->content['source_file']['mime'] = $source_image_size['mime'];

                                break;
                            case 'local_data':
                                if (!isset($this->content['source_file']['object']))
                                {
                                    // TODO: Error Handling, fail to get file from database on rendering
                                    $this->message->error = 'Rendering: Local Database Source File entity not set';
                                    return false;
                                }
                                $entity_image_obj = &$this->content['source_file']['object'];
                                if (empty($entity_image_obj->row))
                                {
                                    // TODO: Error Handling, fail to get source file from database, cannot find matched record
                                    $this->message->error = 'Rendering: Local Database Source File entity set, but data row is not set in entity';
                                    return false;
                                }
                                // Generate default image from db data
                                $entity_image_obj->generate_cache_file();

                                if ($this->content['default_file']['path'] != end($entity_image_obj->row)['file'])
                                {
                                    // TODO: Error Handling, request file_path is not consistent with entity_image file_path
                                    $this->message->notice = 'Rendering: Local Database generate image path is different from request image path [Request:'.$this->content['default_file']['path'].';Generated:'.end($entity_image_obj->row)['file'].']';
                                    $this->content['default_file']['path'] = end($entity_image_obj->row)['file'];
                                }

                                if (!file_exists($this->content['default_file']['path']))
                                {
                                    // TODO: Error Handling, fail to get local default file from db
                                    $this->message->error = 'Rendering: Local Database fail to generate default size image ['.$this->content['default_file']['path'].']';
                                    return false;
                                }
                                $this->content['source_file']['path'] = end($entity_image_obj->row)['file'];
                                $this->content['source_file']['size'] = filesize($this->content['source_file']['path']);
                                $this->content['source_file']['update'] = strtotime(end($entity_image_obj->row)['update_time']);
                                $this->content['source_file']['width'] = end($entity_image_obj->row)['width'];
                                $this->content['source_file']['height'] = end($entity_image_obj->row)['height'];
                                $this->content['source_file']['mime'] = end($entity_image_obj->row)['mime'];
                                break;
                            default:
                        }

                        // create source file resource object
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

                        // If source file is remote (from other domain), Create local default file first
                        if ($this->content['source_file']['path'] != $this->content['default_file']['path'])
                        {
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
                                    imagegif($default_image, $this->content['default_file']['path']);
                                    break;
                                case 'image/jpg':
                                case 'image/jpeg':
                                default:
                                    imagejpeg($default_image, $this->content['default_file']['path'], $image_quality['image/jpeg']);
                            }
                            $this->content['default_file']['size'] = filesize($this->content['default_file']['path']);
                            if ($this->content['default_file']['size'] > $this->content['source_file']['size'])
                            {
                                // If somehow resized image getting bigger in size, just overwrite it with original file
                                copy($this->content['source_file']['path'],$this->content['default_file']['path']);
                            }
                            // Remove source file unless it was a local file (say high resolution images we keep in content folder)
                            if ($this->content['source_file']['type'] != 'local_file') unlink($this->content['source_file']['path']);
                            // Default image create process finish here, unset default file gd object
                            unset($default_image);
                        }
                        else
                        {
                            $this->content['default_file']['width'] = $this->content['source_file']['width'];
                            $this->content['default_file']['height'] = $this->content['source_file']['height'];
                        }

                        // At this point, source image gd object and default image file should be generated no matter which source it came from
                        // If requested file is not the default version, generate thumb according to specifications
                        if ($this->content['target_file']['path'] != $this->content['default_file']['path'])
                        {

                            $target_image_folder = dirname($this->content['target_file']['path']);
                            if (!file_exists($target_image_folder)) mkdir(dirname($target_image_folder), 0755, true);

                            if (empty($this->content['target_file']['width']) AND empty($this->content['target_file']['height']))
                            {
                                $this->content['target_file']['width'] = $this->content['default_file']['width'];
                                $this->content['target_file']['height'] = $this->content['default_file']['height'];
                            }
                            else
                            {
                                // If only width is set, calculate height from default
                                if (empty($this->content['target_file']['height']))
                                {
                                    $this->content['target_file']['height'] = round($this->content['default_file']['height'] / $this->content['default_file']['width'] * $this->content['target_file']['width']);
                                }
                                // If only height is set, calculate width from default
                                if (empty($this->content['target_file']['width']))
                                {
                                    $this->content['target_file']['width'] =  round($this->content['default_file']['width'] / $this->content['default_file']['height'] * $this->content['target_file']['height']);
                                }
                            }

                            if (empty($this->content['target_file']['quality']))
                            {
                                // default display image generate with fast speed
                                $this->content['target_file']['quality'] = $this->preference->image['quality']['spd'];
                            }

                            if (empty($this->content['target_file']['mime']))
                            {
                                // Only create thumb as png or jpeg, (gif will lose animation frames during imagecopyresample, so no point to save as gif any more)
                                if ($this->content['source_file']['mime'] == 'image/png') $this->content['target_file']['mime'] = $this->content['source_file']['mime'];
                                else $this->content['target_file']['mime'] = 'image/jpeg';
                            }

                            if (!isset($this->content['target_file']['quality'][$this->content['target_file']['mime']]))
                            {
                                // TODO: Error Handling, quality for thumbnail image type not set
                                return false;
                            }

                            $target_image = imagecreatetruecolor($this->content['target_file']['width'],  $this->content['target_file']['height']);
                            imagecopyresampled($target_image,$source_image,0,0,0,0,$this->content['target_file']['width'], $this->content['target_file']['height'],$this->content['source_file']['width'],$this->content['source_file']['height']);
                            imageinterlace($target_image,true);

                            switch ($this->content['target_file']['mime'])
                            {
                                case 'image/png':
                                    imagesavealpha($target_image, true);
                                    imagepng($target_image, $this->content['target_file']['path'], $this->content['target_file']['quality'][$this->content['target_file']['mime']][0], $this->content['target_file']['quality'][$this->content['target_file']['mime']][1]);
                                    break;
                                case 'image/jpg':
                                case 'image/jpeg':
                                default:
                                    imagejpeg($target_image, $this->content['target_file']['path'], $this->content['target_file']['quality'][$this->content['target_file']['mime']]);
                            }
                        }

                        if (isset($this->content['source_file']['update']))
                        {
                            $last_modified_time = gmdate('D, d M Y H:i:s',$this->content['source_file']['update']);
                        }
                        else
                        {
                            $last_modified_time = gmdate('D, d M Y H:i:s');
                        }

                        header('Last-Modified: '.$last_modified_time.' GMT');
                        header('Content-Length: '.filesize($this->content['target_file']['path']));
                        header('Content-Type: '.$this->content['target_file']['mime']);

                        readfile($this->content['target_file']['path']);
                }
                break;

        }
    }
}