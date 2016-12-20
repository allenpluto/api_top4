<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 26/09/2016
 * Time: 2:24 PM
 */
if (!isset($start_time)) $start_time = microtime(1);

function render_xml($field = array(), &$xml = NULL, $parent_node_name = '')
{
    if (!isset($xml)) $xml = new SimpleXMLElement('<?xml version="1.0"?><response></response>');
    foreach ($field as $field_name=>$field_value)
    {
        if (!empty($parent_node_name)) $field_name = $parent_node_name;
        if (is_array($field_value))
        {
            // For sequential array
            if (array_keys($field_value) === range(0, count($field_value) - 1))
            {
                $parent_node = $xml->addChild($field_name.'s');
                render_xml($field_value,$parent_node,$field_name);
            }
            else
            {
                $parent_node = $xml->addChild($field_name);
                render_xml($field_value,$parent_node);
            }
        }
        else
        {
            $xml->addChild($field_name,htmlspecialchars($field_value));
        }
    }
    return $xml;
}

function render_html($field = array(), $template_name = '')
{
    if (empty($template_name)) return '';
$GLOBALS['time_stack']['analyse template '.$template_name] = microtime(1) - $GLOBALS['start_time'];

    $field_parameter = array();
    if (isset($field['_parameter']))
    {
        $field_parameter = $field['_parameter'];
        unset($field['_parameter']);
    }
    if (isset($field_parameter['condition']) AND empty($field_parameter['condition'])) return '';
    if (isset($field['_value']))
    {
        $field = $field['_value'];
    }
    if (is_array($field) AND isset($field[0]))
    {
        $rendered_content_array = array();
        foreach($field as $field_index=>$field_value)
        {
            $rendered_content_array[] = render_html($field_value, $template_name);
        }
        $rendered_content =  implode('',$rendered_content_array);
        if (isset($field_parameter['container']))
        {
            return render_html(array('_placeholder'=>$rendered_content),$field_parameter['container']);
        }
        else
        {
            return $rendered_content;
        }
    }

    if (file_exists(PATH_TEMPLATE.$template_name.FILE_EXTENSION_TEMPLATE)) $template = file_get_contents(PATH_TEMPLATE.$template_name.FILE_EXTENSION_TEMPLATE);
    else return '';
    if (empty($field)) return $template;
    if (!is_array($field)) $field = array('_placeholder'=>$field);

    preg_match_all('/\[\[(\W*)(.+?)\]\]/', $template, $matches);

    $match_result = array();

    foreach($matches[2] as $match_key=>$match_value)
    {
        $current_item = $matches[0][$match_key];
        if (!isset($match_result[$current_item]))
        {
            $match_result[$current_item] = array('type'=>$matches[1][$match_key]);
            $match_item = explode(':',$match_value);
            foreach($match_item as $match_item_index=>$match_item_value)
            {
                if ($match_item_index == 0) $match_result[$current_item]['name'] = $match_item_value;
                else
                {
                    if (preg_match('/(\w+)=`(.*)`/', $match_item_value, $match_item_value_matches))
                    {
                        $match_result[$current_item][$match_item_value_matches[1]] = $match_item_value_matches[2];
                    }
                }
            }
        }
    }

    $rendered_content = $template;
    $translate_array = array();
    foreach($match_result as $match_result_key=>&$match_result_value)
    {
        $match_result_value = array_merge($match_result_value,$field_parameter);
        if (isset($match_result_value['condition']))
        {
            if (empty($match_result_value['condition']))
            {
                return '';
            }
            else
            {
                if (isset($field[$match_result_value['condition']]) AND empty($field[$match_result_value['condition']]))
                {
                    return '';
                }
            }
        }
        switch($match_result_value['type'])
        {
            case '*':
                // Field value, directly set value from given field
                if (isset($field[$match_result_value['name']]))
                {
                    if (empty($field[$match_result_value['name']]))
                    {
                        $match_result_value['value'] = '';
                    }
                    else
                    {
                        if (is_array($field[$match_result_value['name']]))
                        {
                            if (!isset($match_result_value['template'])) $match_result_value['template'] = $template_name.'_'.$match_result_value['name'];
                            if (file_exists(PATH_TEMPLATE.$match_result_value['template'].FILE_EXTENSION_TEMPLATE))
                            {
                                $match_result_value['value'] = '';
                                foreach($field[$match_result_value['name']] as $sub_field_index=>$sub_field)
                                {
                                    if (!is_array($sub_field)) $sub_field = array('_placeholder'=>$sub_field);
                                    $sub_field = array_merge($field,$sub_field);
                                    $match_result_value['value'] .= render_html($sub_field,$match_result_value['template']);
                                }
                            }
                            else
                            {
                                $match_result_value['value'] = implode(',',$field[$match_result_value['name']]);
                            }
                        }
                        else
                        {
                            //$match_result_value['value'] = $field[$match_result_value['name']];
                            if (empty($match_result_value['template']))
                            {
                                $match_result_value['value'] = $field[$match_result_value['name']];
                            }
                            else
                            {
                                    //$field_with_default_value = array_merge($field, ['_placeholder'=>$field[$match_result_value['name']],'_parameter'=>array()]);
                                $match_result_value['value'] = render_html(['_placeholder'=>$field[$match_result_value['name']]],$match_result_value['template']);
                                    //unset($field_with_default_value);
                            }
                        }
                    }
                }
                else $match_result_value['value'] = '';
                break;
            case '$':
                // Chunk, load sub-template
                if (!isset($match_result_value['condition'])) $match_result_value['condition'] = true;
                else $match_result_value['condition'] = $field[$match_result_value['condition']];
                if (!isset($match_result_value['alternative_chunk'])) $match_result_value['alternative_chunk'] = '';
                if (isset($match_result_value['field'])) $field = array_merge($field, json_decode($match_result_value['field'],true));
                if ($match_result_value['condition']) $match_result_value['value'] = render_html($field,$match_result_value['name']);
                else $match_result_value['value'] = render_html($field,$match_result_value['alternative_chunk']);
                break;
            case '':
                // Object, fetch value and render for each row
                if (empty($field[$match_result_value['name']]))
                {
                    $match_result_value['value'] = '';
                    break;
                }

                if (!isset($match_result_value['object']))
                {
                    if (class_exists('view_'.$match_result_value['name']))
                    {
                        $match_result_value['object'] = 'view_'.$match_result_value['name'];
                    }
                    else
                    {
                        $match_result_value['object'] = 'entity_'.$match_result_value['name'];
                    }
                }
                if (!isset($match_result_value['template']))
                {
                    $match_result_value['template'] = $template.'_'.$match_result_value['name'];
                    if (!file_exists(PATH_TEMPLATE.$match_result_value['template'].FILE_EXTENSION_TEMPLATE)) $match_result_value['template'] = $match_result_value['object'];
                }
                if (isset($match_result_value['field'])) $field = array_merge($field, json_decode($match_result_value['field'],true));

                if (!file_exists(PATH_TEMPLATE.$match_result_value['template'].FILE_EXTENSION_TEMPLATE))
                {
                    $match_result_value['value'] = '';
                    break;
                }
$GLOBALS['time_stack']['analyse parameter for object '.$match_result_value['object']] = microtime(1) - $GLOBALS['start_time'];
                try
                {
                    $object = new $match_result_value['object']($field[$match_result_value['name']]);
$GLOBALS['time_stack']['create object 1 '.$match_result_value['object']] = microtime(1) - $GLOBALS['start_time'];
                }
                catch (Exception $e)
                {
                    // Error Handling, error rendering sub object during render_html
                    $GLOBALS['global_message']->error = 'error rendering sub object during render_html'.$e->getMessage();
                    $match_result_value['value'] = $e->getMessage();
                    break;
                }
$GLOBALS['time_stack']['create object '.$match_result_value['object']] = microtime(1) - $GLOBALS['start_time'];

                $result = $object->fetch_value();
                $GLOBALS['time_stack']['fetch value '.$match_result_value['object']] = microtime(1) - $GLOBALS['start_time'];
//print_r($object);
                unset($object);
                $rendered_result = array();
                foreach ($result as $index=>$row)
                {
                    $row = array_merge($field,$row);
                    $rendered_result[] = render_html($row,$match_result_value['template']);
$GLOBALS['time_stack']['render row['.$index.'] '.$match_result_value['object']] = microtime(1) - $GLOBALS['start_time'];
                }
                $match_result_value['value'] = implode('',$rendered_result);
                unset($result);
                unset($rendered_result);

                break;
            case '-':
                $match_result_value['value'] = '';
                break;
            case '+':
                // do not replace, keep for further operation, such as insert style or script
                $match_result_value['value'] = '';
                if (isset($field[$match_result_value['name']]))
                {
                    foreach($field[$match_result_value['name']] as $resource_index=>&$resource)
                    {
                        $resource_obj =  new content($resource);
                        //print_r($resource_obj);
                        $match_result_value['value'] .= $resource_obj->get_result();
                    }
                }
                else $match_result_value['value'] = '';
                break;
            default:
                $match_result_value['value'] = '';
        }
        if (isset($match_result_value['value']))
        {
            $translate_array[$match_result_key] = $match_result_value['value'];
            if (isset($match_result_value['container']) AND !empty($match_result_value['value'])) $translate_array[$match_result_key] = render_html(['_placeholder'=>$translate_array[$match_result_key]],$match_result_value['container']);
        }
$GLOBALS['time_stack']['render variable '.$match_result_key] = microtime(1) - $GLOBALS['start_time'];
    }
    $rendered_content = strtr($rendered_content,$translate_array);

    //print_r($match_result);

    return $rendered_content;
}

