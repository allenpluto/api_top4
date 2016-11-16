<?php
// Class Object
// Name: entity_api_method
// Description: api method table, list all the api method name, may or may not available to current api user

class entity_api_method extends entity
{
    function __construct($value = null, $parameter = array())
    {
        if (empty($parameter['api_id']))
        {
            // TODO: Error Handling api account id not provided
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Cannot access API Account';
            return false;
        }
        $this->api_id = $parameter['api_id'];
        parent::__construct($value,$parameter);
    }

    // General Public Accessible Functions
    function list_available_method($parameter = array())
    {
        $row = $this->get(['where'=>'`tbl_entity_api_method`.id < 100 OR `tbl_entity_api_method`.id IN (SELECT api_method_id FROM tbl_rel_api_to_api_method WHERE api_id = :api_id)','bind_param'=>[':api_id'=>$parameter['api_id']],'id_group'=>array()]);
        $result = [];
        foreach($row as $record_index=>$record)
        {
            $result[] = ['name'=>$record['name'],'request_uri'=>$record['friendly_uri'],'description'=>$record['description'],'field'=>($record['field']?json_decode($record['field'],true):'None')];
        }
        return $result;
    }

    // Insert Functions
    function insert_account(&$parameter = array())
    {
        $entity_account = new entity_account();
        $account_field_array = ['username','first_name','last_name','company','address','address2','city','state','zip','country','latitude','longitude','phone','fax','email','url','nickname','personal_message'];
        $set_account_parameter = array('row'=>array());

        if (empty($parameter['username']) OR empty($parameter['first_name']) OR empty($parameter['last_name']))
        {
            // TODO: Error Handling, Website uri not provided
            //$parameter['status'] = 'INVALID_REQUEST';
            //$parameter['message'] = 'New Account Details not provided, username, first_name and last_name are mandatory';
            $parameter = ['status'=>'INVALID_REQUEST','message'=>'New Account Details not provided, username, first_name and last_name are mandatory','username'=>$parameter['username'],'first_name'=>$parameter['first_name'],'last_name'=>$parameter['last_name']];
            return false;
        }

        $entity_account_check = new entity_account();
        $entity_account_check_param = array(
            'bind_param' => array(':username'=>$parameter['username']),
            'where' => array('`username` = :username')
        );
        $entity_account_check->get($entity_account_check_param);
        if (count($entity_account_check->row) > 0)
        {
            $parameter = ['status'=>'REQUEST_DENIED','message'=>'Account Exists','username'=>$parameter['username']];
            return false;
        }
        unset($entity_account_check);

        $set_account_row = array();
        foreach($parameter as $parameter_item_index=>$parameter_item)
        {
            if (in_array($parameter_item_index,$account_field_array))
            {
                $set_account_row[$parameter_item_index] = $parameter_item;
            }
        }
        $set_account_row['importID'] = $this->api_id;
        $set_account_parameter['row'][] = $set_account_row;

        $account_insert_result = $entity_account->set($set_account_parameter);
        if ($account_insert_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return '';
        }

        if (count($entity_account->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
        }
        else
        {
            $record = end($entity_account->row);
            $parameter = ['status'=>'OK','result'=>['token'=>$record['complementary_info'],'username'=>$record['username'],'password'=>$record['plain_password']]];
        }
        return $parameter['result'];
    }

    function insert_account_multiple(&$parameter = array())
    {
        $entity_account = new entity_account();
        $account_field_array = ['username','first_name','last_name','company','address','address2','city','state','zip','country','latitude','longitude','phone','fax','email','url','nickname','personal_message'];
        $set_account_parameter = array('row'=>array());
        $parameter['result'] = [];

        if (!isset($parameter['row']))
        {
            $parameter = ['row'=>[$parameter]];
        }

        foreach ($parameter['row'] as $parameter_row_index=>$parameter_row)
        {
            if (empty($parameter_row['username']) OR empty($parameter_row['first_name']) OR empty($parameter_row['last_name']))
            {
                // TODO: Error Handling, Website uri not provided
                //$parameter['status'] = 'INVALID_REQUEST';
                //$parameter['message'] = 'New Account Details not provided, username, first_name and last_name are mandatory';
                $parameter['result'][] = ['status'=>'INVALID_REQUEST','message'=>'New Account Details not provided, username, first_name and last_name are mandatory','username'=>$parameter_row['username'],'first_name'=>$parameter_row['first_name'],'last_name'=>$parameter_row['last_name']];
                continue;
            }

            $entity_account_check = new entity_account();
            $entity_account_check_param = array(
                'bind_param' => array(':username'=>$parameter_row['username']),
                'where' => array('`username` = :username')
            );
            $entity_account_check->get($entity_account_check_param);
            if (count($entity_account_check->row) > 0)
            {
                $parameter['result'][] = ['status'=>'REQUEST_DENIED','message'=>'Account Exists','username'=>$parameter_row['username']];
                continue;
            }
            unset($entity_account_check);

            $set_account_row = array();
            foreach($parameter_row as $parameter_item_index=>$parameter_item)
            {
                if (in_array($parameter_item_index,$account_field_array))
                {
                    $set_account_row[$parameter_item_index] = $parameter_item;
                }
            }
            $set_account_row['importID'] = $this->api_id;
            $set_account_parameter['row'][] = $set_account_row;
        }
        $account_insert_result = $entity_account->set($set_account_parameter);
        if ($account_insert_result === FALSE)
        {
            $parameter['status'] = 'SERVER_ERROR';
            $parameter['message'] = 'Database insert request failed, try again later';
            return '';
        }

        if (count($entity_account->row) == 0)
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
        }
        else
        {
            foreach($entity_account->row as $record_index=>$record)
            {
                $parameter['result'][] = ['status'=>'OK','token'=>$record['complementary_info'],'username'=>$record['username'],'password'=>$record['plain_password']];
            }
        }
        $overall_status = array();
        foreach($parameter['result'] as $record_index=>$record)
        {
            if (empty($overall_status[$record['status']])) $overall_status[$record['status']] = 1;
            else $overall_status[$record['status']]++;
        }
        if (empty($overall_status['OK']))
        {
            $parameter['status'] = 'ZERO_RESULTS';
            $parameter['message'] = 'No row inserted';
        }
        else
        {
            $parameter['status'] = 'OK';
            $parameter['message'] = $overall_status['OK'].' row(s) inserted';
        }
        return $parameter['result'];
    }

    // Select Functions
    function select_account_by_username(&$parameter = array())
    {
        $entity_account_check = new entity_account();
        $entity_account_check_param = array(
            'bind_param' => array(':username'=>$parameter['username']),
            'where' => array('`username` = :username')
        );
        $entity_account_check->get($entity_account_check_param);
        if (count($entity_account_check->row) > 0)
        {

        }

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