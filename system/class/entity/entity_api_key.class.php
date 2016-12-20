<?php
// Class Object
// Name: entity_api_key
// Description: api key table, stores all api key and ip restrictions

class entity_api_key extends entity
{
    function get($parameter = array())
    {
        $row = parent::get($parameter);
        if (is_array($row))
        {
            foreach($row as $record_index=>&$record)
            {
                if (!empty($record['ip_restriction'])) $record['ip_restriction'] = explode(',',$record['ip_restriction']);
                else $record['ip_restriction'] = array();
            }
            return $row;
        }
        else
        {
            return false;
        }
    }

    function set($parameter = array())
    {
        if (isset($parameter['row']))
        {
            foreach($parameter['row'] as $record_index=>&$record)
            {
                if (isset($record['ip_restriction'])) $record['ip_restriction'] = implode(',',$record['ip_restriction']);
            }
        }
        return parent::set($parameter);

    }

    function update($value = array(), $parameter = array())
    {
        if (isset($value['ip_restriction'])) $value['ip_restriction'] = implode(',',$value['ip_restriction']);
        return parent::update($value,$parameter);

    }
    function get_api_key($parameter = array())
    {
        $row = $this->get($parameter);
        $result_row = array();
        if (is_array($row))
        {
            foreach($row as $record_index=>$record)
            {
                $result_record = array();
                $result_record['name'] = $record['name'];
                $result_record['alternate_name'] = $record['alternate_name'];
                $result_record['ip_restriction'] = $record['ip_restriction'];
                $result_row[] = $result_record;
            }
        }
        return $result_row;
    }

    function generate_api_key($account_id)
    {
        $crc32b = hash('crc32b',2000-$account_id);
        $set_count = strlen($crc32b);
        $random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-$set_count*4);

        $api_key_part = [];
        for($i=0;$i<$set_count;$i++)
        {
            $sub_hash = substr($random_hash,$i*4,4);
            $seq = ord(substr($sub_hash,0,1)) % 3 + 1;
            $sub_hash = substr_replace($sub_hash,substr($crc32b,$i,1),$seq,1);
            $api_key_part[] = $sub_hash;
        }
        $api_key = implode('-',$api_key_part);

        return $api_key;
    }

    function validate_api_key(&$parameter = array())
    {
        $key_part = explode('-',$parameter['api_key']);
        $crc32b_dec = '';
        foreach($key_part as $index=>$sub_hash)
        {
            $crc32b_dec .= substr($sub_hash,ord(substr($sub_hash,0,1)) % 3 + 1,1);
        }
        $get_parameter = array(
            'bind_param' => array(':name'=>$parameter['api_key']),
            'where' => array('`name` = :name')
        );
        $row = $this->get($get_parameter);
        if (empty($row))
        {
            // Error, type invalid api key
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Invalid api key';
            return false;
        }
        foreach($row as $row_id=>$record)
        {
            if (hash('crc32b',2000-$record['account_id']) == $crc32b_dec)
            {
                foreach ($record['ip_restriction'] as $ip_index=>$ip_pattern)
                {
                    $ip_pattern = str_replace('.','\.',$ip_pattern);
                    $ip_pattern = str_replace('*','([0-9a-f]*)',$ip_pattern);
                    $ip_pattern = '/'.$ip_pattern.'/';
                    if (preg_match($ip_pattern,$parameter['remote_ip']))
                    {
                        $parameter['status'] = 'OK';
                        $parameter['message'] = NULL;
                        return $record['account_id'];
                    }
                }
                // Error, requested ip not accepted
                $parameter['status'] = 'REQUEST_DENIED';
                $parameter['message'] = 'Invalid ip address ['.$parameter['remote_ip'].']';
                return false;
            }
            else
            {
                // Error, type invalid api key, key is not generated through genuine method
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Invalid api key, key is not genuine';
                return false;
            }
        }
    }
}

?>