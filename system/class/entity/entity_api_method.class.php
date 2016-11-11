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
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Cannot access API Account';
            return false;
        }
        $row = $this->get(['where'=>'`tbl_entity_api_method`.id < 100 OR `tbl_entity_api_method`.id IN (SELECT api_method_id FROM tbl_rel_api_to_api_method WHERE api_id = :api_id)','bind_param'=>[':api_id'=>$parameter['api_id']],'id_group'=>array()]);
        $result = [];
        foreach($row as $record_index=>$record)
        {
            $result[] = ['name'=>$record['name'],'request_uri'=>$record['friendly_uri'],'description'=>$record['description'],'field'=>($record['field']?json_decode($record['field'],true):'None')];
        }
        return $result;
    }

    function insert_account(&$parameter = array())
    {
        if (empty($parameter['account']))
        {
            // TODO: Error Handling, Website uri not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Account Details not provided';
            return false;
        }
        $entity_account = new entity();
        return $entity_account->set($parameter['account']);
    }

    function select_business_by_uri(&$parameter = array())
    {
        if (empty($parameter['uri']))
        {
            // TODO: Error Handling, Website uri not provided
            $parameter['status'] = 'INVALID_REQUEST';
            $parameter['message'] = 'Website uri not provided';
            return false;
        }
        $index_organization_obj = new index_organization();
        if ($index_organization_obj->filter_by_uri($parameter['uri']) === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database search request failed, try again later';
            return false;
        }
        $view_organization_obj = new view_organization($index_organization_obj->id_group);
        $view_organization_obj->fetch_value();
        $parameter['result'] = [];
        if (count($view_organization_obj->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'Cannot find any businesses match the given uri';
        }
        else
        {
            $parameter['status'] = 'OK';
            foreach($view_organization_obj->row as $record_index=>$record)
            {
                $parameter['result'][] = ['name'=>$record['name'],'friendly_url'=>$record['friendly_url'],'website'=>$record['website']];
            }
        }
        return $parameter['result'];
    }
}

?>