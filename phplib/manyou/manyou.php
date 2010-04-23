<?php
/**
 *
 *
 * Manyou_Platform_v0.2
 * (c) 2008 comsenz inc
 * $Id: Manyou.php 18477 2008-12-25 06:17:26Z chenhao $
 */

class Manyou_API_Client {
    var $user;
    var $friends;
    var $added;
    var $api_key;
    var $secret;
    var $errno;
    var $errmsg;

    function profile_setMYML($myml, $uid = 0) {
        return $this->_call_method('profile.setMYML', array('myml'=> $myml, 'uid' => $uid));
    }

    function profile_getMYML($uid = 0) {
        return $this->_call_method('profile.getMYML', array('uid' => $uid));
    }

    function friend_get() {
        if (isset($this->friends)) {
            return $this->friends;
        }
        return $this->_call_method('friend.get', array());
    }

    function friend_areFriends($uid1, $uid2) {
        //if ($uid1 == $this->user_getLoggedInUser();
        return $this->_call_method('friend.areFriends', array('uid1' => $uid1,
                                                              'uid2' => $uid2
                                                              ));
    }

    function friend_getAppUsers() {
        return $this->_call_method('friend.getAppUsers', array());
    }

    function user_getLoggedInUser() {
        if (isset($this->user)) {
            return $this->user;
        }
        return $this->_call_method('user.getLoggedInUser', array());
    }

	function user_getLoggedInUserLevel() {
		if (isset($this->userLevel)) {
            return $this->userLevel;
        }
        return $this->_call_method('user.getLoggedInUserLevel', array());
    }

	function user_getInvitationURL($sid) {
		return $this->_call_method('user.getInvitationURL', array('sid'=> $sid));
	}

    function user_isAppAdded() {
        if (isset($this->added)) {
            return $this->added;
        }
        return $this->_call_method('user.isAppAdded', array());
    }

    function user_getinfo($uids, $fields = null) {
        return $this->_call_method('user.getinfo', array( 'uids'=> $uids,
                                                          'fields'=>$fields));
    }

    function notification_send($uids, $msg) {
        return $this->_call_method('notification.send', array( 'uids'=> $uids,
                                                          'msg'=>$msg));
    }

    function notification_get() {
        return $this->_call_method('notification.get', array());
    }

    function feed_publishTemplatizedAction($title_template,$title_data,$body_template,$body_data,$body_general,$image_1,$image_1_link,$image_2,$image_2_link,$image_3,$image_3_link,$image_4,$image_4_link,$target_ids = null) {

        return $this->_call_method('feed.publishTemplatizedAction', array('title_template' => $title_template,
                                                                           'title_data' => $title_data,
                                                                           'body_template' => $body_template,
                                                                          'body_data' => $body_data,
                                                                          'body_general' => $body_general,
                                                                          'image_1' => $image_1,
                                                                          'image_1_link' => $image_1_link,
                                                                          'image_2' => $image_2,
                                                                          'image_2_link' => $image_2_link,
                                                                          'image_3' => $image_3,
                                                                          'image_3_link' => $image_3_link,
                                                                          'image_4' => $image_4,
                                                                          'image_4_link' => $image_4_link,
                                                                          'target_ids' => $target_ids));
    }

	function site_get($sid) {
        return $this->_call_method('site.get', array('sid'=> $sid));
    }

	function _call_method($method, $args) {
        $this->errno = 0;
        $this->errmsg = '';

        $url = 'http://api.manyou.com/openapi.php';

        $params = array();
        $params['method'] = $method;
        $params['session_key'] = $this->session_key;
        $params['api_key'] = $this->api_key;
        $params['format'] = 'PHP';
        $params['v'] = '0.1';
        //$params['secret'] = $this->secret;

        ksort($params);
        $str = '';
        foreach ($params as $k=>$v) {
            $str .= $k . '=' . $v . '&';
        }

        ksort($args);
        foreach ($args as $k=>$v) {
            if (is_array($v)) {
                $v = join(',' , $v);
            }
            $params['args'][$k] = $v;
            $k = 'args[' . $k . ']';
            $str .= $k .'=' . $v . '&';
        }
        $params['sig'] = md5($str . $this->secret);

		list($errno, $result) = $this->post_request($url, $params);

        if (!$errno) {
			$result = unserialize($result);
            if (isset($result['errCode']) && $result['errCode'] != 0) {
                $this->errno = $result['errCode'];
                $this->errmsg = $result['errMessage'];
                // TODO handle error
                return null;
            }
            return $result['result'];
        } else {
			return false;
        }
    }

