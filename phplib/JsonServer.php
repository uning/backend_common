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
	 * 
	 * @param $method
	 * @param $obj
	 * @param $shortm
	 * @return unknown_type
	 */
	static function register($method,&$obj,$shortm){
		self::$exist_methods[$method] = array($obj,$shortm);
	}
	
	/**
	 * controller
	 * @param $name
	 * @return 
	 */
    static function registerController($name){
            require_once CONTROLLER_ROOT."$name.php";
            $c= new $name;
			$cc=new ReflectionClass($name); 
			$ms = $cc->getMethods();
		    foreach($ms as $m){
		    	if($m->isPublic()){
		    	  self::$exist_methods[$m->class.'.'.$m->name] = array($c,$m->name);
		    	  self::$help_infos[$m->class.'.'.$m->name] = $m->getDocComment();
		    	}
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
		@$str.=file_get_contents(REQ_DATA_ROOT.$name.'.param.read');
		$str.="response:\n";
		@$str.=file_get_contents(REQ_DATA_ROOT.$name.'.resp');
		return $str;
	}

	/**
	 * 
	 */
	protected function auth($key)
	{
		if($this->_do_auth==false || $this->_debug )
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
		$this->_req['m']=$m;
		$this->_req['p']=$params;
		return $this->_handle($this->_req);
	}


	/*
	 *http post
	 * 
	 * method
	 * auth, 
	 * params,½
	 */
	public function getRequest()
	{
		if($this->_req)
			return $this->_req;
	
		

		$jsonstr = file_get_contents('php://input');
		$this->_req = json_decode($jsonstr,true);
		if(!isset($this->_req['m'])){
		  throw new JsonServerExecption( ' no method get:'.$jsonstr);
        }		
		
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
	 * 
	 *
	 */
	public function handle($req=null)
	{
	    try{
		  if(!$req)
			$req= & $this->getRequest();
		  if(!$req){
		    $r['s']='KO';
			$r['msg']='null request';
		  }
		  else if(!$this->auth($req['k'])){
		    $r['s']='KO';
			$r['msg']='auth failed';
		  }
		  else{
		  $r=$this->_handle($req);
		 }
		}catch (Exception $e){
	       $ret['s']='KO';
	       $ret['msg']=$e->getMessage();
        }
		
	    if($this->_debug)
		  $r['request'] = $req;
		$r['k'] = "kkkk";//todo:add generate logic
		if($this->_use_deflate) 
		   return gzdeflate(json_encode($r));
		return json_encode($r);
	}


	/*
	 * controller,we don't make more check
	 * the controller must has the class name save as file name
	 *
	 */
	protected function _handle(&$req)
	{
		//just add method map here
		$method=$req['m'];
		$mypre=$method;
		if($this->_debug){
		   CrabTools::mydump($req['p'],REQ_DATA_ROOT.$mypre.'.param');
		}

		
		if(isset(self::$exist_methods[$method])){
			$caller= &self::$exist_methods[$method];
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
		$ret=$c->$m($req['p']);
		if($this->_debug){
		  CrabTools::myprint($ret,REQ_DATA_ROOT.$mypre.'.resp');
		}
		self::$exist_methods[$method][0]=&$c;
		self::$exist_methods[$method][1]=&$m;
		return $ret;
	}
}
