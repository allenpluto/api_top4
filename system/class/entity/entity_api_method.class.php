<?php
// Class Object
// Name: entity_api_method
// Description: api method table, list all the api method name, may or may not available to current api user

class entity_api_method extends entity
{
    function __construct($value = null, $parameter = array())
    {
        parent::__construct($value,$parameter);
    }

    function list_available_method($parameter = array())
    {
        if (empty($parameter['api_id']))
        {
            // TODO: Error Handling api account id not provided
            return false;
        }
        $row = $this->get(['where'=>'`tbl_entity_api_method`.id < 100 OR `tbl_entity_api_method`.id IN (SELECT api_method_id FROM tbl_rel_api_to_api_method WHERE api_id = :api_id)','bind_param'=>[':api_id'=>$parameter['api_id']],'id_group'=>array()]);
        $result = [];
        if (empty($parameter['function_name_only']))
        {
            foreach($row as $record_index=>$record)
            {
                $result[] = ['name'=>$record['name'],'request_uri'=>$record['friendly_uri'],'description'=>$record['description'],'field'=>($record['field']?json_decode($record['field'],true):'None')];
            }
        }
        else
        {
            foreach($row as $record_index=>$record)
            {
                $result[] = [$record['friendly_uri']];
            }
        }
        return $result;
    }

    function select_business_by_uri(&$parameter = array())
    {
        if (empty($parameter['uri']))
        {
            // TODO: Error Handling api account id not provided
            $parameter['status'] = 'fail';
            $parameter['message'] = 'Website uri not provided';
            return false;

        }
    }
}

?>