<?php

/*
global 函数
获取数据库配置
缓存配置及连接获取
 */
 

class ServerConfig 
{
    public static  $language='';
	public static  $languages=array('zh','ru','pt','en');
	
	
	static public function setLang($lang)
	{
	   self::$language=$lang;
	}
	
	static public function getLang()
	{
	  return self::$language;
	}


	
	public static function connect_mysql(&$params)
	{
		require_once 'Zend/Db.php';
		static $insts;
		$name=md5(serialize($params));
		if(isset($insts[$name]))
			return $insts[$name];
		try{
			$db=Zend_Db::factory('PDO_MYSQL', $params);
			$insts[$name]=$db;
		}catch (Exception $e){
			throw $e;

		}
		return $db;
	}

	
	/**/
	public static  function & connect_memcached($name)
	{	
		static $insts=array();
		if(isset($insts[$name]))
			return $insts[$name];
		require_once('MemcacheClient.php');
		if(!isset(self::$$name)){
			Logger::error("get connect_memcached:$name failed");
			return false;
		}
		$inst=new MemcacheClient($name);
		//set some other option
		foreach(BasicConfig::$$name as $h){
			$inst->addServer($h[0],$h[1]);
		}
		$insts[$name]=$inst;
		return $inst;
	}


	/**
	 * connect to mysql
	 *
	 */
	public static  function connect_main($id,$flag=0)
	{
		if ($flag==1){//useid 
			$id=floor($id/USERNUM_PERDB);
		}
		if(!isset(BasicConfig::$main_mysql_db[$id])){
			error_log("get_mysql:conf not set: id=$id");
			return false;
		}
		else
			return ServerConfig::connect_mysql(BasicConfig::$main_mysql_db[$id]);

	}

	/**
	 * function get db connection
	 */
	 
	public static function getdb_by_userid($userid)
	{
		return ServerConfig::connect_main_mysql($userid,1);
	}

	public static function getdb_by_platformid($platformid)
	{
		$userid=AutoIncIdGenerator::genid($platformid);
		if($userid)
			return ServerConfig::connect_shop_mysql($userid,1); 
	}


	static function get_server($name='test')
	{
		if(!IS_PRODUCTION)
			$name='test';
		$cname=ucfirst($name).'_Amf_Server';

		$file=AMF_SERVER_ROOT.$cname.'.php';
		if(@require_once($file)){
			return new $cname;
		}
		require_once 'Zend/Amf/Server.php';
		return new Zend_Amf_Server();
	}





}