    function post_request($url, $params) {

        $str = '';

        foreach ($params as $k=>$v) {
            if (is_array($v)) {
                foreach ($v as $kv => $vv) {
                    $str .= '&' . $k . '[' . $kv  . ']=' . urlencode($vv);
                }
            } else {
                $str .= '&' . $k . '=' . urlencode($v);
            }
        }

        if (function_exists('curl_init')) {
            // Use CURL if installed...
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Manyou API PHP Client 0.1 (curl) ' . phpversion());
            $result = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            return array($errno, $result);
        } else {
            // Non-CURL based version...
            $context =
            array('http' =>
                    array('method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
                                    'User-Agent: Manyou API PHP Client 0.1 (non-curl) '.phpversion()."\r\n".
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
        return array(0, $result);
    }
}

class Manyou {

    var $params;
    var $session_key;
    var $api_client;
    var $api_key;
    var $secret;

    function Manyou($api_key, $secret) {

        $this->api_key = $api_key;
        $this->secret = $secret;

        $this->get_valid_params();

	    $this->session_key = $this->params['sessionId'];

        $this->api_client = new Manyou_API_Client();

        $this->api_client->api_key = $api_key;
        $this->api_client->secret = $secret;
        $this->api_client->session_key = $this->session_key;

        if (isset($this->params['friends']) && trim($this->params['friends'])) {
            $this->api_client->friends = explode(',' , $this->params['friends']);
        }
        if (isset($this->params['added'])) {
            $this->api_client->added = $this->params['added'] ? true : false;
        }
        if (isset($this->params['uId'])) {
            $this->api_client->user = $this->params['uId'];
        }

		 if (isset($this->params['uLevel'])) {
            $this->api_client->userLevel = $this->params['uLevel'];
        }
    }

    function generate_sig($params, $namespace = 'my_sig_') {

        ksort($params);
        $str = '';
        foreach ($params as $k=>$v) {
            if ($v) {
                $str .= $namespace . $k . '=' . $v . '&';
            }
        }
        return  md5($str. $this->secret);
    }

	// 除去post/get过来的参数前面的my_sig_，重组为一个数组
    function get_my_params($params, $namespace = 'my_sig_') {
        $my_params = array();
        foreach ($params as $k=>$v) {
            if (substr($k, 0, strlen($namespace)) == $namespace) {
                $my_params[substr($k, strlen($namespace))] = $this->no_magic_quotes($v);
            }
        }
        return $my_params;
    }

    function is_valid_params($params, $namespace = 'my_sig_') {
		if (!isset($params['key'])) {
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
        $params = $this->get_my_params($_POST);

		if (!$params) {
            $params = $this->get_my_params($_GET);

            if (!$params) {
                $params = $this->get_my_params($_COOKIE, $this->api_key . '_');
                foreach ($params as $k => $v) {
                    if (!in_array($k, array('uId', 'sessionId', 'sId', 'key'))) {
                        unset($params[$k]);
                    }
                }
                if ($this->is_valid_params($params, $this->api_key . '_')) {
                    $this->params = $params;
                } else {
                    return ;
                }
            } else if ($this->is_valid_params($params)) {
                $this->set_cookies($params, 3600 * 2);
                $this->params = $params;
            }
        } else if ($this->is_valid_params($params)) {
			$this->params = $params;
        }
    }

    function set_cookies($params, $expires = 3600) {
		//var_dump($params);

		header('P3P: CP="NOI DEV PSA PSD IVA PVD OTP OUR OTR IND OTC"');
	    $cookies = array();
        $cookies[$this->api_key . '_' . 'uId'] = $params['uId'];
		$cookies[$this->api_key . '_' . 'uLevel'] = $params['uLevel'];
        $cookies[$this->api_key . '_' . 'sId'] = $params['sId'];
        $cookies[$this->api_key . '_' . 'sessionId'] = $params['sessionId'];

		$expireTime = time() + (int)$expires;

        foreach ($cookies as $name => $val) {
            setcookie($name, $val, $expireTime);
            $_COOKIE[$name] = $val;
        }
        $sig = $this->generate_sig($cookies, '');
        setcookie($this->api_key . '_key', $sig, $expireTime);
        $_COOKIE[$this->api_key . '_key'] = $sig;

		// 保存当前站点URL、AppId等信息到Cookie中，供Iframe方式的App使用
		setcookie('prifixUrl', $params['prefix'], $expireTime);
		setcookie('appId', $params['appId'], $expireTime);
		setcookie('added', $params['added'], $expireTime);

		if (isset($params['in_iframe'])) {
			setcookie('inFrame', $params['in_iframe'], $expireTime);
		}
    }

	// 跳转
	function redirect($url) {
		if ($this->in_my_canvas()) {
			// MYML模式的App
			echo '<my:redirect url="' . $url . '"/>';
		} else if (strrpos($url, $this->get_site_url()) === false) {
			// Iframe模式的App，但$url不在当前UCH站点
			header('Location: ' . $url);
		} else {
			// Iframe模式的App，但要转向的$url仍然在当前UCH站点中
			echo "<script type=\"text/javascript\">\ntop.location.href = \"$url\";\n</script>";
		}
		exit;
    }

	// 返回当前已经登录用户Id
	function get_loggedin_user() {
		if (isset($this->params['uId'])) {
			return $this->params['uId'];
		} else if(!empty($_COOKIE[$this->api_key . '_uId'])) {
			return $_COOKIE[$this->api_key . '_uId'];
		} else {
			return false;
		}
	}

	// 返回当前已经登录用户的管理权限
	function get_loggedin_user_level() {
		if (isset($this->params['uLevel'])) {
			return $this->params['uLevel'];
		} else if(!empty($_COOKIE[$this->api_key . '_uLevel'])) {
			return $_COOKIE[$this->api_key . '_uLevel'];
		} else {
			return false;
		}
	}

    // 是否在MYML模式的App的canvas页面
	function in_my_canvas() {
        return isset($this->params['in_canvas']);
    }

    // 是否在Iframe模式的App
	function in_frame() {
        return isset($this->params['in_iframe']) || isset($_COOKIE['inFrame']);
    }

    // 要求登录
	function require_login() {
		if (!$this->get_loggedin_user()) {
			$this->redirect($this->get_login_url());
		}
    }

    // 要求已添加该应用
	function require_add() {
        if (!$this->added()) {
			$this->redirect($this->get_add_url());
		}
    }

	// 要求必须在Iframe中
	function require_frame() {
		if (!$this->in_frame()) {
			$this->redirect($this->get_login_url());
		}
	}

	// 返回登录地址
	function get_login_url() {
		$url = $this->get_site_url() . 'do.php?ac=login&refer=userapp.php?id=' . $this->current_app();
		return $url;
	}

	// 返回添加应用的地址
	function get_add_url() {
		// 如果当前用户是通过站外邀请而来
		if (isset($this->params['invitedby_bi'])) {
			$url = $this->get_site_url() . 'cp.php?ac=userapp&appid=' . $this->current_app() .'&my_extra=' . $this->params['invitedby_bi'];
		} else { // 普通用户
			$url = $this->get_site_url() . 'cp.php?ac=userapp&appid=' . $this->current_app();
		}

		return $url;
	}

	// 对要跳转的地址进行封装
    function get_url($suffix) {
        return $this->get_site_url() . 'userapp.php?id=' . $this->current_app() . '&my_suffix=' . urlencode(base64_encode($suffix));
    }

	// 返回当前站点URL地址
	function get_site_url() {
		if (isset($this->params['prefix'])) {
			return $this->params['prefix'];
		} else if(!empty($_COOKIE['prifixUrl'])) {
			return $_COOKIE['prifixUrl'];
		}
	}

	// 返回当前站点Id
    function current_site() {
		if (isset($this->params['sId'])) {
			return $this->params['sId'];
		} else if(!empty($_COOKIE[$this->api_key . '_sId'])) {
			return $_COOKIE[$this->api_key . '_sId'];
		}
    }

	// 返回当前App Id
	function current_app() {
		if (isset($this->params['appId'])) {
			return $this->params['appId'];
		} else if(!empty($_COOKIE['appId'])) {
			return $_COOKIE['appId'];
		}
	}

	// 用户是否已经添加应用
	function added() {
		if (isset($this->params['added']) && $this->params['added'] == '1') {
			return true;
		} else if(isset($_COOKIE['added']) && $_COOKIE['added'] == '1') {
			return true;
		} else {
			return false;
		}
	}

	// 站外邀请，获得邀请者的漫游uid
	function get_outsite_inviter() {
		if (isset($this->params['invitedby_ai']) && intval($this->params['invitedby_ai']) > 0
			&& $this->is_installation()) {
			return $this->params['invitedby_ai'];
		} else {
			return false;
		}
	}

	//TODO 站内邀请，获得邀请者的漫游uid
	function get_insite_inviter() {
	}

	// 当前用户是否刚刚安装应用（第一次访问）
	function is_installation() {
		if (isset($this->params['installed']) && $this->params['installed'] == 1) {
			return true;
		} else {
			return false;
		}
	}

	// 当前用户是否刚刚卸载了应用
	function is_uninstallation() {
		if (isset($this->params['uninstalled']) && $this->params['uninstalled'] == 1) {
			return true;
		} else {
			return false;
		}
	}

    function no_magic_quotes($val) {
        if (get_magic_quotes_gpc()) {
            return stripslashes($val);
        } else {
            return $val;
        }
    }
}
?>