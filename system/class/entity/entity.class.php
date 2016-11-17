<?php
// Class Object
// Name: entity
// Description: Base class for all database table classes, read/write limited rows per query (PHP memory limit and system performance)

class entity extends base
{
    // database connection
    protected $_conn = null;

    // ids of select rows
    var $id_group = array();

    // row of values, INPUT/UPDATE data into the table and SELECT data from table
    public $row = null;

    // Object variables
    var $parameter = array();
    var $_initialized = false;

    // By default, all entities can be constructed by a number (id), an array of numbers (ids), a string of numbers separate by comma (e.g. "10,11,12") or a string of friendly url
    function __construct($value = null, $parameter = array())
    {
//print_r([$value, $parameter]);
        parent::__construct();
        if (!empty($parameter)) $this->set_parameter($parameter);

        if (empty($this->_conn))
        {
            if ($GLOBALS['db']) $db = $GLOBALS['db'];
            else $db = new db;
            $this->_conn = $db->db_get_connection();

            if (!isset($this->parameter['table']))
            {
                $this->parameter['table'] = DATABASE_TABLE_PREFIX.get_class($this);
            }

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

            // parameter['primary_key'] in entity need to be single column field, if it is not defined, default to id
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
        }

        // some relations (mostly multiple to multiple relations) are not saved in any entity tables, they are stored in relational tables
        if (!isset($this->parameter['relational_fields']))
        {
            $this->parameter['relational_fields'] = array();
        }

        if (!is_null($value))
        {
            $format = format::get_obj();
            $id_group = $format->id_group($value);
            if ($id_group === false)
            {
                if (is_string($value))
                {
                    $value = preg_replace('/^(.*)(-)(.*)/','$1',$value);
                    $parameter = array(
                        'bind_param' => array(':friendly_uri'=>$value),
                        'where' => array('`friendly_uri` = :friendly_uri')
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
        }


        $this->parameter['relational_fields'] = $this->construct_relational_fields($this->parameter['relational_fields']);

        return true;
    }

    protected function construct_relational_fields($relational_fields)
    {
        if ($GLOBALS['db']) $db = $GLOBALS['db'];
        else $db = new db;

        foreach ($relational_fields as $relational_field_name=>$relational_field)
        {
            if (!is_array($relational_field))  $relational_fields[$relational_field_name] = array();

            if (!isset($relational_fields[$relational_field_name]['table']))
            {
                $entity_pair = [str_replace('entity_','',get_class($this)), $relational_field_name];
                asort($entity_pair);
                $relational_fields[$relational_field_name]['table'] = 'tbl_rel_'.implode('_to_',$entity_pair);
            }
            if (!isset($relational_fields[$relational_field_name]['table_fields']))
            {
                $result = $db->db_get_columns($relational_fields[$relational_field_name]['table']);
                if ($result === false)
                {
                    return false;
                }
                else
                {
                    $relational_fields[$relational_field_name]['table_fields'] = $result;
                }
            }
            if (!isset($relational_fields[$relational_field_name]['primary_key']))
            {
                $result = $db->db_get_primary_key($relational_fields[$relational_field_name]['table']);
                if ($result === false)
                {
                    return false;
                }
                else
                {
                    $relational_fields[$relational_field_name]['primary_key'] = $result;
                }
            }
            // source_id_field is the field name in relation table (FK) that reference to current entity primary key
            // default source_id_field is entity name plus '_'.$this->parameter['primary_key'], e.g. for tbl_entity_organization, default source_id_field in any relation_table is organization_id
            if (!isset($relational_fields[$relational_field_name]['source_id_field'])) $relational_fields[$relational_field_name]['source_id_field'] = str_replace('entity_','',get_class($this)).'_'.$this->parameter['primary_key'];
            if (!in_array($relational_fields[$relational_field_name]['source_id_field'],$relational_fields[$relational_field_name]['primary_key']))
            {
                $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): source_id_field ['.$relational_fields[$relational_field_name]['source_id_field'].'] is not a defined PK field in relation table '.$relational_fields[$relational_field_name]['table'].' PK ('.implode(',',$relational_fields[$relational_field_name]['primary_key']).')';
                return false;
            }
            if (!isset($relational_fields[$relational_field_name]['target_id_field'])) $relational_fields[$relational_field_name]['target_id_field'] = $relational_field_name.'_id';
            if (!in_array($relational_fields[$relational_field_name]['target_id_field'],$relational_fields[$relational_field_name]['primary_key']))
            {
                $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): target_id_field ['.$relational_fields[$relational_field_name]['target_id_field'].'] is not a defined PK field in relation table '.$relational_fields[$relational_field_name]['table'].' PK ('.implode(',',$relational_fields[$relational_field_name]['primary_key']).')';
                return false;
            }

        }

        return $relational_fields;
    }

    function query($sql, $parameter=array())
    {
//print_r($sql.'<br>');
//print_r($parameter);
        $query = $this->_conn->prepare($sql);
        $query->execute($parameter);

        if ($query->errorCode() == '00000')
        {
            return $query;
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

    // Select id_group by conditions
    function get($parameter = array())
    {
//print_r($parameter);
        $format = format::get_obj();
        if (isset($parameter['id_group']))
        {
            $id_group = $format->id_group($parameter['id_group']);
            unset($parameter['id_group']);
        }
        else
        {
            if (empty($this->id_group))
            {
                if (empty($parameter['where']))
                {
                    $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' GET entity with empty id_group and where condition';
                    return false;
                }
                else
                {
                    $id_group = array();
                }
            }
            else
            {
                $id_group = $this->id_group;
            }
        }

        if (isset($parameter['table_fields']))
        {
            $table_fields = array();
            if (isset($parameter['table_fields'][0]))
            {
                foreach($parameter['table_fields'] as $table_field_index=>$table_field)
                {
                    $table_fields[$table_field] = $table_field;
                }
                $parameter['table_fields'] = $table_fields;
            }
        }

        if (isset($parameter['relational_fields']))
        {
            if (isset($parameter['relational_fields'][0])) $parameter['relational_fields'] = array_flip($parameter['relational_fields']);
            foreach ($parameter['relational_filed'] as $relational_field_name=>$relational_field)
            {
                if (empty($relational_field))
                {
                    if (isset($this->parameter['relational_fields'][$relational_field_name]))
                    {
                        $parameter['relational_fields'][$relational_field_name] = $this->parameter['relational_fields'][$relational_field_name];
                    }
                    else
                    {
                        $parameter['relational_fields'][$relational_field_name] = $this->construct_relational_fields([$relational_field_name]);
                    }
                }
            }
        }

        if (isset($parameter['fields']))
        {
            $parameter['table_fields'] = array();
            $parameter['relational_fields'] = array();
            foreach ($parameter['fields'] as $set_field_index=>$set_field)
            {
                if (isset($this->parameter['table_fields'][$set_field])) $parameter['table_fields'][$set_field] = $this->parameter['table_fields'][$set_field];
                if (isset($this->parameter['relational_fields'][$set_field])) $parameter['relational_fields'][$set_field] = $this->parameter['relational_fields'][$set_field];
            }
            unset($parameter['fields']);
        }

        $parameter = array_merge($this->parameter,$parameter);

        if (empty($parameter['bind_param']))
        {
            $parameter['bind_param'] = array();
        }

        $fields = array();
        $joins = array();
        foreach ($parameter['table_fields'] as $table_field_name=>$table_field)
        {
            $fields[] = $parameter['table'].'.'.$table_field;
        }
        foreach ($parameter['relational_fields'] as $relational_field_name=>$relational_field)
        {
            $fields[] = 'GROUP_CONCAT('.$relational_field['table'].'.'.$relational_field['target_id_field'].') AS '.$relational_field_name;
            $joins[] = 'LEFT JOIN '.$relational_field['table'].' ON '.$parameter['table'].'.'.$parameter['primary_key'].' = '.$relational_field['table'].'.'.$relational_field['source_id_field'];
        }
        $sql = 'SELECT '.implode(',',$fields).' FROM '.$parameter['table'].' '.implode(' ',$joins);
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
        if (!empty($id_group))
        {
            $where[] = $parameter['table'].'.'.$parameter['primary_key'].' IN ('.implode(',',array_keys($id_group)).')';
            $parameter['bind_param'] = array_merge($parameter['bind_param'],$id_group);
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

        $sql .= ' GROUP BY '.$parameter['table'].'.'.$parameter['primary_key'];

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
        $query = $this->query($sql,$parameter['bind_param']);
        if ($query === false) return false;

        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $new_id_group = array();
        $new_row = array();
        foreach ($result as $row_index=>$row_value)
        {
            $new_id_group[] = $row_value[$parameter['primary_key']];
            $new_row['id_'.$row_value[$parameter['primary_key']]] = $row_value;
        }
        // Keep the original id order if no specific "order by" is set
        if (empty($parameter['order']) AND !empty($id_group))
        {
            $this->id_group = array_intersect($id_group, $new_id_group);
            $this->row = array();
            foreach ($this->id_group as $id_index=>$id)
            {
                $this->row['id_'.$id] = $new_row['id_'.$id];
            }

        }
        else
        {
            $format = format::get_obj();
            $new_id_group = $format->id_group($new_id_group);
            $this->id_group = $new_id_group;
            $this->row = $new_row;
        }

        $this->_initialized = true;
        return $this->row;
    }

    // INSERT/UPDATE multiple rows of data, return id_group of inserted/updated rows
    function set($parameter = array())
    {
//print_r('set function');
//print_r($parameter);
        if (isset($parameter['row']))
        {
            $row = $parameter['row'];
            unset($parameter['row']);
        }
        else
        {
            if (empty($this->row))
            {
                $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' INSERT/UPDATE entity with empty row';
                return false;
            }
            else
            {
                $row = $this->row;
            }
        }

        if (isset($parameter['table_fields']))
        {
            $table_fields = array();
            if (isset($parameter['table_fields'][0]))
            {
                foreach($parameter['table_fields'] as $table_field_index=>$table_field)
                {
                    $table_fields[$table_field] = $table_field;
                }
                $parameter['table_fields'] = $table_fields;
            }
            // By default, leave enter_time and update_time untended, let MYSQL update them with system timestamp if they are not specified in set fields
            if (in_array('update_time', $parameter['table_fields']))
            {
                $flag_keep_update_time = true;
            }
            if (in_array('enter_time', $parameter['table_fields']))
            {
                $flag_keep_enter_time = true;
            }
        }

        if (isset($parameter['relational_fields']))
        {
            if (isset($parameter['relational_fields'][0])) $parameter['relational_fields'] = array_flip($parameter['relational_fields']);
            $parameter['relational_fields'] = $this->construct_relational_fields($parameter['relational_fields']);
        }

        if (isset($parameter['fields']))
        {
            $parameter['table_fields'] = array();
            $parameter['relational_fields'] = array();
            foreach ($parameter['fields'] as $set_field_index=>$set_field)
            {
                // row provided value for updating current entity table
                if (isset($this->parameter['table_fields'][$set_field])) $parameter['table_fields'][$set_field] = $this->parameter['table_fields'][$set_field];

                // row provided value for updating relational tables, e.g. category field to update rel_category_to_organization table
                if (isset($this->parameter['relational_fields'][$set_field])) $parameter['relational_fields'][$set_field] = $this->parameter['relational_fields'][$set_field];
            }
            unset($parameter['fields']);
        }

        $parameter = array_merge($this->parameter,$parameter);
        $format = format::get_obj();

        if (empty($flag_keep_update_time)) unset($parameter['table_fields']['update_time']);
        if (empty($flag_keep_enter_time)) unset($parameter['table_fields']['enter_time']);


        if ($GLOBALS['db']) $db = $GLOBALS['db'];
        else $db = new db;

        if (!isset($parameter['bind_param']))
        {
            $parameter['bind_param'] = array();
        }

        $id_group = array();
//print_r('sql');
//print_r($parameter);
//print_r($row);

        $sql = 'INSERT INTO '.$parameter['table'].' (`'.implode('`,`',$parameter['table_fields']).'`) VALUES (:'.implode(',:',$parameter['table_fields']).') ON DUPLICATE KEY UPDATE ';
        $field_bind = array();
        $rel_table_value = array();
        foreach ($parameter['table_fields'] as $field_index=>$field_name)
        {
            if ($field_name != $parameter['primary_key'])
            {
                $field_bind[] = '`'.$field_name.'` = :'.$field_name;
            }
        }
        $sql .= implode(',',$field_bind);
        unset($field_bind);
        $query = $this->_conn->prepare($sql);
//print_r($sql);
        $new_row = array();
        foreach ($row as $record_index=>$record)
        {
            $bind_value = array();

            // if the row value is not assigned with field_name as key, force assign them according to defined table_fields and relation_fields
            if (isset($record[0]))
            {
                if (count($record) < count($parameter['table_fields'])+count($parameter['relational_fields']) )
                {
                    $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' INSERT/UPDATE number of tokens ('.(count($parameter['table_fields'])+count($parameter['relational_fields'])).') does not match number of bound variables('.count($record).') - '.print_r($record,true);
                    continue;
                }
                $old_record = $record;
                $record = array();
                $count_table_fields = 0;
                foreach ($parameter['table_fields'] as $field_index=>$field_name)
                {
                    $record[$field_index] = $old_record[$count_table_fields];
                    $count_table_fields++;
                }
                foreach ($parameter['relational_fields'] as $field_index=>$field_name)
                {
                    $record[$field_index] = $old_record[$count_table_fields];
                    $count_table_fields++;
                }
            }

            foreach ($parameter['table_fields'] as $field_index=>$field_name)
            {
                if (isset($record[$field_name]))
                {
                    $bind_value[':'.$field_name] = $record[$field_name];
                }
                else
                {
                    $bind_value[':'.$field_name] = '';
                }
            }

            $bind_value = array_merge($bind_value,$parameter['bind_param']);

//print_r('bind value<br>');
//print_r($bind_value);

            if (count($bind_value) != count($parameter['table_fields']))
            {
                $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' INSERT/UPDATE number of tokens ('.count($parameter['table_fields']).') does not match number of bound variables('.count($bind_value).') - '.print_r($bind_value,true);
            }
            else
            {

                $query->execute($bind_value);
                if ($query === false) continue;

                $insert_respond = $query->rowCount();
                unset($record_primary_key);
                if ($insert_respond == 0)
                {
                    $record_primary_key = $bind_value[':'.$parameter['primary_key']];
                }
                else
                {
                    $query2 = $this->_conn->query('SELECT LAST_INSERT_ID() AS new_id;');
                    $result = $query2->fetch(PDO::FETCH_ASSOC);
                    if ($query2->errorCode() == '00000')
                    {
                        if ($result['new_id'] == 0)
                        {
                            if (isset($bind_value[':'.$parameter['primary_key']])) $record_primary_key = $bind_value[':'.$parameter['primary_key']];
                            else
                            {
                                $query_errorInfo = $query2->errorInfo();
                                $GLOBALS['global_message']->notice = 'Insert new record failed, probably has duplicate key';
                                continue;
                            }
                        }
                        else
                        {
                            $record_primary_key = $result['new_id'];
                        }
                    }
                    else
                    {
                        $query_errorInfo = $query2->errorInfo();
                        $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): SQL Error - '.$query_errorInfo[2];
                    }
                }
                if (isset($record_primary_key))
                {
                    $new_row['id_'.$record_primary_key] = $record;
                    $new_row['id_'.$record_primary_key][$parameter['primary_key']] = $record_primary_key;

                    foreach ($parameter['relational_fields'] as $relational_field_name=>$relational_field)
                    {
                        if (isset($record[$relational_field_name]))
                        {
                            $relational_table_bind_value = array();
                            $relational_table_bind_value[':'.$relational_field['source_id_field']] = $record_primary_key;
                            $relational_sql = 'SELECT GROUP_CONCAT(DISTINCT '.$relational_field['target_id_field'].' ORDER BY '.$relational_field['target_id_field'].') AS current_target_id_values FROM '.$relational_field['table'].' WHERE '.$relational_field['source_id_field'].' = :'.$relational_field['source_id_field'].';';
                            $relational_query = $this->query($relational_sql, $relational_table_bind_value);
                            if ($relational_query === false) continue;
                            $relational_result = $relational_query->fetch(PDO::FETCH_ASSOC);
                            if ($relational_result) $current_target_id = $relational_result['current_target_id_values'];
                            else $current_target_id = '';

                            $new_target_id_values = $record[$relational_field_name];
                            if (!is_array($new_target_id_values)) $new_target_id_values = explode(',',$new_target_id_values);
                            asort($new_target_id_values);
                            $new_target_id_values = array_unique($new_target_id_values);
                            $record[$relational_field_name] = implode(',',$new_target_id_values);

                            if ($current_target_id != $record[$relational_field_name])
                            {
                                // if target ids are different from original and update_time is not set, update entity table current row update_time
                                if (!isset($record['update_time']) AND $insert_respond == 0)
                                {
                                    $relational_sql = 'UPDATE '.$parameter['table'].' SET update_time = NOW() WHERE '.$parameter['primary_key'].' = '.$record_primary_key.';';
                                    $relational_query = $this->query($relational_sql);
                                }

                                // Delete every records in relation table corresponding to current entity row
                                $relational_sql = 'DELETE FROM '.$relational_field['table'].' WHERE '.$relational_field['source_id_field'].' = :'.$relational_field['source_id_field'].';';
                                $relational_query = $this->query($relational_sql, $relational_table_bind_value);

                                // If new target_id_values are not empty, insert each target_id into relation table
                                if (!empty($record[$relational_field_name]))
                                {
                                    $relational_table_bind_row = array();
                                    $relational_sql = 'INSERT INTO '.$relational_field['table'].'('.$relational_field['source_id_field'].','.$relational_field['target_id_field'].') VALUES ';
                                    foreach ($new_target_id_values as $target_id_index => $target_id_value)
                                    {
                                        $relational_table_bind_value[':'.$relational_field['target_id_field'].'_'.$target_id_index] = $target_id_value;
                                        $relational_table_bind_row[] = '(:'.$relational_field['source_id_field'].',:'.$relational_field['target_id_field'].'_'.$target_id_index.')';
                                    }
                                    $relational_sql .= implode(',',$relational_table_bind_row).';';
                                    $relational_query = $this->query($relational_sql, $relational_table_bind_value);
                                }

                                $insert_respond++;
                            }

                        }
                    }

                    $id_group[] = $record_primary_key;
                }
                if ($insert_respond == 0)
                {
                    $record_display = array();
                    foreach($record as $key=>$item)
                    {
                        $record_display[] = $key.'=>"'.$item.'"';
                    }
                    $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' Row ('.implode(',',$record_display).') has not been inserted or updated. All values might be same as original row.';
                }
            }
        }

        $this->id_group = $format->id_group($id_group);
        $this->row = $new_row;
        $this->_initialized = true;
        return $this->id_group;
    }

    function delete($parameter = array())
    {
        if (!$this->_initialized)
        {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot perform delete before it is initialized with get() or set() function';
            return false;
        }
        if (empty($this->id_group))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot perform delete with empty id_group';
            return array();
        }
        $parameter = array_merge($this->parameter,$parameter);
        $format = format::get_obj();

        if (empty($parameter['bind_param']))
        {
            $parameter['bind_param'] = array();
        }

        $sql = 'DELETE FROM '.$parameter['table'];

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
        $where[] = $parameter['primary_key'].' IN ('.implode(',',array_keys($this->id_group)).')';
        $parameter['bind_param'] = array_merge($parameter['bind_param'],$this->id_group);

        if (!empty($where))
        {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $query = $this->query($sql, $parameter['bind_param']);
        if ($query !== false)
        {
            if ($query->rowCount() == 0)
            {
                $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' no row deleted under condition '.print_r($where, true);
                return false;
            }
            else
            {
                $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' '.$query->rowCount().' row(s) deleted';
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    function update($value = array(), $parameter = array())
    {
        if (empty($value))
        {
            if (empty($this->row))
            {
                $GLOBALS['global_message']->warning = __FILE__.'(line '.__LINE__.'): '.get_class($this).' INSERT/UPDATE entity with empty value';
                return false;
            }
            else
            {
                $value = $this->row[0];
                $this->row = null;
            }
        }
        if (!$this->_initialized)
        {
            $GLOBALS['global_message']->error = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot perform delete before it is initialized with get() or set() function';
            return false;
        }
        if (empty($this->id_group))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' cannot perform delete with empty id_group';
            return array();
        }
        $parameter = array_merge($this->parameter,$parameter);
        $format = format::get_obj();

        if (empty($parameter['bind_param']))
        {
            $parameter['bind_param'] = array();
        }

        $sql = 'UPDATE '.$parameter['table'].' SET ';
        $rel_table_bind = array();
        foreach ($parameter['table_fields'] as $field_index=>$field_name)
        {
            if (isset($value[$field_name]))
            {
                $field_bind[] = '`'.$field_name.'` = :'.$field_name;
                $parameter['bind_param'][':'.$field_name] = $value[$field_name];
            }
        }
        $sql .= implode(',',$field_bind);
        unset($field_bind);

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
        $where[] = $parameter['primary_key'].' IN ('.implode(',',array_keys($this->id_group)).')';
        $parameter['bind_param'] = array_merge($parameter['bind_param'],$this->id_group);
        if (!empty($where))
        {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $query = $this->query($sql, $parameter['bind_param']);
        if ($query !== false)
        {
            if ($query->rowCount() == 0)
            {
                $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' no row updated for '.print_r($parameter['bind_param']).' under condition '.print_r($where, true);
                return false;
            }
            else
            {
                $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.get_class($this).' '.$query->rowCount().' row(s) updated';
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    function sync($parameter = array())
    {
        if (!isset($parameter['sync_table']))
        {
            $parameter['sync_table'] = str_replace('entity','view',$parameter['table']);
        }
        $parameter = array_merge($this->parameter,$parameter);

        if (!isset($parameter['join']))
        {
            $parameter['join'] = array();
        }

        if (!isset($parameter['sync_type']))
        {
            $parameter['sync_type'] = 'differential_sync';
        }

        if ($GLOBALS['db']) $db = $GLOBALS['db'];
        else $db = new db;

        if (!$db->db_table_exists($parameter['sync_table']))
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): table '.$parameter['sync_table'].' does not exist, attempt to init with init_sync function';
            $parameter['sync_type'] = 'init_sync'; // When target sync_table does not exist, can only perform init_sync (DROP TABLE AND CREATE TABLE)
        }

        if ($parameter['sync_type'] == 'init_sync')
        {
            $sql = 'DROP TABLE IF EXISTS '.$parameter['sync_table'].';';
            $update_fields = array();
            foreach ($parameter['update_fields'] as $field_index=>$field_value)
            {
                $update_fields[] = $field_value.' AS '.$field_index;
            }
            $sql .= 'CREATE TABLE '.$parameter['sync_table'].' SELECT '.implode(',',$update_fields).' FROM '.$parameter['table'].' '.implode(' ',$parameter['join']);
            if (!empty($parameter['where'])) $sql .= ' WHERE ('.implode(' AND ',$parameter['where']).')';
            if (!empty($parameter['group'])) $sql .= ' GROUP BY '.implode(', ',$parameter['group']);
            unset($update_fields);
            $sql .= ';';
            $sql .= 'ALTER TABLE '.$parameter['sync_table'].' ENGINE = MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
            $sql .= 'ALTER TABLE '.$parameter['sync_table'].' ADD PRIMARY KEY ('.$parameter['primary_key'].');';
            $sql .= 'ALTER TABLE '.$parameter['sync_table'].' MODIFY enter_time TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00"';
            $sql .= ', MODIFY update_time TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00"';
            if (isset($parameter['fulltext_key']))
            {
                foreach ($parameter['fulltext_key'] as $fulltext_index=>$fulltext_fields)
                {
                    $sql .= ', ADD FULLTEXT KEY '.$fulltext_index.' ('.implode(',',$fulltext_fields).')';
                }
            }
            $sql .= ';';
            $query = $this->query($sql);
            if ($query === false) return false;
            return true;
        }

        if ($parameter['sync_type'] == 'full_sync')
        {
            $sql = 'TRUNCATE TABLE '.$parameter['sync_table'].';';
            $update_fields = array();
            foreach ($parameter['update_fields'] as $field_index=>$field_value)
            {
                $update_fields[] = $field_value.' AS '.$field_index;
            }
            $sql = 'INSERT INTO '.$parameter['sync_table'].'('.implode(',',array_keys($parameter['update_fields'])).') (SELECT '.implode(',',$update_fields).' FROM '.$parameter['table'].' '.implode(' ',$parameter['join']);
            if (!empty($parameter['where'])) $sql .= ' WHERE ('.implode(' AND ',$parameter['where']).')';
            if (!empty($parameter['group'])) $sql .= ' GROUP BY '.implode(', ',$parameter['group']);
            unset($update_fields);
            $sql .= ') ON DUPLICATE KEY UPDATE '.$parameter['primary_key'].'='.$parameter['primary_key'].';';

            $query = $this->query($sql);
            if ($query === false) return false;
            return true;
        }

        if (!isset($parameter['sync_table_primary_key'])) {
            $result = $db->db_get_primary_key($parameter['sync_table']);
            if (empty($result[0]))
            {
                $parameter['sync_table_primary_key'] = 'id';
            }
            else
            {
                $parameter['sync_table_primary_key'] = $result[0];
            }
        }


        switch ($parameter['sync_type'])
        {
            case 'update_current':
                // update_current, force update current object id_group rows
                $sync_id_group = array(
                    'delete' => array(),
                    'update' => array(),
                    'insert' => $this->id_group
                );
                break;
            case 'delete_current':
                // delete_current, force delete current object id_group rows
                $sync_id_group = array(
                    'delete' => $this->id_group,
                    'update' => array(),
                    'insert' => array()
                );
                break;
            case 'differential_sync':
            default:
                // differential_sync, compare source table and target table id and updated date field to get the sync rows
                $sync_id_group = $db->db_compare_records(array(
                    'source_table'=>$parameter['table'],
                    'source_primary_key'=>$parameter['primary_key'],
                    'target_table'=>$parameter['sync_table'],
                    'target_primary_key'=>$parameter['sync_table_primary_key'],
                    'where'=>(!empty($parameter['where'])?$parameter['where']:'')
                ));
                if ($sync_id_group === false) return false;
        }

        // id_group to delete
        if (count($sync_id_group['delete']) > 0)
        {
            $sql = 'DELETE FROM '.$parameter['sync_table'].' WHERE '.$parameter['sync_table_primary_key'].' IN ('.implode(',',$sync_id_group['delete']).')';
            $query = $this->query($sql);
            if ($query !== false)
            {
                if ($query->rowCount() == 0)
                {
                    $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.$parameter['table'].' on sync to '.$parameter['sync_table'].' no row deleted';
                }
                else
                {
                    $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.$parameter['table'].' on sync to '.$parameter['sync_table'].' '.$query->rowCount().' row(s) deleted';
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            $GLOBALS['global_message']->notice = __FILE__.'(line '.__LINE__.'): '.$parameter['table'].' on sync to '.$parameter['sync_table'].' no row deleted';
        }

        $id_group = array_merge($sync_id_group['insert'], $sync_id_group['update']);
        if (count($id_group) > 0)
        {
            // Generate INSERT/UPDATE query
            if (!isset($parameter['sync_table_fields'])) {
                $result = $db->db_get_columns($parameter['sync_table']);
                if ($result === false) {
                    return false;
                } else {
                    $parameter['sync_table_fields'] = $result;
                }
            }

            if (!isset($parameter['join_query'])) $parameter['join_query'] = array();

            if (!isset($parameter['update_fields'])) $parameter['update_fields'] = array();

            $shared_table_fields = array_intersect($parameter['table_fields'], $parameter['sync_table_fields']);
            foreach ($shared_table_fields as $field_index => $field_name) {
                if (!array_key_exists($field_name, $parameter['update_fields'])) $parameter['update_fields'][$field_name] = $parameter['table'] . '.' . $field_name;
            }
            unset($shared_table_fields);

            // default table fields remove two timestamp fields, but on sync, they are required
            if (!array_key_exists('enter_time', $parameter['update_fields'])) $parameter['update_fields']['enter_time'] = $parameter['table'] . '.enter_time';
            if (!array_key_exists('update_time', $parameter['update_fields'])) $parameter['update_fields']['update_time'] = $parameter['table'] . '.update_time';

            $sql = 'INSERT INTO ' . $parameter['sync_table'] . ' (' . implode(',', array_keys($parameter['update_fields'])) . ')
SELECT ' . implode(',', $parameter['update_fields']) . ' FROM ' . $parameter['table'] . ' ' . implode(' ', $parameter['join']) . ' WHERE ' . $parameter['table'] . '.' . $parameter['primary_key'] . ' IN (' . implode(',', $id_group) . ')';
            if (!empty($parameter['where'])) $sql .= ' AND (' . implode(' AND ', $parameter['where']) . ')';
            if (!empty($parameter['group'])) $sql .= ' GROUP BY '.implode(', ',$parameter['group']);
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $update_fields = array();
            foreach ($parameter['update_fields'] as $field_index => $field_name) {
                $update_fields[] = $field_index . '=VALUES(' . $field_index . ')';
            }
            $sql .= implode(',', $update_fields);
            unset($update_fields);
            $query = $this->query($sql);
            if ($query !== false) {
                if ($query->rowCount() == 0) {
                    $GLOBALS['global_message']->notice = __FILE__ . '(line ' . __LINE__ . '): '.$parameter['table'].' on sync to '.$parameter['sync_table'].' no row inserted/updated';
                }
                else
                {
                    $GLOBALS['global_message']->notice = __FILE__ . '(line ' . __LINE__ . '): '.$parameter['table'].' on sync to '.$parameter['sync_table'].' '.$query->rowCount().' row(s)/field(s) inserted/updated';
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            $GLOBALS['global_message']->notice = __FILE__ . '(line ' . __LINE__ . '): '.$parameter['table'].' on sync to '.$parameter['sync_table'].' no row inserted/updated';
        }
        return true;
    }
}

?>