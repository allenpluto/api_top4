<?php
// Class Object
// Name: entity_account
// Description: account table, stores all user account related information

class entity_account extends entity
{
    var $parameter = array(
        'table' => '`Account`',
        'primary_key' => 'id',
    );

    function __construct($value = null, $parameter = array())
    {
        parent::__construct();

        $dbLocation = 'mysql:dbname=top4_main;host='.DATABASE_HOST;
        $dbUser = DATABASE_USER;
        $dbPass = DATABASE_PASSWORD;
        $db = new PDO($dbLocation, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''));

        $this->_conn = $db;
    }

    function set($parameter = array())
    {
print_r($parameter);
        $set_account_parameter = ['fields' => ['username', 'password','complementary_info','agree_tou','account_type','signup_as','other_company','other_company_phone','updated','entered'],'row'=>[]];
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
                        //$set_account_row['plain_password'] = $row['password'];
                    }
                }
                $set_account_row['complementary_info'] = md5('http://www.top4.com.au/members/login.php'.$row['username'].$row['password']);
                $set_account_row['agree_tou'] = 1;
                $set_account_parameter['row'][] = $set_account_row;
            }
        }
        $set_account_parameter = array_merge($parameter, $set_account_parameter);

print_r($set_account_parameter);
        $set_account_result = parent::set($set_account_parameter);
        if ($set_account_result !== FALSE AND isset($parameter['row']))
        {
            foreach($parameter['row'] as $row_index=>&$row)
            {
                foreach($this->row as $result_row_index=>$result_row)
                {
                    if ($row['username'] == $result_row['username'])
                    {
                        $row['account_id'] = $result_row['id'];
                        $row['friendly_url'] = $result_row['id'];
                        if (!empty($row['nickname'])) $row['friendly_url'] = $row['nickname'].' '. $row['friendly_url'];
                        else $row['friendly_url'] = $row['first_name'].' '.$row['last_name'].' '.$row['friendly_url'];
                        $row['friendly_url'] = $this->format->file_name($row['friendly_url']);
                    }
                }
            }
echo 'original parameter after set account:<br>';
print_r($parameter);
print_r($this->row);

            $set_contact_parameter = array(
                'fields' => ['account_id','first_name','last_name','company','address','address2','city','state','zip','country','latitude','longitude','phone','fax','email','url','updated','entered']
            );
            $set_contact_parameter = array_merge($parameter, $set_contact_parameter);
            $entity_contact_obj = new entity_contact();
            $entity_contact_obj->set($set_contact_parameter);


            $set_profile_parameter = array(
                'fields' => ['account_id','nickname','personal_message','friendly_url','credit_points','updated','entered']
            );
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
        parent::update($value, $parameter);
    }

    function authenticate($parameter = array())
    {
        if (empty($parameter['username']) OR empty($parameter['password']))
        {
            // TODO: username and password cannot be empty
            $this->message->notice = 'Username and password cannot be empty';
            return false;
        }
        $param = array(
            'bind_param' => array(':username'=>$parameter['username'],':password'=>md5($parameter['password'])),
            'where' => array('`username` = :username OR `alternate_name` = :name','`password` = :password')
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