<?php
// Include Class Object
// Name: format
// Description: format functions

// Format Related Functions


class format
{
    private static $cached = array();
    private static $_obj = null;

    private function __construct()
    {
    }
    function __destruct()
    {
    }

    public static function get_obj()
    {
        if (empty(self::$_obj))
        {
            self::$_obj = new format();
        }
        return self::$_obj;
    }

    public function __call($method, $value = array())
    {
        if (!method_exists(self::$_obj, $method)) {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' class method "'.$method.'" does not exist';
            return false;
        }
        else
        {
            $flatten_value = $this->minify_js(json_encode($value));
            if ($GLOBALS['global_message']->format_cache AND isset(self::$cached[$method][$flatten_value]))
            {
                $GLOBALS['global_message']->notice = 'Format value from cache ['.$method.']:'.$flatten_value;
                return self::$cached[$method][$flatten_value];
            }
            else
            {
                $result = call_user_func_array(array(self::$_obj, $method), $value);
                if (strlen($flatten_value) <= 200)
                {
                    if(!isset(self::$cached[$method])) self::$cached[$method] = array();
                    self::$cached[$method][$flatten_value] = $result;
                }
                return $result;
            }
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////      Text Input/Output Format Filters    /////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////

    // HTML DOM Element id, class, PHP function name
    private function element_name($value)
    {
        $value = strtolower($value);
        $value = preg_replace('/[^_a-z0-9]/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);
        $result = trim($value,'_');

        return $result;
    }

    // URI, HTML DOM Element friendly-url, Assets (images, downloadable files) file name
    private function file_name($value)
    {
        $value = strtolower($value);
        $value = preg_replace('/[^-a-z0-9]/', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $result = trim($value,'-');

        return $result;
    }

    // Display Text Caption, separate words by space, easier to read
    private function caption($value)
    {
        $value = preg_replace('/[^\sa-z0-9]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $result = trim($value);

        return $result;
    }

    // Display Phone Number
    private function phone($value)
    {
        $value = preg_replace('/[abc]/i', '2', $value);
        $value = preg_replace('/[def]/i', '3', $value);
        $value = preg_replace('/[ghi]/i', '4', $value);
        $value = preg_replace('/[jkl]/i', '5', $value);
        $value = preg_replace('/[mno]/i', '6', $value);
        $value = preg_replace('/[pqrs]/i', '7', $value);
        $value = preg_replace('/[tuv]/i', '8', $value);
        $value = preg_replace('/[wxyz]/i', '9', $value);
        $value = preg_replace('/[^\d]/', '', $value);

        switch(strlen($value))
        {
            case 10:
                if(substr($value,0,2) == '04' or substr($value,0,2) == '13' or substr($value,0,2) == '18') //Format for Mobile, National and Free Phone Number
                {
                    $result = substr($value,0,4).' '.substr($value,4,3).' '.substr($value,7,3);
                }
                else
                {
                    $result = '('.substr($value,0,2).') '.substr($value,2,4).' '.substr($value,6,4);
                }
                break;
            case 8:
                $result = substr($value,0,4).' '.substr($value,4,4);
                break;
            case 6:
                $result = substr($value,0,2).' '.substr($value,2,2).' '.substr($value,4,2);
                break;
            default:
                $result = $value;
                // Unknown type of phone number, return without space it out
        }
        return $result;
    }

    // Display Time (transfer any float number 0-1 to string 00:00-24:00)
    private function time($value)
    {
        $minutes = round($value*1440);
        $hour = floor($minutes/60);
        $minute = $minutes%60;
        $result = ($hour<10?'0':'').$hour.':'.($minute<10?'0':'').$minute;
        return $result;
    }

    // Display Website URI
    private function uri($value)
    {
        $result = trim($value);
        if (empty($result)) return $result;

        if (!preg_match("/^https?\:\/\//", $result))
        {
            $result = str_replace(":","",$result);
            $result = str_replace("//","/",$result);
            $result = trim($result,'/');
            $result = 'http://'.$result;
        }

        return $result;
    }

    // Display ABN
    private function abn($value)
    {
        $result = trim($value);
        switch(strlen($result))
        {
            case 11:    // ABN
                $result = substr($value,0,2).' '.substr($value,2,3).' '.substr($value,5,3).' '.substr($value,8,3);
                break;
            case 9:     // ASIC/ACN
                $result = substr($value,0,3).' '.substr($value,3,3).' '.substr($value,6,3);
                break;
        }
        return $result;
    }

    // HTML content filter, allow basic html tags, remove rest
    // Note: need to be wrapped by '<div>' so even content has broken html wont broke the whole page
    private function html_content($value)
    {
        $result = strip_tags($value, '<h2><h3><h4><h5><h6><p><ul><ol><li><a><span><strong><em>');
        return $result;
    }

    private function minify_html($value)
    {
        if (!is_string($value))
        {
            return false;
        }

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
    }

    private function minify_css($value)
    {
        if (!is_string($value))
        {
            return false;
        }

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
        return preg_replace($search, $replace, $value);
    }

    private function minify_js($value)
    {
        if (!is_string($value))
        {
            return false;
        }

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
    }


    private function id_group($value)
    {
        if (empty($value))
        {
            if (is_array($value)) return array();
            else return false;
        }

        if (is_array($value))
        {
            if (!empty($value['value']))
            {
                extract($value);
            }
        }

        if (!is_array($value))
        {
            $value = explode(',',$value);
        }
        $counter = 0;
        if (!isset($key_prefix))
        {
            $key_prefix = ':id_';
        }
        $result = array();
        foreach ($value as $id_index=>$id_value)
        {
            if (is_numeric($id_value))
            {
                $id_value = intval($id_value);
                if ($id_value > 0)
                {
                    $result[$key_prefix.$counter] = $id_value;
                    $counter++;
                }
            }
        }

        if ($counter > 0)
        {
            return $result;
        }
        else
        {
            return false;
        }
    }

    private function pagination_param($value)
    {
        $result = array();
        if (isset($value['page_size']))
        {
            $result['page_size'] = intval($value['page_size']);
            if ($result['page_size'] <= 0)
            {
                return false;
            }
        }
        if (isset($value['page_number']))
        {
            $result['page_number'] = intval($value['page_number']);
            if ($result['page_number'] < 0)
            {
                return false;
            }
        }
        return $result;
    }

    private function search_term($value)
    {
        if (empty($value))
        {
            return false;
        }
        if (is_array($value))
        {
            if (!empty($value['value']))
            {
                extract($value);
            }
        }
        if (!isset($delimiter)) $delimiter = ' ';
        // Make sure min_string_length is greater or equal to db/solr full text search min characters
        // any word with less length will go into special word, too many special word will slow search down
        if (!isset($min_string_length)) $min_string_length = 3;
        if (!isset($special_pattern)) $special_pattern = '';
        if (is_array($value))
        {
            $value = implode($delimiter,$value);
        }
        $value = preg_replace('/[^'.$special_pattern.'a-zA-Z0-9\s\']+/', $delimiter, $value);
        $value = explode($delimiter,$value);
        $result = array('full_text_word'=>array(),'special_word'=>array());
        foreach($value as $key=>$item)
        {
            $item = strtolower(trim($item));
            if (strlen($item) < $min_string_length)
            {
                if (strlen($item) > 0) $result['special_word'][] = $item;
            }
            else
            {
                if (!empty($special_pattern))
                {
                    if (preg_match('/['.$special_pattern.']/', $item))  $result['special_word'][] = $item;
                    else $result['full_text_word'][] = $item;
                }
                else
                {
                    $result['full_text_word'][] = $item;
                }
            }
        }
        $result['full_text_word'] = array_unique($result['full_text_word']);
        $result['special_word'] = array_unique($result['special_word']);
        return $result;
    }

    private function split_uri($value)
    {
        $value_part = explode('/',$value);
        $result = array();
        foreach($value_part as $value_part_index=>$value_part_item)
        {
            $result[] = urldecode($value_part_item);
        }
        return $result;
    }
}
?>