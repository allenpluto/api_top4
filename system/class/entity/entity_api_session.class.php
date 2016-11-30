<?php
// Class Object
// Name: entity_api
// Description: api account table, which stores all api user related information

class entity_api_session extends entity
{
    function generate_api_session_id($parameter = array())
    {
        $crc32b = hash('crc32b',2000-$parameter['account_id']);
        $set_count = strlen($crc32b);
        $random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-$set_count*4);

        $api_session_id_part = [];
        for($i=0;$i<$set_count;$i++)
        {
            $sub_hash = substr($random_hash,$i*4,4);
            $seq = (ord(substr($sub_hash,0,1)) + 1) % 3 + 1;
            $sub_hash = substr_replace($sub_hash,substr($crc32b,$i,1),$seq,1);
            $api_session_id_part[] = $sub_hash;
        }
        $api_session_id = implode('-',$api_session_id_part);
        $set_parameter = [
            'row'=>[['account_id'=>$parameter['account_id'],'name'=>$api_session_id,'expire_time'=>$parameter['expire_time'],'remote_addr'=>$parameter['remote_addr'],'http_user_agent'=>$parameter['http_user_agent']]],
            'table_fields'=>['account_id','name','expire_time','remote_addr','http_user_agent']
        ];
//print_r($parameter);
        $this->set($set_parameter);
        if (empty($this->row))
        {
            // Error Handling, Failed to generate session
            $this->message->error = 'Failed to generate session';
            return false;
        }
        return end($this->row);
    }
    function validate_api_session_id(&$parameter = array())
    {
        $key_part = explode('-',$parameter['api_session_id']);
        $crc32b_dec = '';
        foreach($key_part as $index=>$sub_hash)
        {
            $crc32b_dec .= substr($sub_hash,(ord(substr($sub_hash,0,1)) + 1) % 3 + 1,1);
        }
        $get_parameter = array(
            'bind_param' => array(':name'=>$parameter['api_session_id']),
            'where' => array('`name` = :name')
        );
        $row = $this->get($get_parameter);
        if (empty($row))
        {
            // Error Handling, invalid api key
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Please login again';
            $this->message->notice = 'Invalid session id '.$parameter['api_session_id'].'.';
            return false;
        }

        $session = end($row);
        if (strtotime($session['expire_time']) < strtotime(gmdate('Y-m-d H:i:s ')))
        {
            // Error Handling, invalid api key
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Session Time Out, please login again';
            $this->message->notice = 'Session expired '.$parameter['api_session_id'].'.';

            // Create Log on session timeout
            $entity_api_log_obj = new entity_api_log();
            $log_record = ['name'=>'Logout','account_id'=>$session['account_id'],'status'=>'OK','message'=>'Session timeout, force close. Request Time ['.gmdate('Y-m-d H:i:s ').strtotime(gmdate('Y-m-d H:i:s ')).'], Session Expired at ['.$session['expire_time'].' '.strtotime($session['expire_time']).']','content'=>$session['name'],'remote_ip'=>$parameter['remote_ip'],'request_uri'=>$_SERVER['REQUEST_URI']];
            $entity_api_obj = new entity_api($session['account_id']);
            if (count($entity_api_obj->row) > 0)
            {
                $log_record['description'] = end($entity_api_obj->row)['name'];
            }
            $entity_api_log_obj->set_log($log_record);

            $this->delete();

            return false;
        }

        if(hash('crc32b',2000-$session['account_id']) != $crc32b_dec)
        {
            // Error Handling, invalid api key
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Please login again';
            $this->message->notice = 'Session id is not genius '.$parameter['api_session_id'].'.';
            return false;
        }
        return $session;
    }
}

?>