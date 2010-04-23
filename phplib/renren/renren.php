<?php

/**
 *
 *
 * renren php client v0.01
 * (c) 2009 playcrab inc
 */
define("RENREN_BASE", realpath(dirname(__FILE__) ). "/" );
define ("RENREN_DEBUG",TRUE);
require_once RENREN_BASE.'config.php';
class Renren_API_Client {
    var $user; 
    var $added;
    var $api_key;
    var $secret;
    var $errno; 
    var $errmsg;
	
 	//
    function friends_areFriends($uids1, $uids2) { 
        return $this->_call_method('xiaonei.friends.areFriends', array('uids1' => $uids1,
                                                              'uids2' => $uids2
                                                              ));
    }

	//返回已经添加了一个应用的好友的用户Id列表 
    function friends_getAppUsers() {
        return $this->_call_method('xiaonei.friends.getAppUsers', array( ));
    }
	
	//查询当前用户安装某个应用的好友列表，此接口返回全部数据（2008-12-18）
    function friends_getAppFriends() {
        return $this->_call_method('xiaonei.friends.getAppFriends', array( ));
    }
	//得到当前登录用户的好友列表
    function friends_getFriends() {
        return $this->_call_method('xiaonei.friends.getFriends', array( ));
    }
	
	//获取用户发送站外邀请的详细信息（包括发送邀请信数量、星级用户数等）。最多每次只接受100个用户ID
    function invitations_getUserOsInviteCnt($uids) {
        return $this->_call_method('xiaonei.invitations.getUserOsInviteCnt', array('uids'=>$uids ));
    }
	
	//根据站外邀请id得到此次邀请的详细信息（邀请人、邀请时间、被邀请人、注安装app时间等） 
    function invitations_getOsInfo($invite_ids) {
        return $this->_call_method('xiaonei.invitations.getOsInfo', array('invite_ids'=>$invite_ids ));
    }

	
	//
    function users_getLoggedInUser() { 
        return $this->_call_method('xiaonei.users.getLoggedInUser', array( ));
    }
 
  

	//	
    function users_isAppUser() {
        return $this->_call_method('xiaonei.users.isAppUser', array());
    }

	//
    function users_getInfo($uids = null, $fields = null) {
        return $this->_call_method('xiaonei.users.getInfo', array( 'uids'=> ($uids==null?$this->user:$uids),
                                                          'fields'=>$fields));
    }
	
	//
    function notifications_send($to_ids, $notification) {
        return $this->_call_method('xiaonei.notifications.send', array( 'to_ids'=> $to_ids,
                                                          'notification'=>$notification));
    } 
	
	//
    function notifications_sendemail($recipients, $template_id, $body_data) {
        return $this->_call_method('xiaonei.notifications.sendemail', array( 'to_ids'=> $to_ids,
                                                          'notification'=>$notification ,'template_id'=> $template_id, 'body_data' => $body_data));
    } 
	
	//
    function feed_publishTemplatizedAction ($template_id,$title_data=null,$body_data=null) {

        return $this->_call_method('xiaonei.feed.publishTemplatizedAction', array('template_id' => $template_id,
                                                                           'title_data' => $title_data, 
                                                                          'body_data' => $body_data));
    }
	
	 
	function pay_regOrder($order_id, $amount, $desc) {
        return $this->_call_method('xiaonei.pay.regOrder', array('order_id'=>$order_id,'amount'=>$amount,'desc'=>$desc ));
    }
	
	//查询某个用户在一个应用中一次消费是否完成。此接口传入一个有效的参数：订单号(order_id)
    function pay_isCompleted ($order_id) {
        return $this->_call_method('xiaonei.pay.isCompleted', array('order_id'=>$order_id ));
    }
	
