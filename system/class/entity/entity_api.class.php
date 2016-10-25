<?php
// Class Object
// Name: entity_api
// Description: api account table, which stores all api user related information

class entity_api extends entity
{
    function set($parameter = array())
    {
        if (isset($parameter['row']))
        {
            foreach ($parameter['row'] as $record_index=>&$record)
            {
                if (isset($record['password']))
                {
                    $record['password'] = hash('sha256',hash('crc32b',$record['password']));
                }
            }
        }
        parent::set($parameter);
    }

    function update($value = array(), $parameter = array())
    {
        if (isset($value['password']))
        {
            $value['password'] = hash('sha256',hash('crc32b',$value['password']));
        }
        parent::update($value, $parameter);
    }

    function authenticate($parameter = array())
    {
        if (empty($parameter['username']) OR empty($parameter['password']))
        {
            // TODO: username and password cannot be empty
            return false;
        }
        $param = array(
            'bind_param' => array(':name'=>$parameter['username'],':password'=>hash('sha256',hash('crc32b',$parameter['password']))),
            'where' => array('`name` = :name OR `alternate_name` = :name','`password` = :password')
        );
        $row = $this->get($param);
        if (empty($this->id_group))
        {
            // TODO: Error, type invalid login
            return false;
        }
        if (count($this->id_group))
        {
            // TODO: Error, Multiple accounts match, should never happen
            return false;
        }
        return $this->id_group[0];
    }
}

?>