<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 26/09/2016
 * Time: 2:24 PM
 */

function template_render($field = array(), $template = '')
{
    if (empty($template)) return '';
    if (file_exists(PATH_TEMPLATE.$template.FILE_EXTENSION_TEMPLATE)) $template = file_get_contents(PATH_TEMPLATE.$template.FILE_EXTENSION_TEMPLATE);
    if (empty($field)) return $template;

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
    foreach($match_result as $match_result_key=>&$match_result_value)
    {
        switch($match_result_value['type'])
        {
            case '*':
                // Field value, directly set value from given field
                if (isset($field[$match_result_value['name']])) $match_result_value['value'] = $field[$match_result_value['name']];
                else $match_result_value['value'] = '';
                break;
            case '$':
                // Chunk, load sub-template
                if (!isset($match_result_value['condition'])) $match_result_value['condition'] = true;
                if (!isset($match_result_value['alternative_chunk'])) $match_result_value['alternative_chunk'] = '';
                if ($match_result_value['condition']) $match_result_value['value'] = template_render($field,$match_result_value['name']);
                else $match_result_value['value'] = template_render($field,$match_result_value['alternative_chunk']);
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
                    $match_result_value['object'] = $match_result_value['name'];
                }
                if (!isset($match_result_value['template']))
                {
                    $match_result_value['template'] = $template.'_'.$match_result_value['name'];
                }
                $match_result_value['value'] = $field[$match_result_value['name']];

                break;
            case '-':
                break;
            case '+':
                break;
            default:

        }
    }

    print_r($match_result);

    return true;


    foreach($matches[2] as $key=>$value)
    {
        switch ($matches[1][$key][0])
        {
            case '*':
                // simple field value
                if (!isset($field[$value[0]]))
                {
                    $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);
                    continue;
                }
                //$rendered_content = str_replace($matches[0][$key][0], str_replace(chr(146),chr(39),$field[$value[0]]), $rendered_content);
                $rendered_content = str_replace($matches[0][$key][0], $field[$value[0]], $rendered_content);
                break;
            case '$':
                // chunk
                $child_template = $value[0];
                break;
            case '&':
                // object parameter variable
                if (!isset($this->parameter[$value[0]]))
                {
                    $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);
                    continue;
                }
                $rendered_content = str_replace($matches[0][$key][0], $this->parameter[$value[0]], $rendered_content);
                break;
            case '-':
                // comment area, do not render even if it matches any key
                $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);
                break;
            case '+':
                // placeholder, do not replace
                break;
            default:
                // view object, executing sub level rendering
                if (!isset($content[$value[0]]))
                {
                    $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);
                    continue;
                }
                $chunk_render = '';
                if (is_object( $content[$value[0]]))
                {
                    if (method_exists($content[$value[0]], 'render'))
                    {
                        $chunk_render = $content[$value[0]]->render();
                    }
                }
                $rendered_content = str_replace($matches[0][$key][0], $chunk_render, $rendered_content);
                // Un-recognized template variable types, do not process
                // $GLOBALS['global_message']->warning = 'Un-recognized template variable types. ('.__FILE__.':'.__LINE__.')';
                // $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);

        }
    }
    $rendered_result[] = $rendered_content;


    if(!isset($parameter['render_format'])) $parameter['render_format'] = 'none';
    switch($parameter['render_format'])
    {
        case 'array':
            $rendered_html = print_r($rendered_result, true);
            break;
        case 'json':
            $rendered_html = json_encode($rendered_result);
            break;
        default:
            $rendered_html = implode('', $rendered_result);
    }

    $this->rendered_html = $rendered_html;

    return $rendered_html;

}
