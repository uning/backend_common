<?php
class JsonServerExecption extends Exception
{
};
class JsonServer{

	protected  $_req     = array() ;/*struct req*/
	protected  $_do_auth = false;
	protected  $_debug   = true;/* when in debug mod ,result contain the request*/
	protected  $_use_deflate = false;

	/**
	 * 验证，先使用不变的session
	 */
	protected function auth($key,$auth)
	{
		if($this->_do_auth==false)
			return true;
		static $secret='playcrab';
		return md5($key.$secret)==$auth;
	}
	
	/*
	*/
	public function JsonServer()
	{
	}


	/*
	 * 获取请求信息,使用http post
	 * 合法post req 包含
	 * method，classnaem.mfunction
	 * auth, key,验证用字段
	 * params,调用服务器端方法的参数
	 */
	public function getRequest()
	{
		if($this->_req)
			return $this->_req;
		$this->_req['method']=$_POST['method'];
		$this->_req['params']=json_decode($_POST['params']);
		
		
		//*

		//$jsonstr = file_get_contents('php://input');
		//$r=json_decode($jsonstr,true);//返回数组
		if(function_exists('json_last_error')){
			switch(json_last_error()){
			case JSON_ERROR_DEPTH:
				throw new JsonServerExecption( ' - Maximum stack depth exceeded');
				break;
			case JSON_ERROR_CTRL_CHAR:
				throw new JsonServerExecption( ' - Unexpected control character found');
				break;
			case JSON_ERROR_SYNTAX:
				throw new JsonServerExecption( ' - Syntax error, malformed JSON');
				break;
			case JSON_ERROR_NONE:
				break;
			}
		}
		return $this->_req;
		//*/
	}



	/*
	 * 执行
	 *
	 */
	public function handle($req=null)
	{
		if(!$req)
			$req=$this->getRequest();
		if(!$this->auth($req['key'],$req['auth']))
			throw new JsonServerExecption( "auth failed ");
		$r=$this->_handle($req);
		if($this->_debug)
		  $r['request'] = $req;
		if($this->_use_deflate) 
		   return gzdeflate(json_encode($r));
		return json_encode($r);
	}


	/*
	 * 获取controller,we don't make more check
	 * the controller must has the class name save as file name
	 *
	 */
	protected function _handle($req)
	{
		//just add method map here
		static $exist_methods=array(
			'TestJson.echo'=>array('TestJson','sendback'),
		);
		$method=$req['method'];
		$mypre=$method;
		if($this->_debug){
		   CrabTools::mydump($req['params'],REQ_DATA_ROOT.$mypre.'.param');
		   CrabTools::myprint($req['params'],REQ_DATA_ROOT.$mypre.'.param.read');
		}

		if(isset($exist_methods[$method])){
			$caller=$exist_methods[$method];
			$cn=$caller[0];
			$m=$caller[1];
			require_once CONTROLLER_ROOT."$cn.php";
			$c=new $cn;

		}else{
			$caller=explode('.',$method);
			$cn=$caller[0];
			$m=$caller[1];
			if(!file_exists(CONTROLLER_ROOT."$cn.php")){
				throw new JsonServerExecption( "method $method file not exist:(".CONTROLLER_ROOT."$cn.php)");
			}
			require_once CONTROLLER_ROOT."$cn.php";
			$c=new $cn;
			if(!method_exists($c,$m)){
				throw new JsonServerExecption( "$cn don't has callable method $m");
			}
		}
		$ret=$c->$m($req['params']);
		if($this->_debug){
		 CrabTools::myprint($ret,REQ_DATA_ROOT.$mypre.'.resp');
		}
		$exist_methods[$method]=array($cn,$m);
		return $ret;
	}
}
