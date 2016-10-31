<?php
// Include Class Object
// Name: content
// Description: web page content functions

// Render template, create html page view...

class content extends base {
    public $status;

    protected $request = array();
    protected $content = array();
    public $result = array();

    function __construct($parameter = array())
    {
        parent::__construct();
        $this->status = 'OK';

        $this->request = array();

        // Analyse uri structure and validate input variables, store separate input parts into $request
        if ($this->request_decoder($parameter) === false)
        {
            // TODO: Error Log, error during reading input uri and parameters
            $this->message->error = 'Fail: Error during request_decoder';
            $this->status = 'Fail';
        }
//print_r('request_decoder: <br>');
//print_r($this);

        // Generate the necessary components for the content, store separate component parts into $content
        // Read data from database (if applicable), only generate raw data from db
        // If any further complicate process required, leave it to render
        if ($this->status == 'OK' AND $this->build_content() === false)
        {
            // TODO: Error Log, error during building data object
            $this->message->error = 'Fail: Error during build_content';
            $this->status = 'Fail';
        }
//print_r('build_content: <br>');
//print_r($this);

        // Processing file, database and etc (basically whatever time consuming, process it here)
        // As some rendering methods may only need the raw data without going through all the file copy, modify, generate processes
        if ($this->render() === false)
        {
            // TODO: Error Log, error during rendering
            $this->message->error = 'Fail: Error during render';
            $this->status = 'Fail';
            return false;
        }
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

        $type = ['css','image','js','json','xml'];
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
            case 'image':
                if (empty($request_path))
                {
                    // TODO: image folder forbid direct access
                    return false;
                }
                include_once(PATH_PREFERENCE.'image'.FILE_EXTENSION_INCLUDE);
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
            case 'xml':
                if (empty($request_path))
                {
                    $this->request['method'] = 'list_available_method';
                }
                else
                {
                    $this->request['method'] = array_shift($request_path);
                }
                if (!empty($request_path))
                {
                    $this->request['value'] = array_shift($request_path);
                }
                if (!empty($request_path))
                {
                    // TODO: More unrecognized value passed through URI
                    $this->message->error = 'Decoding: URI parts unrecognized ['.implode('/',$request_path).']';
                    return false;
                }
                break;
            case 'html':
            default:
                //$request_path_part = array_shift($request_path);
                $module = ['listing','business','business-amp','console'];
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
                    case 'console':
                        $method = ['dashboard','credential'];
                        if (in_array($request_path_part,$method))
                        {
                            $this->request['method'] = $request_path_part;
                        }
                        else
                        {
                            // TODO: Error Handling, trying to access console without module specified or unrecognized module
                            header("HTTP/1.0 404 Not Found");
                            header('Location: '.URI_SITE_BASE.$this->request['module'].DIRECTORY_SEPARATOR.end($method));
                        }
                        break;
                    default:
                        $this->request['document'] = $request_path_part;
                }

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
        if (!isset($this->request['option']['format'])) $this->content['format'] = $this->request['data_type'];
        else $this->content['format'] = $this->request['option']['format'];

        if($this->content['format'] == 'html_tag')
        {
            if (isset($this->request['option']['html_tag']))
            {
                $this->content = array_merge($this->content,$this->request['option']['html_tag']);
            }
            if (!isset($this->content['attr'])) $this->content['attr'] = array();
            switch($this->request['data_type']) {
                case 'css':
                    if (!isset($this->content['name'])) $this->content['name'] = 'link';
                    $this->content['attr']['href'] = $this->request['file_uri'];
                    if (!isset($this->content['attr']['type'])) $this->content['attr']['type'] = 'text/css';
                    break;
                case 'js':
                    if (!isset($this->content['name'])) $this->content['name'] = 'script';
                    $this->content['attr']['src'] = $this->request['file_uri'];
                    if (!isset($this->content['attr']['type'])) $this->content['attr']['type'] = 'text/javascript';
                default:
                    // TODO: Error Handling, tag name not given
                    if (!isset($this->content['name'])) $this->content['name'] = 'div';
            }
        }

