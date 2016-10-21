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

    }
}

?>