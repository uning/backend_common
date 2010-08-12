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

	static function getMethodLastCallParam($name)
	{
		@$str=unserialize(file_get_contents(REQ_DATA_ROOT.$name.'.param'));
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
		if(!$this->_req||!isset($this->_req['m'])){
			throw new JsonServerExecption( 'params error:'.$jsonstr);
		}		
		return $this->_req;
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
				$r['s']='norequest';
				return  json_encode($r);
			}
			if(!$this->auth($req['k'])){
				$r['s']='auth';
				return  json_encode($r);
			}
			$r=$this->_handle($req);
				
				
		}catch (Exception $e){
			$r['s']='exc';
			$r['msg']=$e->getMessage();
			$r['exce']=$e->getTrace();
			return  json_encode($r);
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
		$log_method=array(
				'Achieve.get'=>1,
				'Achieve.finish'=>1,
				'Advert.buy'=>1,
				'Advert.set'=>1,
				'Cinema.enter'=>1,
				'Cinema.pick'=>1,
				'Gift.send'=>1,
				'Gift.accept'=>1,
				'Man.update'=>1,
				'UserController.login'=>1,
				'UserController.precheckout'=>1,
				'UserController.update_friends'=>1,
				'UserController.enlarge_mall'=>1,
				'ItemController.buy'=>1,
				'ItemController.sale'=>1,
				'CarController.buy'=>1,
				'CarController.sale'=>1,
				'CarController.go_goods'=>1,
				'CarController.enlarge_garage'=>1,
				'GoodsController.buy'=>1,
				'GoodsController.remove'=>1,
				'GoodsController.exhibit_goods'=>1,
				'GoodsController.checkshop'=>1,
				'GoodsController.checkout'=>1,
				'TaskController.share'=>1,
				'TaskController.request'=>1,
				'TaskController.accept'=>1,
				'TaskController.update'=>1,
				'TaskController.finish'=>1,
				'TaskController.get_award'=>1,
				'Friend.dis_neighbor'=>1,
				'Friend.invite_neighbor'=>1,
				'Friend.accept_neighbor'=>1,
					);
		$ret=$c->$m($req['p']);
		if($this->_debug){
			CrabTools::myprint($ret,REQ_DATA_ROOT.$mypre.'.resp');
		}
		if(!$ret){
			$ret['s']= "KO";
			$ret['msg']= "$cn::$m return null";
		}
		if($ret['s']=='OK'){
			if(array_key_exists($method,$log_method)){
				TTLog::record(array('m'=>$method,'p'=>json_encode($params)));
			}
		}
		return $ret;
	}
}