        switch($this->request['data_type'])
        {
            case 'css':
            case 'image':
            case 'js':
                $this->content['target_file'] = [
                    'path'=>$this->request['file_path'],
                    'uri'=>$this->request['file_uri'],
                    'minify'=>false
                ];
                if (file_exists($this->content['target_file']['path']))
                {
                    $this->content['target_file']['last_modified'] = filemtime($this->content['target_file']['path']);
                    $this->content['target_file']['content_length'] = filesize($this->content['target_file']['path']);
                }
                else
                {
                    $this->content['target_file']['last_modified'] = 0;
                    $this->content['target_file']['content_length'] = 0;
                }
                foreach ($this->request['extension'] as $extension_index=>$extension)
                {
                    if ($extension == 'min')
                    {
                        $this->content['target_file']['minify'] = true;
                        continue;
                    }
                    if (isset($this->preference->image))
                    {
                        if (in_array($extension,array_keys($this->preference->image['size'])))
                        {
                            $this->request['extension']['image_size'] = $extension;
                            $this->content['target_file']['width'] = $this->preference->image['size'][$extension];
                            continue;
                        }
                    }
                }

                $source_file = $this->request['data_type'].DIRECTORY_SEPARATOR;
                if (!empty($this->request['sub_path'])) $source_file .= implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR;
                $source_file .= $this->request['document'].'.'.$this->request['file_type'];

                if (isset($this->request['option']['source']))
                {
                    $this->content['source_file'] = ['path'=>PATH_ASSET.$source_file];
                    if ((strpos($this->request['option']['source'],URI_SITE_BASE) == FALSE)  AND (preg_match('/^http/',$this->request['option']['source']) == 1))
                    {
                        // If source_file is not relative uri and not start with current site uri base, it is an external (cross domain) source file
                        $this->content['source_file']['original_file'] = $this->request['option']['source'];
                        $this->content['source_file']['source'] = 'remote_file';
                    }
                    else
                    {
                        $this->content['source_file']['original_file'] = str_replace(URI_SITE_BASE,PATH_SITE_BASE,$this->request['option']['source']);
                        $this->content['source_file']['source'] = 'local_file';
                    }

                    if ($this->content['source_file']['source'] == 'local_file')
                    {
                        $this->content['source_file']['last_modified'] = filemtime($this->content['source_file']['original_file']);
                        $this->content['source_file']['content_length'] = filesize($this->content['source_file']['original_file']);
                    }
                    else
                    {
                        // External source file
                        $file_header = @get_headers($this->content['source_file']['original_file'],true);
                        if (strpos( $file_header[0], '200 OK' ) === false) {
                            // TODO: Error Handling, fail to get external source file header
                            return false;
                        }
                        if (isset($file_header['Last-Modified'])) {
                            $this->content['source_file']['last_modified'] = strtotime($file_header['Last-Modified']);
                        } else {
                            if (isset($file_header['Expires'])) {
                                $this->content['source_file']['last_modified'] = strtotime($file_header['Expires']);
                            } else {
                                if (isset($file_header['Date'])) $this->content['source_file']['last_modified'] = strtotime($file_header['Date']);
                                else $this->content['source_file']['last_modified'] = ('+1 day');
                            }
                        }
                        if (isset($file_header['Content-Length']))
                        {
                            $this->content['source_file']['content_length'] = $file_header['Content-Length'];
                            if ($this->content['source_file']['content_length'] > 10485760)
                            {
                                // TODO: Error Handling, source file too big
                                $this->message->error = 'Source File too big ( > 10MB )';
                                return false;
                            }
                        }
                    }
                    if ($this->content['source_file']['last_modified'] > $this->content['target_file']['last_modified'])
                    {
                        if ($this->content['source_file']['path'] == $this->content['source_file']['original_file'])
                        {
                            unset($this->content['source_file']['original_file']);
                        }
                        else
                        {
                            if(file_exists($this->content['target_file']['path'])) unlink($this->content['target_file']['path']);

                            if (!file_exists(dirname($this->content['source_file']['path']))) mkdir(dirname($this->content['source_file']['path']), 0755, true);
                            copy($this->content['source_file']['original_file'],$this->content['source_file']['path']);
                            if(!isset($this->content['source_file']['content_length'])) $this->content['source_file']['content_length'] = filesize($this->content['source_file']['path']);
                        }
                    }
                }
                else
                {
                    $this->content['source_file'] = ['source'=>'local_file'];
                    if (file_exists(PATH_ASSET.$source_file))
                    {
                        $this->content['source_file']['path'] = PATH_ASSET.$source_file;
                        $this->content['source_file']['remove_cache'] = true;
                    }
                    else
                    {
                        if (file_exists(PATH_CONTENT.$source_file))
                        {
                            $this->content['source_file']['path'] = PATH_CONTENT.$source_file;
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
                                $this->message->error = 'Building: fail to get source file from database, file not in standard format';
                                return false;
                            }
                            $entity_class = 'entity_'.$this->request['data_type'];
                            if (!class_exists($entity_class))
                            {
                                // TODO: Error Handling, last ditch failed, source file does not exist in database either
                                $this->message->error = 'Building: cannot find source file';
                                return false;
                            }
                            $entity_obj = new $entity_class($document_id);
                            if (empty($entity_obj->row))
                            {
                                // TODO: Error Handling, fail to get source file from database, cannot find matched record
                                $this->message->error = 'Building: fail to get source file from database, invalid id';
                                return false;
                            }
                            $record = $entity_obj->row[0];
                            $this->content['source_file']['path'] = PATH_ASSET.$source_file;
                            if (!file_exists(dirname($this->content['source_file']['path']))) mkdir(dirname($this->content['source_file']['path']), 0755, true);
                            file_put_contents($this->content['source_file']['path'],$record['data']);
                            //$entity_image_obj->generate_cache_file();
                            $this->content['source_file']['object'] = &$entity_image_obj;                        }
                    }
                    $this->content['source_file']['last_modified'] = filemtime($this->content['source_file']['path']);
                    $this->content['source_file']['content_length'] = filesize($this->content['source_file']['path']);
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
                            $this->message->error = 'Building: image size is not defined in global preference';
                            return false;
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
                            if (strpos($this->request['source_file']['path'],URI_SITE_BASE) == FALSE)
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
                                $this->message->error = 'Building: fail to get source file from database, file not in standard format';
                                return false;
                            }
                            $entity_image_obj = new entity_image($document_id);
                            if (empty($entity_image_obj->row))
                            {
                                // TODO: Error Handling, fail to get source file from database, cannot find matched record
                                $this->message->error = 'Building: fail to get source file from database, invalid id';
                                return false;
                            }
                            //$entity_image_obj->generate_cache_file();
                            $this->content['source_file']['object'] = &$entity_image_obj;
                        }
                    }
                }
                break;
            case 'json':
            case 'xml':
                if (empty($_SERVER['HTTP_AUTH_KEY']))
                {
                    // TODO: Error Handling, api key authentication failed
                    $this->message->notice = 'Building: Api Key Not Provided';
                    $this->result = [
                        'status'=>'REQUEST_DENIED',
                        'message'=>'Api Key Not Provided'
                    ];
                    return true;
                }
                $entity_api_key_obj = new entity_api_key();
                $method_variable = ['api_key'=>$_SERVER['HTTP_AUTH_KEY'],'remote_ip'=>get_remote_ip()];
                $auth_id = $entity_api_key_obj->validate_api_key($method_variable);
                if ($auth_id === false)
                {
                    // TODO: Error Handling, api key authentication failed
                    $this->message->notice = 'Building: Api Key Authentication Failed';
                    $this->result = [
                        'status'=>$method_variable['status'],
                        'message'=>$method_variable['message']
                    ];
                    return true;
                }
                $entity_api_obj = new entity_api($auth_id);
                $entity_api_method_obj = new entity_api_method($this->request['method']);
                if (empty($entity_api_method_obj->id_group))
                {
                    // TODO: Error Handling, api method not recognized
                    $this->message->notice = 'Building: Unknown Request Api Method ['.$this->request['method'].']';
                    $this->result = [
                        'status'=>'INVALID_REQUEST',
                        'message'=>'Method Does Not Exist: '.$this->request['method']
                    ];
                    return true;
                }

                $method_variable = [];
                if (!empty($this->request['option'])) $method_variable = $this->request['option'];
                if (!empty($this->request['value'])) $method_variable['value'] = $this->request['value'];
                $method_variable['api_id'] = $auth_id;
                $method_variable['status'] = 'OK';
                $method_variable['message'] = '';

                if (end($entity_api_method_obj->id_group) > 99)
                {
                    // For non-public functions, check if the user get the access
                    $available_functions = $entity_api_method_obj->list_available_method(array_merge($method_variable));
                    $available_function_name = [];
                    foreach($available_functions as $available_function_index=>&$available_function)
                    {
                        $available_function_name[] = $available_function['request_uri'];
                        $available_function['request_uri'] = URI_SITE_BASE.$this->content['format'].'/'.$available_function['request_uri'];
                    }
                    if (!in_array($this->request['method'],$available_function_name))
                    {
                        // TODO: Error Handling, user permission error, user does not have permission to use this function
                        $this->message->notice = 'Building: User ['.end($entity_api_obj->row)['name'].'] does not have the permission to use the method ['.$this->request['method'].']';
                        $this->result = [
                            'status'=>'REQUEST_DENIED',
                            'message'=>'User ['.end($entity_api_obj->row)['name'].'] does not have the permission to use the method ['.$this->request['method'].']',
                            'available_methods'=>$available_functions
                        ];
                        return true;
                    }
                }

                // For public functions, direct execute
                //$method_calling = str_replace('-','_',$this->request['method']);
                $method_calling = $this->request['method'];
                if (!method_exists($entity_api_method_obj,$method_calling))
                {
                    // TODO: Error Handling, internal error, api method defined in database, but does not exist in class function
                    $this->message->notice = 'Building: Server Internal Error Api Method ['.$this->request['method'].'] not defined';
                    $this->result = [
                        'status'=>'UNKNOWN_ERROR',
                        'message'=>'Method not available: ['.$this->request['method'].']. Server side is probably upgrading or under maintenance, try again later.'
                    ];
                    return true;
                }

                $this->content['method'] = $this->request['method'];

                $api_call_result = $entity_api_method_obj->$method_calling($method_variable);

                if (isset($method_variable['status'])) $this->result['status'] = $method_variable['status'];
                if (isset($method_variable['message'])) $this->result['message'] = $method_variable['message'];
                if ($api_call_result !== FALSE)
                {
                    foreach ($api_call_result as $record_index=>&$record)
                    {
                        if (!empty($record['request_uri'])) $record['request_uri'] = URI_SITE_BASE.$this->content['format'].'/'.$record['request_uri'];
                        if (!empty($record['friendly_url']))
                        {
                            $record['listing_url'] = 'http://www.top4.com.au/business/'.$record['friendly_url'];
                            unset($record['friendly_url']);
                        }
                    }
                    $this->result['result'] = &$api_call_result;
                }

                break;
            case 'html':
            default:
                $this->content['resource'] = [];
                $this->content['resource'][] = ['value'=>'/js/jquery.min.js','option'=>['source'=>PATH_CONTENT_JS.'jquery-1.11.3.js','format'=>'html_tag']];
                $this->content['resource'][] = ['value'=>'/js/default.min.js','option'=>['format'=>'html_tag']];
                $this->content['resource'][] = ['value'=>'/js/default-top4.js','option'=>['source'=>'http://dev.top4.com.au/scripts/default.js','format'=>'html_tag']];
                $this->content['resource'][] = ['value'=>'/css/default.min.css','option'=>['format'=>'html_tag']];
