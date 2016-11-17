<?php
// Class Object
// Name: entity_listing
// Description: Listing table, business listings

class entity_listing extends entity
{
    var $parameter = array(
        'table' => '`Listing`'
    );

    function get($parameter = array())
    {
        $get_account_parameter = ['fields' => ['id','username','importID','complementary_info','agree_tou','account_type','signup_as','other_company','other_company_phone','updated','entered']];
        $get_account_parameter = array_merge($get_account_parameter, $parameter);
        $get_account_result = parent::get($get_account_parameter);

        if (count($this->id_group) == 0)
        {
            // TODO: Error Handling, ZERO_RESULTS
            $this->message->notice = 'No account fits the get conditions';
            return false;
        }
//print_r($this);
        $get_contact_parameter = [
            'fields' => ['account_id','first_name','last_name','company','address','address2','city','state','zip','country','latitude','longitude','phone','fax','email','url','updated','entered']
        ];
//print_r($this->id_group);
        $entity_contact_obj = new entity_contact($this->id_group,[]);
//print_r($entity_contact_obj);
        $entity_contact_obj->get($get_contact_parameter);
//print_r($entity_contact_obj);
        $get_profile_parameter = [
            'fields' => ['account_id','nickname','personal_message','friendly_url','credit_points','updated','entered']
        ];
        $entity_profile_obj = new entity_profile($this->id_group,[]);
        $entity_profile_obj->get($get_profile_parameter);

        $result_row = $this->row;
        foreach ($result_row as $row_index=>&$row)
        {
            if (isset($entity_contact_obj->row[$row_index])) $row = array_merge($row,$entity_contact_obj->row[$row_index]);
            if (isset($entity_profile_obj->row[$row_index])) $row = array_merge($row,$entity_profile_obj->row[$row_index]);
        }
        return $result_row;
    }

    function set($parameter = array())
    {
//print_r($parameter);
        $set_listing_parameter = ['fields' => ['title','address','address2','city','state','zip_code','account_id','phone','alternate_phone','mobile_phone','fax','email','url','importID','category','updated','entered'],'row'=>[]];
        if (isset($parameter['row']))
        {
            foreach ($parameter['row'] as $row_index=>&$row)
            {
                $row['updated'] = date('Y-m-d H:i:s');
                $row['entered'] = $row['updated'];
                $set_listing_row = $row;
                if (isset($set_listing_row['password']))
                {
                    $set_listing_row['password'] = md5($set_listing_row['password']);
                }
                else
                {
                    if (!isset($row['id']))
                    {
                        $row['password'] = substr(sha1(openssl_random_pseudo_bytes(20)),-8);
                        $set_listing_row['password'] = md5($row['password']);
                        $set_listing_row['plain_password'] = $row['password'];
                    }
                }
                $set_listing_row['complementary_info'] = md5('http://www.top4.com.au/members/login.php'.$row['username'].$row['password']);
                $set_listing_row['agree_tou'] = 1;
                $set_listing_parameter['row'][] = $set_listing_row;
            }
        }
        $set_listing_parameter = array_merge($parameter, $set_listing_parameter);

//print_r($set_listing_parameter);
        $set_listing_result = parent::set($set_listing_parameter);
        if ($set_listing_result !== FALSE AND isset($parameter['row']))
        {
            foreach($parameter['row'] as $row_index=>&$row)
            {
                foreach($this->row as $result_row_index=>$result_row)
                {
                    if ($row['username'] == $result_row['username'])
                    {
                        $row['account_id'] = $result_row['id'];
                        $row['friendly_url'] = $result_row['id'];
                        if (!empty($row['title'])) $row['friendly_url'] = $row['title'].' '. $row['friendly_url'];
                        $row['friendly_url'] = $this->format->file_name($row['friendly_url']);
                    }
                }
            }
        }


        return $this->row;
    }

    function update($value = array(), $parameter = array())
    {
        if (isset($value['username']) OR isset($value['password']))
        {
            $entity_account = new entity_account($this->id_group);
            $entity_account->get();
            if (count($entity_account->row) == 1)
            {
                $record = end($entity_account->row);
                $record = array_merge($record, $value);
                $value['complementary_info'] = md5('http://www.top4.com.au/members/login.php'.$record['username'].$record['password']);
            }
        }
        if (isset($value['password']))
        {
            $value['password'] = md5($value['password']);
        }
        parent::update($value, $parameter);
    }

    function authenticate($parameter = array())
    {
        if (empty($parameter['username']) OR empty($parameter['password']))
        {
            // TODO: username and password cannot be empty
            $this->message->error = 'Username and password cannot be empty';
            return false;
        }
        $param = array(
            'bind_param' => array(':username'=>$parameter['username'],':password'=>md5($parameter['password'])),
            'where' => array('`username` = :username OR `alternate_name` = :username','`password` = :password')
        );
        $row = $this->get($param);
        if (empty($this->id_group))
        {
            // TODO: Error, Invalid login
            $this->message->notice = 'invalid login';
            return false;
        }
        if (count($this->id_group))
        {
            // TODO: Error, Multiple accounts match, should never happen
            $this->message->warning = 'multiple login matched';
        }
        return end($this->row);
    }
}

?>