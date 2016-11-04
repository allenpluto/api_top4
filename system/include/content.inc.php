<?php
// Include Class Object
// Name: content
// Description: web page content functions

// Render template, create html page view...

class content extends base {
    protected $request = array();
    protected $content = array();
    protected $result = array();

    function __construct($parameter = array())
    {
        parent::__construct();

        $this->request = array();
        $this->result = array(
            'status'=>200,
            'header'=>array(),
            'content'=>array()
        );

        // Analyse uri structure and validate input variables, store separate input parts into $request
        if ($this->request_decoder($parameter) === false)
        {
            // TODO: Error Log, error during reading input uri and parameters
            $this->message->error = 'Fail: Error during request_decoder';
            //$this->result['status'] = 'Fail';
        }
//print_r('request_decoder: <br>');
//print_r($this);
if ($this->request['data_type'] == 'json' OR $this->request['data_type'] == 'xml')
{
    file_put_contents(PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_access_log.txt','REQUEST: ['.date('D, d M Y H:i:s').']'.$_SERVER['REQUEST_URI']."\n",FILE_APPEND);
}

        // Generate the necessary components for the content, store separate component parts into $content
        // Read data from database (if applicable), only generate raw data from db
        // If any further complicate process required, leave it to render
        if ($this->result['status'] == 200 AND $this->build_content() === false)
        {
            // TODO: Error Log, error during building data object
            $this->message->error = 'Fail: Error during build_content';
            //$this->result['status'] = 'Fail';
        }
//print_r('build_content: <br>');
//print_r($this);

        // Processing file, database and etc (basically whatever time consuming, process it here)
        // As some rendering methods may only need the raw data without going through all the file copy, modify, generate processes
        if ($this->result['status'] == 200 AND $this->generate_rendering() === false)
        {
            // TODO: Error Log, error during rendering
            $this->message->error = 'Fail: Error during render';
            //$this->result['status'] = 'Fail';
            return false;
        }
if ($this->request['data_type'] == 'json' OR $this->request['data_type'] == 'xml')
{
    file_put_contents(PATH_ASSET.'log'.DIRECTORY_SEPARATOR.'api_access_log.txt','RESULT: '.$this->result['content']."\n\n",FILE_APPEND);
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
            case 'font':
            case 'image':
            case 'js':
                if (empty($request_path))
                {
                    // TODO: Folder forbid direct access
                    $this->result['status'] = 403;
                    return false;
                }

                $file_name = array_pop($request_path);
                $file_part = explode('.',$file_name);
                $this->request['document'] = array_shift($file_part);
                if (!empty($file_part)) $this->request['file_type'] = array_pop($file_part);
                $this->request['extension'] = [];
                if ($this->request['data_type'] == 'image')
                {
                    include_once(PATH_PREFERENCE.'image'.FILE_EXTENSION_INCLUDE);
                    $image_size = array_keys($this->preference->image['size']);
                    $image_quality = array_keys($this->preference->image['quality']);
                    foreach ($file_part as $file_extension_index=>$file_extension)
                    {
                        if (in_array($file_extension,$image_size))
                        {
                            $this->request['extension']['image_size'] = $file_extension;
                            unset($file_part[$file_extension_index]);
                        }
                        if (in_array($file_extension,$image_quality))
                        {
                            $this->request['extension']['quality'] = $file_extension;
                            unset($file_part[$file_extension_index]);
                        }
                    }
                    unset($image_size);
                    unset($image_quality);
                }
                foreach ($file_part as $file_extension_index=>$file_extension)
                {
                    if ($file_extension == 'min')
                    {
                        $this->request['extension']['minify'] = $file_extension;
                        unset($file_part[$file_extension_index]);
                    }
                }
                ksort($this->request['extension']);
                if (!empty($file_part))
                {
                    // Put the rest part that is not an extension back to document name, e.g. jquery-1.11.8.min.js
                    $this->request['document'] .= '.'.implode('.',$file_part);
                }
                unset($file_part);
                $decoded_file_name = $this->request['document'];
                if (!empty($this->request['extension'])) $decoded_file_name .= '.'.implode('.',$this->request['extension']);
                if (!empty($this->request['file_type'])) $decoded_file_name .= '.'.$this->request['file_type'];

                if ($file_name != $decoded_file_name)
                {
                    // TODO: Error Handling, decoded file name is not consistent to requested file name
                    $this->result['status'] = 404;
                    return false;
                }

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
                    $this->result = [
                        'status'=>'INVALID_REQUEST',
                        'message'=>'Illegal Request URI'
                    ];
                    return true;
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
                            $this->result['status'] = 301;
                            $this->result['header']['Location'] =  URI_SITE_BASE.$this->request['module'].'/'.end($method);
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
            $this->content['html_tag'] = array();
            if (isset($this->request['option']['html_tag']))
            {
                $this->content['html_tag'] = array_merge($this->content['html_tag'],$this->request['option']['html_tag']);
            }
            if (!isset($this->content['html_tag']['attr'])) $this->content['html_tag']['attr'] = array();
            switch($this->request['data_type']) {
                case 'css':
                    if (!isset($this->content['html_tag']['name'])) $this->content['html_tag']['name'] = 'link';
                    $this->content['html_tag']['attr']['href'] = $this->request['file_uri'];
                    if (!isset($this->content['html_tag']['attr']['type'])) $this->content['html_tag']['attr']['type'] = 'text/css';
                    if (!isset($this->content['html_tag']['attr']['rel'])) $this->content['html_tag']['attr']['rel'] = 'stylesheet';
                    if (!isset($this->content['html_tag']['attr']['media'])) $this->content['html_tag']['attr']['media'] = 'all';
                    break;
                case 'image':
                    if (!isset($this->content['html_tag']['name'])) $this->content['html_tag']['name'] = 'img';
                    $this->content['html_tag']['attr']['src'] = $this->request['file_uri'];
                    if (!isset($this->content['html_tag']['attr']['alt'])) $this->content['html_tag']['attr']['alt'] = trim(ucwords($this->format->caption($this->request['document'])));
                    break;
                case 'js':
                    if (!isset($this->content['html_tag']['name'])) $this->content['html_tag']['name'] = 'script';
                    $this->content['html_tag']['attr']['src'] = $this->request['file_uri'];
                    if (!isset($this->content['html_tag']['attr']['type'])) $this->content['html_tag']['attr']['type'] = 'text/javascript';
                default:
                    // TODO: Error Handling, tag name not given
                    if (!isset($this->content['html_tag']['name'])) $this->content['html_tag']['name'] = 'div';
            }
        }

        switch($this->request['data_type'])
        {
            case 'css':
            case 'image':
            case 'js':
                $this->content['target_file'] = [
                    'path'=>$this->request['file_path'],
                    'uri'=>$this->request['file_uri']
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

                $file_relative_path = $this->request['data_type'].DIRECTORY_SEPARATOR;
                if (!empty($this->request['sub_path'])) $file_relative_path .= implode(DIRECTORY_SEPARATOR,$this->request['sub_path']).DIRECTORY_SEPARATOR;
                $this->content['source_file'] = [
                    'path' => PATH_ASSET.$file_relative_path.$this->request['document'].'.src.'.$this->request['file_type'],
                    'source' => 'local_file'
                ];
                $file_relative_path .= $this->request['document'].'.'.$this->request['file_type'];

                if (!file_exists(dirname($this->content['source_file']['path']))) mkdir(dirname($this->content['source_file']['path']), 0755, true);

                if (isset($this->request['option']['source']))
                {
                    if ((strpos($this->request['option']['source'],URI_SITE_BASE) == FALSE)  AND (preg_match('/^http/',$this->request['option']['source']) == 1))
                    {
                        // If source_file is not relative uri and not start with current site uri base, it is an external (cross domain) source file
                        $this->content['source_file']['original_file'] = $this->request['option']['source'];
                        $this->content['source_file']['source'] = 'remote_file';
                    }
                    else
                    {
                        $this->content['source_file']['original_file'] = str_replace(URI_SITE_BASE,PATH_SITE_BASE,$this->request['option']['source']);
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
                            $this->message->error = 'Source File not accessible - '.$file_header[0];
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
                        if (isset($file_header['Content-Type']))
                        {
                            $this->content['source_file']['content_type'] = $file_header['Content-Type'];
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

                            copy($this->content['source_file']['original_file'],$this->content['source_file']['path']);
                            touch($this->content['source_file']['path'], $this->content['source_file']['last_modified']);
                            if(!isset($this->content['source_file']['content_length'])) $this->content['source_file']['content_length'] = filesize($this->content['source_file']['path']);
                            if(!isset($this->content['source_file']['content_type'])) $this->content['source_file']['content_type'] = mime_content_type($this->content['source_file']['path']);
                        }
                    }
                }
                else
                {
                    if (!file_exists($this->content['source_file']['path']))
                    {
                        if (file_exists(PATH_ASSET.$file_relative_path))
                        {
                            copy(PATH_ASSET.$file_relative_path,$this->content['source_file']['path']);
                            $this->content['source_file']['original_file'] = PATH_ASSET.$file_relative_path;
                            $this->content['source_file']['last_modified'] = filemtime($this->content['source_file']['original_file']);
                            touch($this->content['source_file']['path'],$this->content['source_file']['last_modified']);
                        }
                        elseif (file_exists(PATH_CONTENT.$file_relative_path))
                        {
                            copy(PATH_CONTENT.$file_relative_path,$this->content['source_file']['path']);
                            $this->content['source_file']['original_file'] = PATH_CONTENT.$file_relative_path;
                            $this->content['source_file']['last_modified'] = filemtime($this->content['source_file']['original_file']);
                            touch($this->content['source_file']['path'],$this->content['source_file']['last_modified']);
                        }
                        else
                        {
                            // If file source doesn't exist in content folder, try database
                            $document_name_part = explode('-',$this->request['document']);
                            $document_id = end($document_name_part);
                            if (empty($document_id) OR !is_numeric($document_id))
                            {
                                // TODO: Error Handling, fail to get source file from database, last part of file name is not a valid id
                                $this->message->error = 'Building: fail to get source file from database, file not in standard format';
                                $this->result['status'] = 404;
                                return false;
                            }
                            $entity_class = 'entity_'.$this->request['data_type'];
                            if (!class_exists($entity_class))
                            {
                                // TODO: Error Handling, last ditch failed, source file does not exist in database either
                                $this->message->error = 'Building: cannot find source file';
                                $this->result['status'] = 404;
                                return false;
                            }
                            $entity_obj = new $entity_class($document_id);
                            if (empty($entity_obj->row))
                            {
                                // TODO: Error Handling, fail to get source file from database, cannot find matched record
                                $this->message->error = 'Building: fail to get source file from database, invalid id';
                                $this->result['status'] = 404;
                                return false;
                            }
                            $record = array_shift($entity_obj->row);

                            if (empty($record['data']))
                            {
                                // TODO: Error Handling, image record found, but image data is not stored in database
                                $this->message->error = 'Building: fail to get source file from database, image data not stored';
                                $this->result['status'] = 404;
                                return false;
                            }

                            $this->content['source_file']['source'] = 'local_data';
                            $this->content['source_file']['original_file'] = $record['data'];
                            if (!file_exists(dirname($this->content['source_file']['path']))) mkdir(dirname($this->content['source_file']['path']), 0755, true);
                            file_put_contents($this->content['source_file']['path'],$record['data']);

                            if (!empty($record['update_time']))
                            {
                                $this->content['source_file']['last_modified'] = strtotime($record['update_time']);
                                touch($this->content['source_file']['path'],$this->content['source_file']['last_modified']);
                            }
                            else
                            {
                                $this->content['source_file']['last_modified'] = filemtime($this->content['source_file']['path']);
                            }
                            if (!empty($record['update_time'])) $this->content['source_file']['content_type'] = $record['mime'];
                        }
                    }
                    if (!isset($this->content['source_file']['last_modified'])) $this->content['source_file']['last_modified'] = filemtime($this->content['source_file']['path']);
                    if (!isset($this->content['source_file']['content_length'])) $this->content['source_file']['content_length'] = filesize($this->content['source_file']['path']);
                    if (!isset($this->content['source_file']['content_type'])) $this->content['source_file']['content_type'] = mime_content_type($this->content['source_file']['path']);

                }

                if ($this->request['data_type'] == 'image')
                {
                    $source_image_size = getimagesize($this->content['source_file']['path']);
                    $this->content['source_file']['width'] = $source_image_size[0];
                    $this->content['source_file']['height'] = $source_image_size[1];

                    if (!isset($this->content['default_file'])) $this->content['default_file'] = [];
                    $this->content['default_file']['path'] = PATH_ASSET.$file_relative_path;
                    if ($this->content['source_file']['width'] > max($this->preference->image['size']))
                    {
                        $this->content['default_file']['width'] = max($this->preference->image['size']);
                        $this->content['default_file']['height'] = $this->content['source_file']['height'] / $this->content['source_file']['width'] * $this->content['default_file']['width'];
                    }
                    else
                    {
                        $this->content['default_file']['width'] = $this->content['source_file']['width'];
                        $this->content['default_file']['height'] = $this->content['source_file']['height'];
                    }
                    // Set default image quality as 'max'
                    $this->content['default_file']['quality'] = $this->preference->image['quality']['max'];

                }

                foreach ($this->request['extension'] as $extension_index=>$extension)
                {
                    // General Extensions
                    switch ($extension_index)
                    {
                        case 'minify':
                            $this->content['target_file']['minify'] = true;
                            break;
                    }
                    if ($this->request['data_type'] == 'image')
                    {
                        // Image Extensions
                        switch ($extension_index)
                        {
                            case 'image_size':
                                $this->content['target_file']['width'] = $this->preference->image['size'][$extension];
                                $this->content['target_file']['height'] = $this->content['source_file']['height'] / $this->content['source_file']['width'] * $this->content['target_file']['width'];
                                break;
                            case 'quality':
                                $this->content['target_file']['quality'] = $this->preference->image['quality'][$extension];
                                break;
                        }
                    }
                }

                // If image quality is not specified, use the fast generate setting
                if (!isset($this->content['target_file']['quality'])) $this->content['target_file']['quality'] = $this->preference->image['quality']['spd'];
                break;
            case 'json':
            case 'xml':
                if (empty($_SERVER['HTTP_AUTH_KEY']))
                {
                    // TODO: Error Handling, api key authentication failed
                    $this->message->notice = 'Building: Api Key Not Provided';
                    $this->content['api_result'] = [
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
                    $this->content['api_result'] = [
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
                    $this->content['api_result'] = [
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
                        $this->content['api_result'] = [
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
                    $this->content['api_result'] = [
                        'status'=>'UNKNOWN_ERROR',
                        'message'=>'Method not available: ['.$this->request['method'].']. Server side is probably upgrading or under maintenance, try again later.'
                    ];
                    return true;
                }

                $this->content['method'] = $this->request['method'];

                $api_call_result = $entity_api_method_obj->$method_calling($method_variable);

                if (isset($method_variable['status'])) $this->content['api_result']['status'] = $method_variable['status'];
                if (isset($method_variable['message'])) $this->content['api_result']['message'] = $method_variable['message'];
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
                    $this->content['api_result']['result'] = &$api_call_result;
                }

                break;
            case 'html':
            default:
                switch($this->request['module'])
                {
                    case 'console':
                        if (!isset($_COOKIE['session_id']))
                        {
                            // TODO: Error Handling, session validation failed, session_id not set
                            $this->message->notice = 'Session Validation Failed, Redirect to Login Page';
                            $this->result['status'] = 301;
                            $this->result['header']['Location'] =  URI_SITE_BASE.'login';
                            return false;
                        }

                        $entity_api_session_obj = new entity_api_session();
                        $api_account_id = $entity_api_session_obj->validate_api_session_id($_COOKIE['session_id']);
                        if ($api_account_id == false)
                        {
                            // TODO: Error Handling, session validation failed, session_id invalid
                            $this->message->notice = 'Session Validation Failed, Redirect to Login Page';
                            $this->result['status'] = 301;
                            $this->result['header']['Location'] =  URI_SITE_BASE.'login';
                            return false;
                        }
                        $entity_api_obj = new entity_api($api_account_id);
                        if (empty($entity_api_obj->row))
                        {
                            // TODO: Error Handling, session validation failed, session_id is valid, but cannot read corresponding account
                            $this->message->error = 'Session Validation Succeed, but cannot find related api account';
                            $this->result['status'] = 301;
                            $this->result['header']['Location'] =  URI_SITE_BASE.'login';
                            return false;
                        }
                        $this->content['account'] = end($entity_api_obj->row);

                        break;
                    case 'login':
                        if (isset($_COOKIE['session_id']))
                        {
                            // TODO: session_id is set, check if it is already logged in
                            $entity_api_session_obj = new entity_api_session();
                            $api_account_id = $entity_api_session_obj->validate_api_session_id($_COOKIE['session_id']);
                            if ($api_account_id == false)
                            {
                                // If session_id is not valid, unset it and continue login process
                                setcookie('session_id', '', 1);
                            }
                            $entity_api_obj = new entity_api($api_account_id);
                            if (empty($entity_api_obj->row))
                            {
                                // TODO: Error Handling, session validation failed, session_id is valid, but cannot read corresponding account
                                $this->message->error = 'Session Validation Succeed, but cannot find related api account';
                                // If session_id is not valid, unset it and continue login process
                                setcookie('session_id', '', 1);
                            }

                            $this->result['cookie'] = ['session_id'=>$_COOKIE['session_id']];
                            $this->result['status'] = 301;
                            $this->result['header']['Location'] =  URI_SITE_BASE.'console';
                            return true;
                        }

                        if ($_SERVER['REQUEST_METHOD'] == 'POST')
                        {
                            if (isset($this->request['option']['username']))
                            {
                                $login_param = [];
                                $login_param_keys = ['username','password','remember_me'];
                                foreach($this->request['option'] as  $option_key=>&$option_value)
                                {
                                    if (in_array($option_key,$login_param_keys))
                                    {
                                        $login_param[$option_key] = $option_value;
                                        //unset($option_value);
                                    }
                                }
                                $entity_api_obj = new entity_api();
                                $api_account_id = $entity_api_obj->authenticate($login_param);
                                if ($api_account_id == false)
                                {
                                    // TODO: Error Handling, login failed
                                    setcookie('session_id', '', 1);
                                }

                                $this->result['cookie'] = ['session_id'=>$_COOKIE['session_id']];
                                $this->result['status'] = 301;
                                $this->result['header']['Location'] =  URI_SITE_BASE.'console';
                                return true;
                            }
                        }
                }
                //$this->content['field'] = $page_fetched_value[0];
                $this->content['template'] = 'page_login';

                return true;
                /*$this->content['resource'] = [];
                $this->content['resource'][] = ['value'=>'/js/jquery.min.js','option'=>['source'=>PATH_CONTENT_JS.'jquery-1.11.3.js','format'=>'html_tag']];
                $this->content['resource'][] = ['value'=>'/js/default.min.js','option'=>['format'=>'html_tag']];
                $this->content['resource'][] = ['value'=>'/js/default-top4.js','option'=>['source'=>'http://dev.top4.com.au/scripts/default.js','format'=>'html_tag']];
                $this->content['resource'][] = ['value'=>'/css/default.min.css','option'=>['format'=>'html_tag']];
//echo '<pre>resource_render<br>';
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>';
                foreach($this->content['resource'] as $resource_index=>&$resource)
                {
                    $resource_obj =  new content($resource);
                    $resource['result'] = $resource_obj->get_result();
                    echo $resource['result'];
                    $resource['message'] = $resource_obj->message->display();
                    //print_r($resource_obj);
                    //print_r($resource_obj->message->display());
                    unset($resource_obj);
                }
echo '</head>

<body>
<div class="font_icon font_icon_map">Map Icon</div>
';
                $this->content['resource2'] = [];
                $this->content['resource2'][] = ['value'=>'/image/10/chiaro-bathroom-package-10013.m.jpg','option'=>['format'=>'html_tag','html_tag'=>['attr'=>['class'=>'font_icon_map']]]];
                $this->content['resource2'][] = ['value'=>'/image/wardrobe.m.jpg','option'=>['source'=>'http://mobile.top4.com.au/asset/image/xxl/354_photo_296388.jpg','format'=>'file_uri']];
                foreach($this->content['resource2'] as $resource_index=>&$resource)
                {
                    $resource_obj =  new content($resource);
                    $resource['result'] = $resource_obj->get_result();
                    echo $resource['result'];
                    $resource['message'] = $resource_obj->message->display();
                    //print_r($resource_obj);
                    //print_r($resource_obj->message->display());
                    unset($resource_obj);
                }
echo '</body>
</html>';
                echo '<pre>resource_render<br>';
                print_r($this->content['resource']);
                print_r($this->content['resource2']);
                print_r($this->message->display());
                exit();*/



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

    private function generate_rendering()
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
                        $execution_command = 'java -jar '.PATH_CONTENT_JAR.'yuicompressor-2.4.8.jar --type '.$this->content['format'].' "'.$this->content['source_file']['path'].'" -o "'.preg_replace('/^\w:/','',$this->content['target_file']['path']).'"';
                        exec($execution_command, $result);
                        $this->message->notice = 'Yuicompressor Execution Time: '. (microtime(true) - $start_time);
                        $this->message->notice = $execution_command;
                        //$this->message->notice = $result;
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
                    $this->result['status'] = 301;
                    $this->result['header']['Location'] = $this->content['target_file']['uri'];
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

                // Try up to 3 times to delete the source file
                $unlink_retry_counter = 3;
                while (!unlink($this->content['source_file']['path']) AND $unlink_retry_counter > 0)
                {
                    sleep(1);
                    $unlink_retry_counter--;
                }

                $this->result['header']['Last-Modified'] = gmdate('D, d M Y H:i:s',$this->content['target_file']['last_modified']).' GMT';
                $this->result['header']['Content-Length'] = $this->content['target_file']['content_length'];

                switch ($this->request['data_type'])
                {
                    case 'css':
                        $this->result['header']['Content-Type'] = 'text/css';
                        break;
                    case 'js':
                        $this->result['header']['Content-Type'] = 'application/javascript';
                        break;
                    default:
                }

                $this->result['file_path'] = $this->content['target_file']['path'];
                break;
            case 'file_uri':
                $this->result['content'] = $this->content['target_file']['uri'];
                break;
            case 'image':
                // create source file resource object
                switch ($this->content['source_file']['content_type']) {
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

                // If the source file is not copied from default file, generate default file
                if (!isset($this->content['source_file']['original_file']) OR $this->content['source_file']['original_file'] != $this->content['default_file']['path'])
                {
                    if ($this->content['source_file']['width'] != $this->content['default_file']['width'])
                    {
                        // Resize the image if it is not the same size
                        $default_image = imagecreatetruecolor($this->content['default_file']['width'],  $this->content['default_file']['height']);
                        imagecopyresampled($default_image,$source_image,0,0,0,0,$this->content['default_file']['width'], $this->content['default_file']['height'],$this->content['source_file']['width'],$this->content['source_file']['height']);
                    }
                    else
                    {
                        $default_image = $source_image;
                    }
                    imageinterlace($default_image,true);

                    // Save Default Image with Max Quality Set
                    $image_quality = $this->content['default_file']['quality'];
                    switch($this->content['source_file']['content_type'])
                    {
                        case 'image/png':
                            imagesavealpha($default_image, true);
                            imagepng($default_image, $this->content['default_file']['path'], $image_quality['image/png'][0], $image_quality['image/png'][1]);
                            break;
                        case 'image/gif':
                            // If source is gif, directly copy it, any resize will lose frame data (lose animation effect)
                            copy($this->content['source_file']['path'],$this->content['default_file']['path']);
                            $this->content['default_file']['width'] = $this->content['source_file']['width'];
                            $this->content['default_file']['height'] = $this->content['source_file']['height'];
                            break;
                        case 'image/jpg':
                        case 'image/jpeg':
                        default:
                            imagejpeg($default_image, $this->content['default_file']['path'], $image_quality['image/jpeg']);
                    }
                    $this->content['default_file']['content_length'] = filesize($this->content['default_file']['path']);
                    if ($this->content['default_file']['content_length'] > $this->content['source_file']['content_length'])
                    {
                        // If somehow resized image getting bigger in size, just overwrite it with original file
                        copy($this->content['source_file']['path'],$this->content['default_file']['path']);
                        $this->content['default_file']['content_length'] = $this->content['source_file']['content_length'];
                    }
                    touch($this->content['default_file']['path'],$this->content['source_file']['last_modified']);
                    $this->content['default_file']['last_modified'] = $this->content['source_file']['last_modified'];
                    // Default image create process finish here, unset default file gd object
                    unset($default_image);
                }

                // If the required file is not the default file, generate the required file
                if ($this->content['default_file']['path'] != $this->content['target_file']['path'])
                {
                    if ($this->content['source_file']['width'] != $this->content['target_file']['width'])
                    {
                        // Resize the image if it is not the same size
                        $target_image = imagecreatetruecolor($this->content['target_file']['width'],  $this->content['target_file']['height']);
                        imagecopyresampled($target_image,$source_image,0,0,0,0,$this->content['target_file']['width'], $this->content['target_file']['height'],$this->content['source_file']['width'],$this->content['source_file']['height']);
                    }
                    else
                    {
                        $target_image = $source_image;
                    }
                    imageinterlace($target_image,true);

                    // Save Default Image with Max Quality Set
                    $image_quality = $this->content['target_file']['quality'];
                    switch($this->content['source_file']['content_type'])
                    {
                        case 'image/png':
                            imagesavealpha($target_image, true);
                            imagepng($target_image, $this->content['target_file']['path'], $image_quality['image/png'][0], $image_quality['image/png'][1]);
                            $this->content['target_file']['content_type'] = 'image/png';
                            break;
                        case 'image/gif':
                        case 'image/jpg':
                        case 'image/jpeg':
                        default:
                            imagejpeg($target_image, $this->content['target_file']['path'], $image_quality['image/jpeg']);
                            $this->content['target_file']['content_type'] = 'image/jpeg';
                    }
                    $this->content['target_file']['content_length'] = filesize($this->content['target_file']['path']);
                    touch($this->content['target_file']['path'],$this->content['source_file']['last_modified']);
                    $this->content['target_file']['last_modified'] = $this->content['source_file']['last_modified'];

                    // Default image create process finish here, unset default file gd object
                    unset($target_image);
                }
                if (empty($this->content['target_file']['last_modified'])) $this->content['target_file']['last_modified'] = filemtime($this->content['target_file']['path']);

                // Try up to 3 times to delete the source file
                $unlink_retry_counter = 3;
                while (!unlink($this->content['source_file']['path']) AND $unlink_retry_counter > 0)
                {
                    sleep(1);
                    $unlink_retry_counter--;
                }

//echo '<pre>';print_r($this);
//print_r(['Last-Modified'=>gmdate('D, d M Y H:i:s',$this->content['target_file']['last_modified']).' GMT','Content-Length'=>$this->content['target_file']['content_length'],'Content-Type'=>$this->content['target_file']['content_type']]);
//exit();
                $this->result['header']['Last-Modified'] = gmdate('D, d M Y H:i:s',$this->content['target_file']['last_modified']).' GMT';
                $this->result['header']['Content-Length'] = $this->content['target_file']['content_length'];
                $this->result['header']['Content-Type'] = $this->content['target_file']['content_type'];

                $this->result['file_path'] = $this->content['target_file']['path'];
                break;
            case 'json':
                $this->result['content'] = json_encode($this->content['api_result']);
                $this->result['header']['Last-Modified'] = gmdate('D, d M Y H:i:s').' GMT';
                $this->result['header']['Content-Length'] = strlen($this->result['content']);
                $this->result['header']['Content-Type'] = 'application/json';
                break;
            case 'html_tag':
                $this->result['content'] = '<'.$this->content['html_tag']['name'];
                foreach($this->content['html_tag']['attr'] as $attr_name=>$attr_content)
                {
                    $this->result['content'] .= ' '.$attr_name.'="'.$attr_content.'"';
                }
                $this->result['content'] .= '>';
                $void_tag = ['area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr'];
                if (!in_array($this->content['html_tag']['name'],$void_tag))
                {
                    if (isset($this->content['html_tag']['html']))
                    {
                        $this->result['content'] .= htmlspecialchars($this->content['html_tag']['html']);
                    }
                    $this->result['content'] .= '</'.$this->content['html_tag']['name'].'>';
                }
                break;
            case 'xml':
                $this->result['content'] = render_xml($this->content['api_result'])->asXML();
                $this->result['header']['Last-Modified'] = gmdate('D, d M Y H:i:s').' GMT';
                $this->result['header']['Content-Length'] = strlen($this->result['content']);
                $this->result['header']['Content-Type'] = 'text/xml';
                break;
            case 'html':
                $this->result['content'] = render_html($this->request['field'],$this->request['template']);
                $this->result['header']['Last-Modified'] = gmdate('D, d M Y H:i:s').' GMT';
                $this->result['header']['Content-Length'] = strlen($this->result['content']);
                $this->result['header']['Content-Type'] = 'text/html';
                break;
        }
    }

    function render()
    {
        http_response_code($this->result['status']);
        foreach($this->result['header'] as $header_name=>$header_content)
        {
            header($header_name.': '.$header_content);
        }
        if (isset($this->result['file_path']))
        {
            readfile($this->result['file_path']);
            exit();
        }
        if (!empty($this->result['content']))
        {
            print_r($this->result['content']);
        }
    }

    function get_result()
    {
        if (isset($this->result['file_path']))
        {
            return file_get_contents($this->result['file_path']);
        }
        return $this->result['content'];
    }
}