echo '<pre>resource_render<br>';
                foreach($this->content['resource'] as $resource_index=>&$resource)
                {
                    $resource_obj =  new content($resource);
                    $resource['result'] = $resource_obj->result;
                    print_r($resource_obj);
                    print_r($resource_obj->message->display());
                    unset($resource_obj);
                }
                echo '<pre>resource_render<br>';
                print_r($this->content['resource']);
                print_r($this->message->display());
                exit();



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
        switch($this->content['format'])
        {
            case 'css':
            case 'js':
                $target_file_path = dirname($this->content['target_file']['path']);
                if (!file_exists($target_file_path)) mkdir($target_file_path, 0755, true);

                if (!file_exists($this->content['target_file']['path']) OR $this->content['source_file']['last_modified'] > $this->content['target_file']['last_modified'])
                {
                    if (!empty($this->content['target_file']['minify']))
                    {
                        // Yuicompressor 2.4.8 does not support output as Windows absolute path start with Driver
                        $start_time = microtime(true);
                        exec('java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar "'.$this->content['source_file']['path'].'" -o "'.preg_replace('/^\w:/','',$this->content['target_file']['path']).'"', $result);
                        $this->message->notice = 'Yuicompressor Execution Time: '. (microtime(true) - $start_time);
                    }

                    if (!file_exists($this->content['target_file']['path']))
                    {
                        // If fail to generate minimized file, copy the source file
                        copy($this->content['source_file']['path'], $this->content['target_file']['path']);
                    }
                    else
                    {
                        if (filesize($this->content['target_file']['path']) > $this->content['source_file']['content_length'])
                        {
                            // If file getting bigger, original file probably already minimized with better algorithm (e.g. google's js files, just use the original file)
                            copy($this->content['source_file']['path'], $this->content['target_file']['path']);
                        }
                    }
                    if (!empty($this->content['target_file']['minify']))
                    {
                        $start_time = microtime(true);
                        file_put_contents($this->content['target_file']['path'],minify_content(file_get_contents($this->content['target_file']['path']),$this->request['data_type']));
                        $this->message->notice = 'PHP Minifier Execution Time: '. (microtime(true) - $start_time);
                    }

                    // remove the copied source file
                    if (isset($this->content['source_file']['original_file']) OR !empty($this->content['source_file']['remove_cache']))
                    {
                        unlink($this->content['source_file']['path']);
                    }
                }

                if (!file_exists($this->content['target_file']['path']))
                {
                    // TODO: Error Handling, Fail to generate target file
                    $this->message->error = 'Rendering: Fail to generate target file';
                    return false;
                }

                if ($this->request['file_uri'] != $this->content['target_file']['uri'])
                {
                    // TODO: On Direct Rendering from HTTP REQUEST, if request_uri is different from target file_uri, do 301 redirect
                    header('HTTP/1.1 301 Moved Permanently');
                    header('Location: '.$this->content['target_file']['uri']);
                    return false;
                }

                $this->content['target_file']['last_modified'] = filemtime($this->content['target_file']['path']);
                $this->content['target_file']['content_length'] = filesize($this->content['target_file']['path']);

                if ($this->content['target_file']['content_length'] == 0)
                {
                    // TODO: Error Handling, Fail to generate target file
                    $this->message->error = 'Rendering: Fail to generate target file';
                    return false;
                }

                header('Last-Modified: '.gmdate('D, d M Y H:i:s',$this->content['target_file']['last_modified']).' GMT');
                header('Content-Length: '.$this->content['target_file']['content_length']);

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

                switch ($this->request['render'])
                {
                    case 'source_uri':
                        return $this->request['file_uri'];
                        break;
                }
                break;
            case 'file_uri':
                $this->result = $this->content['target_file']['uri'];
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
                                    return false;
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
                            $this->message->error = 'Rendering: fail to create image';
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
            case 'json':
                $result = json_encode($this->result);
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                header('Content-Length: '.strlen($result));
                header("Content-Type: application/json");
                print_r($result);
                break;
            case 'html_tag':
                $this->result = '<'.$this->content['name'];
                foreach($this->content['attr'] as $attr_name=>$attr_content)
                {
                    $this->result .= ' '.$attr_name.'="'.$attr_content.'"';
                }
                $this->result .= '>';
                $void_tag = ['area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr'];
                if (!in_array($this->content['name'],$void_tag))
                {
                    if (isset($this->content['inner_html']))
                    {
                        $this->result .= htmlspecialchars($this->content['inner_html']);
                    }
                    $this->result .= '</'.$this->content['name'].'>';
                }
                break;
            case 'xml':
                $result = render_xml($this->result)->asXML();
                //$result = $result->__toString();
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                header('Content-Length: '.strlen($result));
                header("Content-Type: text/xml");
                print_r($result);
                break;
            case 'html':
                print_r(render_html($this->request['field'],$this->request['template']));
                break;

        }
    }
}