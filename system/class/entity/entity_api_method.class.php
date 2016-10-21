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
        if (empty($parameter['bind_value'][':api_id']))
        {
            // TODO: Error Handling api account id not provided
            return false;
        }
        $this->get(['where'=>'`tbl_entity_api_method`.id < 100 OR `tbl_entity_api_method`.id IN (SELECT api_method_id FROM tbl_rel_api_to_api_method WHERE api_id = :api_id)']);

    }
}

?>