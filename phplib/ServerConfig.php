<?php

/*
global 函数
获取数据库配置
缓存配置及连接获取
 */
 
 function get_server($name='test')
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

class ServerConfig 
{
    public static  $language='';
	public static  $languages=array('zh','ru','pt','en');
	
	const    TT_TokyoTyrant      ='TokyoTyrant';
	const    TT_TokyoTyrantTable = 'TokyoTyrantTable';
	
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

	/**
	 * return Memcached obj on success,name must be varible in this config
	 *
	 *
	 */
	public static  function & connect_memcached_old($name)
	{
		if(!extension_loaded('memcached'))
		{
			$emsg = "Memcached not installed";
			Logger::error($emsg);
			return false;
		}
		
		if(!isset(BasicConfig::$$name)){
			Logger::error("get connect_memcached:$name failed");
			return false;
		}
		static $insts=array();
		if(isset($insts[$name]))
			return $insts[$name];
		$inst=new Memcached($name);
		//set some other option
		$inst->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
		$inst->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
		$inst->addServers(BasicConfig::$$name);
		$insts[$name]=&$inst;
		return $inst;
	}
	
	/**/
	public static  function & connect_memcached($name)
	{	
		if(!isset(BasicConfig::$$name)){
			Logger::error("get connect_memcached:$name failed");
			return false;
		}
		static $insts=array();
		if(isset($insts[$name]))
			return $insts[$name];
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
	 * */
	public static  function connect_main_mysql($id,$flag=0)
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
	
	
	
	
	static protected function get_tt($name,$uid,&$config,$type,$obj)
	{
		static $inst;
		static $byhost;
		
		    
		$id = floor($uid/USERNUM_PERTTDB);
		$ret = &$inst[$name.':'.$type.':'.$id];
		if( $ret )
		    return $ret;
		
		

		$wc = count($config[$id][$type]);
		$r = rand()%$wc;
		$r= $config[$id][$type][$r];
		
		
		$con = &$byhost[$r['host'].$r['port']];
		if($con ){
			$ret = $con;
			return $ret;
		}
		$con = new $obj($r['host'],$r['port']);
		$ret = $con;
		return $ret;
	}
	
	
}



