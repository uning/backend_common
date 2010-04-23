<?php

/**
 *
 *
 * vk php client v0.01
 * (c) 2009 playcrab inc
 */
define("VK_BASE", realpath(dirname(__FILE__) ). "/" );
define ("VK_DEBUG",TRUE);
require_once VK_BASE.'config.php';
class Vk_API_Client { 
	var $api_id;
	var $private_key;  
	var $secure_key;
	var $viewer_id;
        var $test_mode;
	//users method
	function isAppUser ($uid){
		return $this->_call_method('isAppUser', array('uid' => $uid
					));
	} 
           
	function getProfiles ($uids, $fields = "uid,first_name,last_name,nickname,sex,bdate,city,country,timezone,photo,photo_medium,photo_big"){
		return $this->_call_method('getProfiles', array('uids' => $uids,
					'fields' => $fields
					));
	} 

	function getFriends (){
		return $this->_call_method('getFriends', array());
	} 
	function getAppFriends (){
		return $this->_call_method('getAppFriends', array());
	} 
	function getUserBalance (){
		return $this->_call_method('getUserBalance', array());
	} 
	//end of users method




	//
	function secure_sendNotification($uids, $message) { 
		return $this->_call_method('secure.sendNotification', array('uids' => $uids,
					'message' => $message
					), true);
	}

	function secure_saveAppStatus($uid, $status) { 
		return $this->_call_method('secure.saveAppStatus', array('uid' => $uid,
					'status' => $status
					), true);
	}


	function secure_getAppBalance() { 
		return $this->_call_method('secure.getAppBalance', array(), true);
	}


	function secure_getBalance($uid) { 
		return $this->_call_method('secure.getBalance', array('uid' => $uid), true);
	}


	function secure_addVotes ($uid, $votes) { 
		return $this->_call_method('secure.addVotes', array('uid' => $uid,'votes'=>$votes), true);
	}



	function secure_withdrawVotes($uid, $votes) { 
		return $this->_call_method('secure.withdrawVotes', array('uid' => $uid,'votes'=>$votes), true);
	}

	function secure_transferVotes($uid_from, $uid_to, $votes) { 
		return $this->_call_method('secure.transferVotes', array('uid_from' => $uid_from, 'uid_to'=>$uid_to, 'votes'=>$votes), true);
	}

	function secure_getTransactionsHistory ($type = 0, $uid_from = null, $uid_to = null, $date_from = null, $date_to = null, $limit = 1000){ 
		return $this->_call_method('secure.getTransactionsHistory', array('type' => $type,'uid_from'=>$uid_from,'uid_to'=>$uid_to,'date_from'=>$date_from,'date_to'=>$date_to,'limit'=>$limit), true);
	}



	function _call_method($method, $args, $secure = false) {
		$this->errno = 0;
		$this->errmsg = ''; 
		$url = 'http://api.vkontakte.ru/api.php';

		$params = array();
		$params['method'] = $method; 
		$params['api_id'] = $this->api_id;
		$params['format'] = 'XML';
		$params['timestamp']=time(); 
		$params['v'] = '2.0';	
		$params['random'] = rand(); 
		if($this->test_mode ==  1)
		$params['test_mode'] = $this->test_mode;



		foreach ($args as $k=>$v) {
			if (is_array($v)) {
				$v = join(',' , $v);
			}
			$params[$k] = $v; 
		}

		$str_md5 = '';
		ksort($params);  
		foreach ($params as $k=>$v) {  
			if($v!=null && $v!="")
				$str_md5 .= $k . '=' . $v;
			else
				unset($params[$k]);
		}
		if($secure){
			$secret = $this->secure_key;
		}else{
			$secret = $this->private_key;
		}

		$md5 = $this->viewer_id.$str_md5.$secret;
		$params['sig'] = md5($md5);

		$result = $this->post_request($url, $params);  
		return $result;

	}

	private function xml_to_array($xml)
	{
		$array = (array)(simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA));
		foreach ($array as $key=>$item){
			$array[$key]  = $this->struct_to_array((array)$item);
		}
		return $array;
	}

	private	function struct_to_array($item) {
		if(!is_string($item)) {
			$item = (array)$item;
			foreach ($item as $key=>$val){
				$item[$key]  =  self::struct_to_array($val);
			}
		}
		return $item;
	}

	private function checkreturn($result)
	{	
		$msg='';
		if($result['error_code'])
		{
			$msg.='<br>访问出错!<br>';
			if($result['error_code'][0])
			{
				$msg.='错误编号:'.$result['error_code'][0].'<br>';
			}
			if($result['error_msg'][0])
			{
				$msg.='错误信息:'.$result['error_msg'][0].'<br>';
			}

		}

		if($msg!='' && $result['error_code'][0]!='10702' && $result['error_code'][0]!='10600' ){echo $msg;exit;}


	}


	function post_request($url, $params) { 
		$str = ''; 
		foreach ($params as $k=>$v) {
			if (is_array($v)) {
				$s = '';
				foreach ($v as $kv => $vv) {
					$s = join(',' , urlencode($vv) ); 
				}
				$str .= '&' . $k . '=' . urlencode($s);
			}else{
				$str .= '&' . $k . '=' . urlencode($v);
			}	

		}
		if(strlen($str)>0){
			$str = substr($str,1,strlen($str)-1);
		} 

		echo $url."?".$str;
		if (function_exists('curl_init')) {
			// Use CURL if installed...
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// curl_setopt($ch, CURLOPT_USERAGENT, 'Playcrab VK API PHP Client 0.1 (curl) ' . phpversion());
			$result = curl_exec($ch); 
			curl_close($ch); 

		} else {

			// Non-CURL based version...
			$context =
				array('http' =>
						array('method' => 'POST',
							'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
							'User-Agent:Playcrab VK API PHP Client 0.01 (non-curl) '.phpversion()."\r\n".
							'Content-length: ' . strlen($str),
							'content' => $str));
			$contextid = stream_context_create($context);
			$sock = fopen($url, 'r', false, $contextid);
			if ($sock) {
				$result = '';
				while (!feof($sock)) {
					$result .= fgets($sock, 4096);
				}
				fclose($sock);
			}
		}
		$result = $this->xml_to_array($result);
		//	$this->checkreturn($result);
		return $result;

	}
}

class Vk{

	var $api_client;
	var $api_id;

	var $private_key;
	var $secure_key;
        var $test_mode;

	var $viewer_id;
	function Vk($viewer_id = null) {
		$this->api_id = VkConfig::$api_id;  
		$this->secure_key = VkConfig::$secure_key;  
		$this->private_key = VkConfig::$private_key;  
                $this->test_mode = VkConfig::$test_mode;
		$this->api_client = new Vk_API_Client();
		$this->viewer_id = $viewer_id;	  
		$this->api_client->viewer_id = $this->viewer_id;
		$this->api_client->api_id = $this->api_id;
		$this->api_client->private_key = $this->private_key;
		$this->api_client->secure_key = $this->secure_key;
                $this->api_client->test_mode = $this->test_mode;
	}
    
    public function auth($auth_key, $viewer_id = null){
        $vid = $viewer_id ; 
        if(!$vid)$vid = $this->viewer_id;
        $key = md5($this->api_id . '_' . $vid . '_' . $this->secure_key);
        if($auth_key == $key){
            return true;
        }
        return  false;
    
    }


}
?>
