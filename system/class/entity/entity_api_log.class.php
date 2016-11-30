<?php
// Class Object
// Name: entity_api
// Description: api log table, records api requests and api account events

class entity_api_log extends entity
{
    function set_log($parameter = array())
    {
        $set_parameter = [
            'row'=>[$parameter],
            'table_fields'=>array_keys($parameter)
        ];
        $this->set($set_parameter);
        if (empty($this->row))
        {
            // Error Handling, Failed to generate log
            $this->message->error = 'Failed to generate log';
            return false;
        }
        return end($this->row);

    }
}

?>