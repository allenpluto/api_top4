<?php
// Class Object
// Name: view
// Description: Base class for all database view classes, read only, represents large number of rows
// Stored Value:
//              id_group -  row ids, set on get
//              row - row values of desired page, set on fetch_value, unset on get
//              rendered_html - implement html template of row, set on render, unset on get and fetch_value

class view extends base
{
    // database connection
    protected $_conn = null;

    // ids of select rows
    var $id_group = array();

    // row of values, only fetch on fetch_value(), clear on get()
    public $row = null;

    // rendered html, only generate on render(), clear on get()
    protected $rendered_html = null;

    // Object variables
    var $parameter = array();
    var $_initialized = false;

    /**
     *
     */
    function __construct($value = null, $parameter = array())
    {
        if (!empty($parameter)) $this->set_parameter($parameter);

        if ($GLOBALS['db']) $db = $GLOBALS['db'];
        else $db = new db;
        $this->_conn = $db->db_get_connection();

        // By default, view object name as database view table name, but if certain view object does not have corresponded view table in db, table name to be overwritten, columns and primary key also need to be defined specifically

        // parameter['table'] in view does not necessarily mean one table, can be multiple tables with JOIN conditions, e.g. $this->parameter['table'] = 'tbl_entity_organization JOIN tbl_entity_organization parent_organization ON tbl_entity_organization.parent_organization_id = parent_organization.id'
        if (!isset($this->parameter['table']))
        {
            $this->parameter['table'] = DATABASE_TABLE_PREFIX.get_class($this);
        }

        // parameter['table_fields'] in view suggest the columns being selected, when multiple tables are joined, fields would probably need reference the tables they are in and might need alias, e.g. parameter['table_fields'] = 'tbl_entity_organization.id, tbl_entity_organization.name, parent_organization.id AS parent_id, parent_organization.name AS parent_name'
        if (!isset($this->parameter['table_fields']))
        {
            $result = $db->db_get_columns($this->parameter['table']);
            if ($result === false)
            {
                return false;
            }
            else
            {
                $this->parameter['table_fields'] = $result;
            }
        }

        // parameter['primary_key'] in view need to be single column field, if it is not defined, default to id
        if (!isset($this->parameter['primary_key']))
        {
            $result = $db->db_get_primary_key($this->parameter['table']);
            if (empty($result[0]))
            {
                $this->parameter['primary_key'] = 'id';
            }
            else
            {
                $this->parameter['primary_key'] = $result[0];
            }
        }

        if (!isset($this->parameter['template']))
        {
            if (file_exists(PATH_TEMPLATE.get_class($this).FILE_EXTENSION_TEMPLATE))
            {
                $this->parameter['template'] = get_class($this);
            }
            else
            {
                $this->parameter['template'] = '';
            }
        }

        if (!isset($this->parameter['page_number']))
        {
            $this->parameter['page_number'] = 0;
        }

        if (!isset($this->parameter['page_size']))
        {
            $this->parameter['page_size'] = $GLOBALS['global_preference']->view_page_size;
        }

        $this->parameter['page_count'] = 0;


        if (!is_null($value))
        {
            $format = format::get_obj();
            $id_group = $format->id_group($value);
            if ($id_group === false)
            {
                if (is_string($value))
                {
                    $parameter = array(
                        'bind_param' => array(':friendly_url'=>$value),
                        'where' => array('`friendly_url` = :friendly_url')
                    );
                    $this->get($parameter);
                }
                else
                {
                    $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' initialize object with invalid id(s) {'.print_r($id_group,1).'}';
                    return false;
                }
            }
            else
            {
                $this->id_group = $id_group;
                $this->get();
            }

            /*if (is_array($value))
            {
                $this->id_group = $value;
                $this->get();
            }
            else    // Simplified usage, not secured
            {
                if (is_numeric($value)) // try to initialize with id
                {
                    $this->id_group = array('id'=>$value);
                    $this->get();
                }
                else // try to initialize with friendly url
                {
                    $parameter = array(
                        'bind_param' => array(':friendly_url'=>$value),
                        'where' => array('`friendly_url` = :friendly_url')
                    );
                    $this->get($parameter);
                }
            }
            $this->parameter['page_count'] = ceil(count($this->id_group)/$this->parameter['page_size']);
*/
        }

        return true;
    }

    function query($sql, $parameter=array())
    {
//print_r($sql);
//print_r($parameter);
        $query = $this->_conn->prepare($sql);
        $query->execute($parameter);

        if ($query->errorCode() == '00000')
        {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        else
        {
            $query_errorInfo = $query->errorInfo();
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): SQL Error - '.$query_errorInfo[2];
            return false;
        }
    }

    function set_parameter($parameter = array())
    {
        // In view table, primary_key are fixed during construction
        if (isset($parameter['primary_key']))
        {
            unset($parameter['primary_key']);
        }
        $this->parameter = array_merge($this->parameter, $parameter);
    }

    function set_page_size($page_size)
    {
        $page_size = intval($page_size);
        if ($page_size > 0)
        {
            $this->parameter['page_number'] = 0;
            $this->parameter['page_size'] = $page_size;
            $this->parameter['page_count'] = ceil(count($this->id_group)/$this->parameter['page_size']);
        }
    }