function minify_content($value, $type='html')
{
    if (empty($value))
    {
        return '';
    }

    switch($type)
    {
        case 'css':
            // Minify CSS
            $search = array(
                '/\/\*(.*?)\*\//s',                  // remove css comments
                '/([,:;\{\}])[^\S]+/',             // strip whitespaces after , : ; { }
                '/[^\S]+([,:;\{\}])/',             // strip whitespaces before , : ; { }
                '/(\s)+/'                            // shorten multiple whitespace sequences
            );
            $replace = array(
                '',
                '\\1',
                '\\1',
                '\\1'
            );
            // ('([^']|\\')*?[^\\]')
            return preg_replace($search, $replace, $value);
        case 'html':
            // Minify HTML
            $search = array(
                '/<\!--(?!\[if)(.*?)-->/s',       // remove html comments, except IE comments
                '/\>[^\S ]+/',                      // strip whitespaces after tags, except space
                '/[^\S ]+\</',                      // strip whitespaces before tags, except space
                '/(\s)+/'                            // shorten multiple whitespace sequences
            );
            $replace = array(
                '',
                '>',
                '<',
                '\\1'
            );
            return preg_replace($search, $replace, $value);
        case 'js':
            // Minify JS
            $search = array(
                '/\/\*(.*?)\*\//s',                       // remove js comments with /* */
                '/\/\/(.*?)[\n\r]/s',                     // remove js comments with //
                '/([\<\>\=\+\-,:;\(\)\{\}])[^\S]+(?=([^\']*\'[^\']*\')*[^\']*$)/',        // strip whitespaces after , : ; { }
                '/[^\S]+([\<\>\=\+\-,:;\(\)\{\}])(?=([^\']*\'[^\']*\')*[^\']*$)/',        // strip whitespaces before , : ; { }
                '/^(\s)+/'                                 // strip whitespaces in the start of the file
            );
            $replace = array(
                '',
                '',
                '\\1',
                '\\1',
                ''
            );
            return preg_replace($search, $replace, $value);
        default:
            // Error Handling, minify unknown type
            $GLOBALS['global_message']->error = 'minify_content - unrecognized minify type '.$type;
            return false;
    }
}

function get_remote_ip()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))
    {
        // For Cloud Flare forwarded request, get the original remote ip address
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}