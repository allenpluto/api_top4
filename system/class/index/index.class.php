<?php
// Class Object
// Name: index
// Description: Base class for all database index classes, filters and search functions, primary key might not be unique
//              due to joining multiple to multiple relationship tables

class index extends base
{
    // database connection
    protected $_conn = null;

    // ids of select rows
    var $id_group = array();

    // Object variables
    var $parameter = array();
    var $_initialized = false;

    function __construct($value = null, $parameter = array())
    {
        if (!empty($parameter)) $this->set_parameter($parameter);

        if ($GLOBALS['db']) $db = $GLOBALS['db'];
        else $db = new db;
        $this->_conn = $db->db_get_connection();

        // By default, index object name as database index table name
        // parameter['table'] in index is normally multiple tables with JOIN conditions, e.g. $this->parameter['table'] = 'tbl_entity_organization JOIN tbl_entity_organization parent_organization ON tbl_entity_organization.parent_organization_id = parent_organization.id'
        if (!isset($this->parameter['table'])) {
            $this->parameter['table'] = DATABASE_TABLE_PREFIX . get_class($this);
        }

        // parameter['table_fields'] in index suggest the columns being selected;
        // when multiple tables are joined, fields would probably need reference the tables they are in and might need alias, e.g. parameter['table_fields'] = 'tbl_entity_organization.id, tbl_entity_organization.name, parent_organization.id AS parent_id, parent_organization.name AS parent_name'
        // index tables should only have the search related tables, e.g. date, id, value... anything that can be search criteria
        if (!isset($this->parameter['table_fields'])) {
            $result = $db->db_get_columns($this->parameter['table']);
            if ($result === false) {
                return false;
            } else {
                $this->parameter['table_fields'] = $result;
            }
        }

        // parameter['primary_key'] in index need to be single column field, if it is not defined, default to id
        if (!isset($this->parameter['primary_key'])) {
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

        // parameter['fulltext_index'] in index
        if (!isset($this->parameter['fulltext_index'])) {
            $result = $db->db_get_fulltext_index($this->parameter['table']);
            if ($result === false) {
                $this->parameter['fulltext_index'] = array();
            } else {
                $this->parameter['fulltext_index'] = $result;
            }
        }

        if (!isset($this->parameter['fulltext_index'])) {
            $this->parameter['primary_key'] = 'id';
        }

        if (!empty($value)) {
            $format = format::get_obj();

            $id_group = $format->id_group($value);
            if ($id_group === false)
            {
                $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): ['.get_class($this).'] initialize object with invalid id(s)';
                return false;
            }
            else
            {
                $this->id_group = $id_group;
                $this->get();
            }
        }
    }

    function reset()
    {
        $this->id_group = array();
        $this->_initialized = false;
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
        $this->parameter = array_merge($this->parameter, $parameter);
    }

