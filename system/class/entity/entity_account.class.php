<?php
// Class Object
// Name: entity_account
// Description: account table, stores all user account related information

class entity_account extends entity
{
    var $parameter = array(
        'table' => '`Account`',
        'primary_key' => 'id',
        'table_fields' => [
            'id'=>'id',
            'username'=>'username',
            'password'=>'password',
            'importID'=>'importID',
            'complementary_info'=>'complementary_info',
            'agree_tou'=>'agree_tou',
            'account_type'=>'account_type',
            'signup_as'=>'signup_as',
            'other_company'=>'other_company',
            'other_company_phone'=>'other_company_phone',
            'updated'=>'updated',
            'entered'=>'entered'
        ]
    );

    function __construct($value = null, $parameter = array())
    {
        $dbLocation = 'mysql:dbname=top4_main;host='.DATABASE_HOST;
        $dbUser = DATABASE_USER;
        $dbPass = DATABASE_PASSWORD;
        $db = new PDO($dbLocation, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''));

        $this->_conn = $db;

        parent::__construct($value, $parameter);
    }

    function get($parameter = array())
    {
        $get_account_parameter = ['fields' => ['id','username','password','importID','complementary_info','agree_tou','account_type','signup_as','other_company','other_company_phone','updated','entered']];
        $get_account_parameter = array_merge($get_account_parameter, $parameter);
        $get_account_result = parent::get($get_account_parameter);

        if (count($this->id_group) == 0)
        {
            // Error Handling, ZERO_RESULTS
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
        $set_account_parameter = ['fields' => ['username', 'password','importID','complementary_info','agree_tou','account_type','signup_as','other_company','other_company_phone','updated','entered'],'row'=>[]];
        if (isset($parameter['row']))
        {
            foreach ($parameter['row'] as $row_index=>&$row)
            {
                $row['updated'] = date('Y-m-d H:i:s');
                $row['entered'] = $row['updated'];
                $row['credit_points'] = 100;
                $set_account_row = $row;
                if (isset($set_account_row['password']))
                {
                    $set_account_row['password'] = md5($set_account_row['password']);
                }
                else
                {
                    if (!isset($row['id']))
                    {
                        $row['password'] = substr(sha1(openssl_random_pseudo_bytes(20)),-8);
                        $set_account_row['password'] = md5($row['password']);
                        $set_account_row['plain_password'] = $row['password'];
                    }
                }
                $set_account_row['complementary_info'] = md5('http://www.top4.com.au/members/login.php'.$set_account_row['username'].$set_account_row['password']);
                $set_account_row['agree_tou'] = 1;
                $set_account_parameter['row'][] = $set_account_row;
            }
        }
        $set_account_parameter = array_merge($parameter, $set_account_parameter);

//print_r($set_account_parameter);
        $set_account_result = parent::set($set_account_parameter);
        if ($set_account_result !== FALSE AND isset($parameter['row']))
        {
            foreach($parameter['row'] as $row_index=>&$row)
            {
                foreach($this->row as $result_row_index=>&$result_row)
                {
                    if ($row['username'] == $result_row['username'])
                    {
                        $row['account_id'] = $result_row['id'];
                        $row['friendly_url'] = $result_row['id'];
                        if (!empty($row['nickname'])) $row['friendly_url'] = $row['nickname'].' '. $row['friendly_url'];
                        else $row['friendly_url'] = $row['first_name'].' '.$row['last_name'].' '.$row['friendly_url'];
                        $row['friendly_url'] = $this->format->file_name($row['friendly_url']);
                        $result_row['password'] = $row['password'];
                    }
                }
            }
//echo 'original parameter after set account:<br>';
//print_r($parameter);
//print_r($this->row);

            $set_contact_parameter = [
                'fields' => ['account_id','first_name','last_name','company','address','address2','city','state','zip','country','latitude','longitude','phone','fax','email','url','updated','entered']
            ];
            $set_contact_parameter = array_merge($parameter, $set_contact_parameter);
            $entity_contact_obj = new entity_contact();
            $entity_contact_obj->set($set_contact_parameter);


            $set_profile_parameter = [
                'fields' => ['account_id','nickname','personal_message','friendly_url','credit_points','updated','entered']
            ];
            $set_profile_parameter = array_merge($parameter, $set_profile_parameter);
            $entity_profile_obj = new entity_profile();
            $entity_profile_obj->set($set_profile_parameter);
        }


        return $this->row;
    }

    function update($value = array(), $parameter = array())
    {
        if (isset($value['password']))
        {
            $value['password'] = md5($value['password']);
        }
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
        return parent::update($value, $parameter);
    }

    function authenticate($parameter = array())
    {
        if (empty($parameter['username']) OR empty($parameter['password']))
        {
            // username and password cannot be empty
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
            // Error, Invalid login
            $this->message->notice = 'invalid login';
            return false;
        }
        if (count($this->id_group))
        {
            // Error, Multiple accounts match, should never happen
            $this->message->warning = 'multiple login matched';
        }
        return end($this->row);
    }
}

?>