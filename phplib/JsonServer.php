<?php
class JsonServerExecption extends Exception
{
};
class JsonServer{

	protected  $_req     = array() ;/*struct req*/
	protected  $_do_auth = false;
	
	protected  $_debug   = true;/* when in debug mod ,result contain the request*/
	protected  $_use_deflate = false;
	
	
	
	static $exist_methods;
	static $help_infos;
	
	/**
	 * 注册处理函数
	 * @param $method
	 * @param $obj
	 * @param $shortm
	 * @return unknown_type
	 */
	static function register($method,&$obj,$shortm){
		self::$exist_methods[$method] = array($obj,$shortm);
	}
	
	/**
	 * 注册controller
	 * @param $name
	 * @return 
	 */
    static function registerController($name){
            require_once CONTROLLER_ROOT."$name.php";
            $c= new $name;
			$cc=new ReflectionClass($name); 
			$ms = $cc->getMethods();
		    foreach($ms as $m){
		    	self::$exist_methods[$m->class.'.'.$m->name] = array($c,$m->name);
		    	self::$help_infos[$m->class.'.'.$m->name] = $m->getDocComment();
		    }
	}
	
	static function getAllMethod()
	{
		return array_keys(self::$exist_methods);
	}
	
	static function getMethodHelp($name)
	{
		$str.= self::$help_infos[$name];
		$str.= "\nparams:\n";
		@$str.=file_get_contents(REQ_DATA_ROOT.$name.'.param');
		$str.="\nresponse:\n";
		@$str.=file_get_contents(REQ_DATA_ROOT.$name.'.resp');
		return $str;
	}

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
	
    /**
	 * @param $m
	 * @param $params
	 * @return unknown_type
	 */
	public  function doRequest($m,&$params)
	{
		$this->_debug = true;
		$this->_req['method']=$m;
		$this->_req['params']=$params;
		return $this->handle();
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
		$this->_req['params']=json_decode($_POST['params'],true);
		
		
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
	protected function _handle(&$req)
	{
		//just add method map here
		$method=$req['method'];
		$mypre=$method;
		if($this->_debug){
		   CrabTools::mydump($req['params'],REQ_DATA_ROOT.$mypre.'.param');
		}

		
		if(isset(self::$exist_methods[$method])){
			$caller=$exist_methods[$method];
			$c=&$caller[0];
			$m=$caller[1];

		}else{
			$caller=explode('.',$method);
			$cn=$caller[0];
			$m=$caller[1];
			$file = CONTROLLER_ROOT."$cn.php";
			if(!file_exists($file )){
				throw new JsonServerExecption( "method $method file not exist:(".CONTROLLER_ROOT."$cn.php)");
			}
			@require_once $file;
			$c=new $cn;
			if(!method_exists($c,$m)){
				throw new JsonServerExecption( "$cn don't has callable method $m");
			}
		}
		$ret=$c->$m($req['params']);
		if($this->_debug){
		  CrabTools::myprint($ret,REQ_DATA_ROOT.$mypre.'.resp');
		}
		$exist_methods[$method]=array($c,$m);
		return $ret;
	}
	
	
	
}