    function get($parameter = array())
    {
        $parameter = array_merge($this->parameter,$parameter);

        if (count($this->id_group) > 0)
        {
            $this->_initialized = true;
        }

        if (empty($parameter['bind_param']))
        {
            $parameter['bind_param'] = array();
        }

        $sql = 'SELECT DISTINCT '.$parameter['primary_key'];
        if (isset($parameter['get_field']))
        {
            foreach ($parameter['get_field'] as $field_alias => $field_value)
            {
                $sql .= ', '.$field_value.' AS '.$field_alias;
            }
        }
        else
        {
            $parameter['get_field'] = array();
        }
        $sql .= ' FROM '.$parameter['table'];
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

        if (!empty($parameter['group']))
        {
            if (is_array($parameter['group']))
            {
                $parameter['group'] = implode(', ', $parameter['group']);
            }
            $sql .= ' GROUP BY '.$parameter['group'];
        }

        if (!empty($parameter['order']))
        {
            if (is_array($parameter['order']))
            {
                $parameter['order'] = implode(', ', $parameter['order']);
            }
            $sql .= ' ORDER BY '.$parameter['order'];
        }

        $result = $this->query($sql,$parameter['bind_param']);

        if ($result !== false)
        {
            $new_id_group = array();
            $get_field = array();
            foreach ($result as $row_index=>$row_value)
            {
                $row_id =  $row_value[$parameter['primary_key']];
                $new_id_group[] = $row_id;
                foreach ($parameter['get_field'] as $field_alias => $field_value)
                {
                    if (isset($parameter['get_field']))
                    {
                        $get_field[$field_alias][$row_id] = $row_value[$field_alias];
                    }
                }
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
            if (!empty($get_field)) return $get_field;
            else return $this->id_group;
        }
        else
        {
            return false;
        }
    }

    function full_text_search($parameter = array())
    {
        if (!isset($parameter['value']))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): Full text search with empty value input';
            return false;
        }
        if (isset($parameter['fulltext_index_key']))
        {
            $parameter['fulltext_columns'] = $this->parameter['fulltext_index'][$parameter['fulltext_index_key']];
        }
        if (empty($parameter['fulltext_columns']))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): Full text search without corresponding fulltext fields';
            return false;
        }
        if (!isset($parameter['fulltext_mode'])) $parameter['fulltext_mode'] = 'boolean';
        if (!isset($parameter['fulltext_min_score'])) $parameter['fulltext_min_score'] = 0;
        if (!isset($parameter['special_pattern'])) $parameter['special_pattern'] = '';

        switch ($parameter['fulltext_mode'])
        {
            case 'nature-language':
                $fulltext_mode_text = 'IN NATURAL LANGUAGE MODE';
                break;
            case 'expansion':
                $fulltext_mode_text = 'WITH QUERY EXPANSION';
                break;
            case 'nature-language-expansion':
                $fulltext_mode_text = 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION';
                break;
            case 'boolean':
            default:
                $fulltext_mode_text = 'IN BOOLEAN MODE';
        }

        $original_id_group = array();
        if ($this->_initialized)
        {
            if (empty($this->id_group))
            {
                $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): Full text search filter down from empty id_group';
                return false;
            }
            else
            {
                $original_id_group = $this->id_group;
            }
        }

        $format = format::get_obj();
        $keyword_phrases = $format->search_term(array('value' => $parameter['value'], 'special_pattern' => $parameter['special_pattern']));
        $keyword = implode(' ', $keyword_phrases['full_text_word']);

        // MYSQL fulltext search for standard english words and numbers
        if (!empty($keyword))
        {
            $filter_parameter = array(
                'get_field' => array(
                    'score' => 'MATCH('.implode(', ',$parameter['fulltext_columns']).') AGAINST (:keyword '.$fulltext_mode_text.')'
                    //'score' => 'MATCH(title) AGAINST (:keyword IN BOOLEAN MODE) / '.count($keyword_phrases)
                ),
                'bind_param' => array(
                    ':keyword' => $keyword
                ),
                'where' => 'MATCH('.implode(', ',$parameter['fulltext_columns']).') AGAINST (:keyword) > '.$parameter['fulltext_min_score'],
                //'where' => 'MATCH(title) AGAINST (:keyword) > 0',
                'order' => 'score DESC'
            );
            $result = $this->get($filter_parameter);
        }
        else
        {
            $result = false;
        }
        if ($result === false)
        {
            $result = array('score'=>array());
        }

        // Specific search for words with given special characters, too many of these might reduce the system performance
        foreach ($keyword_phrases['special_word'] as $special_word_index=>$special_word_item)
        {
            $this->id_group = $original_id_group;
            $special_word_score = 1;
            switch ($parameter['fulltext_mode'])
            {
                case 'nature-language':
                case 'expansion':
                case 'nature-language-expansion':
                    $special_word_score = count($special_word_item);
                    break;
                case 'boolean':
                default:
                    $special_word_score = 1;
            }

            $filter_parameter = array(
                'get_field' => array(
                    'score' => $special_word_score
                ),
                'where' => 'CONCAT(\' \','.implode(',\' \',',$parameter['fulltext_columns']).',\' \') LIKE CONCAT(\'% \',:keyword,\' %\')',
                'bind_param' => array(':keyword'=>$special_word_item)
            );
            $new_result = $this->get($filter_parameter);
            if ($new_result === false) continue;
            if (!isset($new_result['score'])) continue;

            foreach ($new_result['score'] as $id=>$row_value)
            {
                if (isset($result['score'][$id]))
                {
                    $result['score'][$id] += $row_value;
                }
                else
                {
                    $result['score'][$id] = $row_value;
                }
            }
        }

        $result_id_group = array();

        if (!isset($result['score']))
        {
            $this->id_group = array();
            return false;
        }
        $result_id_group = array_keys($result['score']);
        array_multisort($result['score'], SORT_NUMERIC, SORT_DESC, $result_id_group);
//echo '<pre>';
//print_r($result['score']);
//print_r($result_id_group);
        $max_score = $result['score'][0];

        // Return score as array(id_1=>score_1,id_2=>score_2...)
        $new_result = array();
        $new_result_id_group = array();
        foreach ($result['score'] as $index=>$score)
        {
            $new_result[$result_id_group[$index]] = round($score/$max_score,6);
            if ($new_result[$result_id_group[$index]] <= $parameter['fulltext_min_score'])
            {
                unset($new_result[$result_id_group[$index]]);
                break;
            }
            $new_result_id_group[] = $result_id_group[$index];
        }

        $this->id_group = $format->id_group($new_result_id_group);
        return $new_result;
    }


}