    function get($parameter = array())
    {
        // When id_group changes, reset the stored row value and rendered html
        $this->row = null;
        $this->rendered_html = null;

        $parameter = array_merge($this->parameter,$parameter);

        if (count($this->id_group) > 0)
        {
            $this->_initialized = true;
        }

        if (empty($parameter['bind_param']))
        {
            $parameter['bind_param'] = array();
        }

        $sql = 'SELECT '.$parameter['primary_key'].' FROM '.$parameter['table'];
        $where = array();
        if (!empty($parameter['where']))
        {
            if (is_array($parameter['where']))
            {
                $where = $parameter['where'];
            }
            else
            {
                $where[] = $parameter['where'];
            }
        }
        if ($this->_initialized)
        {
            if (!empty($this->id_group))
            {
                $where[] = $parameter['primary_key'].' IN ('.implode(',',array_keys($this->id_group)).')';
                $parameter['bind_param'] = array_merge($parameter['bind_param'],$this->id_group);
            }
        }

        if (!empty($where))
        {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        else
        {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot retrieve records with none specific where conditions and empty id_group in view.';
            return false;
        }

        if (!empty($parameter['order']))
        {
            if (is_array($parameter['order']))
            {
                $parameter['order'] = implode(', ', $parameter['order']);
            }
            $sql .= ' ORDER BY '.$parameter['order'];
        }
        if (!empty($parameter['limit']))
        {
            $sql .= ' LIMIT '.$parameter['limit'];
        }
        if (!empty($parameter['offset']))
        {
            $sql .= ' OFFSET '.$parameter['offset'];
        }
        $result = $this->query($sql,$parameter['bind_param']);
        if ($result !== false)
        {
            $new_id_group = array();
            foreach ($result as $row_index=>$row_value)
            {
                $new_id_group[] = $row_value[$parameter['primary_key']];
            }
            // Keep the original id order if no specific "order by" is set
            if ($this->_initialized AND empty($parameter['order'])) $this->id_group = array_intersect($this->id_group, $new_id_group);
            else
            {
                $format = format::get_obj();
                $new_id_group = $format->id_group($new_id_group);
                $this->id_group = $new_id_group;
            }

            $this->_initialized = true;
            $this->parameter['page_count'] = ceil(count($this->id_group)/$this->parameter['page_size']);
            return $this->id_group;
        }
        else
        {
            return false;
        }
    }

    function fetch_value($parameter = array())
    {
        if (isset($this->row))
        {
            return $this->row;
        }
        // unset rendered html when corresponding row value changes
        $this->rendered_html = null;
        if (!$this->_initialized)
        {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot fetch value before it is initialized with get() function';
            return false;
        }
        if (empty($this->id_group))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' fetch value from empty array';
            return array();
        }
        $parameter = array_merge($this->parameter,$parameter);
        $page_number = intval($parameter['page_number']);
        if ($page_number > $parameter['page_count']-1) $page_number =  $parameter['page_count']-1;
        if ($page_number < 0) $page_number = 0;
        $page_size = intval($parameter['page_size']);
        if ($page_size < 1) $page_size = 1;
        $sql = 'SELECT `'.implode('`,`',$parameter['table_fields']).'` FROM '.$this->parameter['table'];
        $where = $parameter['primary_key'].' IN ('.implode(',',array_keys($this->id_group)).')';
        $order = 'FIELD('.$this->parameter['primary_key'].','.implode(',',array_keys($this->id_group)).')';
        $bind_param = $this->id_group;
        $sql .= ' WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$page_size.' OFFSET '.$page_number*$page_size;
        $result = $this->query($sql,$bind_param);
        if ($result !== false)
        {
            $this->row = $result;
        }
        else
        {
            $this->row = array();
        }
        return $this->row;
    }

    function pre_render($parameter = array()) {}    // dummy method, for sub class to overwrite

    function render($parameter = array())
    {
        if (isset($this->rendered_html) AND !isset($parameter['template']))
        {
            return $this->rendered_html;
        }

        if (!isset($this->row))
        {
            $result = $this->fetch_value($parameter);

            if ($result === false)
            {
                $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot render object due to fetch value failed';
                return false;
            }
        }

        if (empty($this->row))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' rendering empty array';
            return '';
        }

        $parameter = array_merge($this->parameter,$parameter);

        $this->pre_render($parameter);

        $template = PATH_TEMPLATE.$parameter['template'].FILE_EXTENSION_TEMPLATE;
        if (!file_exists($template)) $template = '';
        else $template = file_get_contents($template);

        if (count($this->row) > 0)
        {
            if (empty($template))
            {
                return print_r($this->row,1);
            }
            else
            {
                $rendered_result = array();
                foreach ($this->row as $row_index=>$row_value)
                {
                    $content = $row_value;
                    if (isset($parameter['extra_content']))
                    {
                        $content = array_merge($content,$parameter['extra_content']);
                    }
                    $rendered_content = $template;
                    preg_match_all('/\[\[(\W+)?(\w+)\]\]/', $template, $matches, PREG_OFFSET_CAPTURE);
                    foreach($matches[2] as $key=>$value)
                    {
                        switch ($matches[1][$key][0])
                        {
                            case '*':
                                // simple value variable
                                if (!isset($content[$value[0]]))
                                {
                                    $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);
                                    continue;
                                }
                                $rendered_content = str_replace($matches[0][$key][0], str_replace(chr(145),chr(39),$content[$value[0]]), $rendered_content);
                                $rendered_content = str_replace($matches[0][$key][0], str_replace(chr(146),chr(39),$content[$value[0]]), $rendered_content);
                                break;
                            case '$':
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
                                // Un-recognized template variable types, do not process
                                $GLOBALS['global_message']->warning = 'Un-recognized template variable types. ('.__FILE__.':'.__LINE__.')';
                                $rendered_content = str_replace($matches[0][$key][0], '', $rendered_content);

                        }
                    }
                    $rendered_result[] = $rendered_content;
                }

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
        }
        else
        {
            return '';
        }
    }
}

?>