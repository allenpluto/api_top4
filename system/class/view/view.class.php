<?php
// Class Object
// Name: view
// Description: Base class for all database view classes, read only, represents large number of rows (store id only to reduce php memory use)

class view
{
	// database connection
	protected $_conn = null;

	// ids of select rows
	var $content = array();
	
	// Object variables
	var $parameters = array();
	var $message = null;
	var $_initialized = false;
	
	function __construct()
	{
        if ($GLOBALS['db']) $db = $GLOBALS['db'];
		else $db = new db;
		$this->_conn = $db->db_get_connection();
		
		// By default, view object name as database view table name, but if certain view object does not have corresponded view table in db, table name to be overwritten, columns and primary key also need to be defined specifically
		
		// parameters['table'] in view does not necessarily mean one table, can be multiple tables with JOIN conditions, e.g. $this->parameters['table'] = 'tbl_entity_organization JOIN tbl_entity_organization parent_organization ON tbl_entity_organization.parent_organization_id = parent_organization.id'
		if (!isset($this->parameters['table']))
		{
			$this->parameters['table'] = DATABASE_TABLE_PREFIX.get_class($this);
		}
			
		// parameters['table_fields'] in view suggest the columns being selected, when multiple tables are joined, fields would probably need reference the tables they are in and might need alias, e.g. parameters['table_fields'] = 'tbl_entity_organization.id, tbl_entity_organization.name, parent_organization.id AS parent_id, parent_organization.name AS parent_name'
		if (!isset($this->parameters['table_fields']))
		{
			$result = $db->db_get_columns($this->parameters['table']);
			if ($result === false)
			{
				$this->message = $db->message;
				return false;			
			}
			else
			{
				$this->parameters['table_fields'] = $result;
			}
		}
		
		// parameters['primary_key'] in view need to be single column field, if it is not defined, default to id
		if (!isset($this->parameters['primary_key']))
		{
			$result = $db->db_get_primary_key($this->parameters['table']);
			if ($result === false)
			{
				$this->message = $this->_conn->message;
				return false;			
			}
			else
			{
				if (count($result) == 1)
				{
					$this->parameters['primary_key'] = '`'.$result[0].'`';
				}
				else
				{
					// Views do not necessarily have defined primary key column, assume it is 'id'
					$this->parameters['primary_key'] = '`id`';				
				}
			}
		}
		
	}

	function query($sql, $parameters=array())
	{

		$query = $this->_conn->prepare($sql);
		$query->execute($parameters);
//print_r($query);
//exit();

		return $query;
	}

	function get($parameters = array())
	{
		if (count($this->id_group) > 0)
		{
			$this->_initialized = true;		// In case initialize process is done out of class functions
		}

		if (empty($parameters['bind_param']))
		{
			$parameters['bind_param'] = array();
		}

		$sql = 'SELECT '.$this->parameters['primary_key'].' FROM '.$this->parameters['table'];
		if ($this->_initialized)
		{
			if (!empty($this->id_group))
			{
				$where_id = $this->parameters['primary_key'].' IN (-1';

				foreach ($this->id_group as $row_id_index=>$row_id_value)
				{
					$where_id .= ',:id_'.$row_id_index;
					$parameters['bind_param'][':id_'.$row_id_index] = $row_id_value;
				}
				$where_id .= ')'; 
			}
		}
		if (!empty($parameters['where']))
		{
			if (is_array($parameters['where']))
			{
				$where = array_merge($parameters['where'],array($where_id));
			}
			else
			{
				$where = array_merge(array($parameters['where']),array($where_id));
			}
		}
		else
		{
			$where = array($where_id);
		}
		
		if (!empty($where))
		{
			$sql .= ' WHERE '.implode(' AND ', $where);
		}
		else
		{
			$this->message[] = 'Error: Cannot retrieve records without specific where conditions and empty id_group.';
			return false;
		}
		
		if (!empty($parameters['order']))
		{
			if (is_array($parameters['order']))
			{
				$parameters['order'] = implode(', ', $parameters['where']);
			}
			$sql .= ' ORDER BY '.$parameters['order'];
		}
		else
		{
			if ($this->_initialized)
			{
				if (!empty($this->id_group))
				{
					$order = 'FIELD('.$this->parameters['primary_key'];

					foreach ($this->id_group as $row_id_index=>$row_id_value)
					{
						$order .= ',:id_'.$row_id_index;
						$parameters['bind_param'][':id_'.$row_id_index] = $row_id_value;
					}
					$order .= ')'; 
					$sql .= ' ORDER BY '.$order;
				}
			}
		}
		if (!empty($parameters['limit']))
		{
			$sql .= ' LIMIT '.$parameters['limit'];
		}
		if (!empty($parameters['offset']))
		{
			$sql .= ' OFFSET '.$parameters['offset'];
		}
		$query = $this->query($sql,$parameters['bind_param']);
		if ($query->errorCode() == '00000')
		{
			$result = $query->fetchAll();
			$this->id_group = array();
			foreach ($result as $row_index=>$row)
			{
				$this->id_group[] = $row[0];
			}
			$this->_initialized = true;
			$this->parameters['page_count'] = ceil(count($this->id_group)/$this->parameters['page_size']);
			return $result;
		}
		else
		{
			$query_errorInfo = $query->errorInfo();
			$this->message[] = 'SQL Error: '.$query_errorInfo[2];
			return false;
		}
	}

	function set_parameters($parameters = array())
	{
		$this->parameters = array_merge($this->parameters, $parameters);
	}

	function render($parameters = array())
	{
		if ($this->_initialized)
		{
			$page_number = $this->parameters['page_number'];
			if (isset($parameters['page_number']))
			{
				$page_number = intval($parameters['page_number']);
				if ($page_number > $this->parameters['page_count']-1) $page_number =  $this->parameters['page_count']-1;
				if ($page_number < 0) $page_number = 0;				
			}
			$page_size = $this->parameters['page_size'];
			if (isset($parameters['page_size']))
			{
				$page_size = intval($parameters['page_size']);
				if ($page_size < 1) $page_size = 1;
			}
			if (!empty($this->id_group))
			{
				$sql = 'SELECT '.implode(',',$this->parameters['table_fields']).' FROM '.$this->parameters['table'];
				$where = $this->parameters['primary_key'].' IN (-1';
				$order = 'FIELD('.$this->parameters['primary_key'];
				$bind_param = array();

				foreach ($this->id_group as $row_id_index=>$row_id_value)
				{
					$where .= ',:id_'.$row_id_index;
					$order .= ',:id_'.$row_id_index;
					$bind_param[':id_'.$row_id_index] = $row_id_value;
				}
				$where .= ')'; 
				$order .= ')'; 
				$sql .= ' WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$page_size.' OFFSET '.$page_number*$page_size;
				$query = $this->query($sql,$bind_param);

				if ($query->errorCode() == '00000')
				{
					$result = $query->fetchAll(PDO::FETCH_ASSOC);
					return $result;
				}
				else
				{
					$query_errorInfo = $query->errorInfo();
					$this->message[] = 'SQL Error: '.$query_errorInfo[2];
					return false;
				}
			}
		}
		else
		{
			$this->message[] = 'Object Error: Cannot render object before it is initialized with get() function';
			return false;			
		}
	}
}

?>