	/*
	* 加入签名认证后，应用需要在此请求基础上增加sig参数，具体生成签名的规则为：
    * 将请求中所有参数进行排序，排序为字典序；
    * 将排序好的参数进行转换，去掉"&",例如：k1=v1&k2=v2&k3=v3变为k1=v1k2=v2k3=v3；
    * 在上述转换后的串末尾追加上应用的secret_key；
    * 用MD5算出上述串的MD5值，然后作为sig的值传入请求中。 
	*/
	function _call_method($method, $args) {
        $this->errno = 0;
        $this->errmsg = ''; 
        $url = 'http://api.xiaonei.com/restserver.do';

        $params = array();
        $params['method'] = $method;
        $params['session_key'] = $this->session_key;
        $params['api_key'] = $this->api_key;
        $params['format'] = 'XML';
        $params['v'] = '1.0';	
		$params['call_id'] = time(); 
       

		$str_md5 = '';
        foreach ($args as $k=>$v) {
            if (is_array($v)) {
                $v = join(',' , $v);
            }
            $params[$k] = $v; 
        }
		ksort($params); 
		foreach ($params as $k=>$v) {  
			$str_md5 .= $k . '=' . $v;
        }
        $params['sig'] = md5($str_md5 . $this->secret);

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
	 
		 
        if (function_exists('curl_init')) {
			 
            // Use CURL if installed...
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           // curl_setopt($ch, CURLOPT_USERAGENT, 'Playcrab Renren API PHP Client 0.1 (curl) ' . phpversion());
            $result = curl_exec($ch); 
            curl_close($ch); 
         
        } else {
			 
            // Non-CURL based version...
            $context =
            array('http' =>
                    array('method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
                                    'User-Agent:Playcrab Renren API PHP Client 0.01 (non-curl) '.phpversion()."\r\n".
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

class Renren {

    var $params;
    var $session_key;
    var $api_client;
    var $api_key;
    var $secret;
	var $added;
    var $canvas_url;
	var $mobile_canvas_url;
    var $group_url;
	var $callback_url;
	var $pay_secure;

	var $app_id = 75552;
	

	//
	var $resource_url ;

    function Renren($session_key = null,$user = null) {

        $this->api_key = RenrenConfig::$api_key; //$api_key;
        $this->secret = RenrenConfig::$secret; //$secret;
		$this->api_client = new Renren_API_Client();
        
        $this->canvas_url =  RenrenConfig::$canvas_host.RenrenConfig::$canvas_name;
		$this->mobile_canvas_url =  RenrenConfig::$mobile_canvas_host.RenrenConfig::$mobile_canvas_name;
        $this->group_url = RenrenConfig::$group_url;
		$this->pay_secure  = RenrenConfig::$pay_secure;
        $this->callback_url  = RenrenConfig::$callback_url;
		$this->resource_url  = RenrenConfig::$resource_url;
        $this->app_id  = RenrenConfig::$app_id;
        if($session_key == null){
           $this->get_valid_params();
		    if (isset($this->params['session_key'])){
				$this->session_key = $this->params['session_key'];
			}
			if (isset($this->params['user'])) {
				$this->user = $this->params['user'];
			}
			if (isset($this->params['added'])) {
				$this->added  = $this->params['added'] ? true:false;   
				$this->api_client->added = $this->added;
			}
			
		}	
		else{
			$this->session_key = $session_key;
			if($user!=null){
				$this->user = $user;
			}
		}	
		$this->api_client->user = $this->user;
		$this->api_client->session_key = $this->session_key;
        $this->api_client->api_key = $this->api_key;
        $this->api_client->secret = $this->secret;
        
    }


    

    function generate_sig($params, $namespace = 'xn_sig_') {

        ksort($params);
        $str = '';
        foreach ($params as $k=>$v) {
            if ($v) {
                $str .= $namespace . $k . '=' . $v ;
            }
        }
        return  md5($str. $this->secret);
    }

	// 除去post/get过来的参数前面的xn_sig_，重组为一个数组
    function get_xn_params($params, $namespace = 'xn_sig_') {
        $xn_params = array();
        foreach ($params as $k=>$v) {
            if (substr($k, 0, strlen($namespace)) == $namespace) {
                $xn_params[substr($k, strlen($namespace))] = $this->no_magic_quotes($v);
            }
        }
        return $xn_params;
    }
	
	
	//not supported in renren
    function is_valid_params($params, $namespace = 'xn_sig_') {
		return true;//TODO: to be refined if renren is ready
		if (!isset($params['sig'])) {
			return false;
		} 
        $sig = $params['key'];
        unset($params['key']);

	    //print $sig ."<br>" . $this->generate_sig($params) . "<br>";

        if ($sig != $this->generate_sig($params, $namespace)) {
            return false;
        }
        return true;
    }

    function get_valid_params() {
        $params = $this->get_xn_params($_POST);
		
		if (!$params) {
            $params = $this->get_xn_params($_GET);
        }
		if ($params && $this->is_valid_params($params)) {
			$this->params = $params;
        }
    }

  
	// 跳转
	function redirect($url) {
		if (!$this->in_frame()) {
			// XNML模式的App
			echo '<xn:redirect url="' . $url . '"/>'; 
		} else {
			// Iframe模式的App 
			echo "<script type=\"text/javascript\">\ntop.location.href = \"$url\";\n</script>";
		}
		exit;
    }
 

 
    // 是否在Iframe模式的App
	function in_frame() {
        return isset($this->params['in_iframe']) || isset($_COOKIE['in_frame']);
    }

      

    function no_magic_quotes($val) {
        if (get_magic_quotes_gpc()) {
            return stripslashes($val);
        } else {
            return $val;
        }
    }
}

