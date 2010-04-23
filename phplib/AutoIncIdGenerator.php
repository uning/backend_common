<?php

/*
 *use mysql and memcache to generate autoinc id
 *
 */

/*
   工作正常,需要mysql 数据库，
   id_generator
   有$strKeyName对应的表 
   以及一个减小查询压力的支持 Memcche协议的数据库(不是必须)


   CREATE DATABASE id_generator DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

   CREATE TABLE `userid` (
   `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '自增id',
   `keyvalue` VARCHAR( 128 ) NOT NULL COMMENT 'key',
   UNIQUE (
   `keyvalue`
   )
   ) ENGINE = MYISAM ;
 */

class AutoIncIdGenerator
{

    static protected function cache_get($strKey,$strKeyName)
	{
	    $mc=ServerConfig::connect_memcached('cache_server');
		$mckey=$strKeyName.$strKey;
		if($mc)
			$id=$mc->get($mckey,null);
		return $id;
	}
	
	static protected function cache_set($value,$strKey,$strKeyName)
	{
	    $mc=ServerConfig::connect_memcached('cache_server');
		$mckey=$strKeyName.$strKey;
		if($mc)
			$mc->set($mckey,$value);
	}
	/**
	 * return id,if not exists add it
	 *
	 * @paam string $strKey,the staff to gen key
	 *
	 */
	
	static function genid($strKey,$strKeyName='userid')
	{
		if(!$strKey)
			return 0;
		$id=self::getid($strKey,$strKeyName);
		if($id>0){ //alredy generated
			return $id;
		}
		if(!$id){
		    $db=ServerConfig::connect_mysql(BasicConfig::$genid_mysql_db);
			$db->insert($strKeyName,array('keyvalue'=>$strKey));
			$id= $db->lastInsertId();
		}
		if($id>0){ // generated
			self::cache_set($id,$strKey,$strKeyName);
			return $id;
		}
		throw new Exception(__METHOD__." failed  key='$strKeyName' value='$strKey'");
	}

	/**
	 *
	 * just query
	 *
	 */
	static function getid($strKey,$strKeyName='userid')
	{
		$id=self::cache_get($strKey,$strKeyName);
		if($id)
		 return $id;
		$db=ServerConfig::connect_mysql(BasicConfig::$genid_mysql_db);
		$sql="select `id` from `$strKeyName` where `keyvalue`='$strKey' ";
		$id = $db->fetchOne($sql);
		if($id>0){ //alredy generated
			self::cache_set($id,$strKey,$strKeyName);
			return $id;
		}
		return 0;
	}

	/*
	   获取用户数目
	 */
	static function userCount()
	{
		
		$db=ServerConfig::connect_mysql(BasicConfig::$genid_mysql_db);
		$sql="select max(id) from `userid` where `id`> 10000";
		return  $db->fetchOne($sql);
	}

	/*
	 *根据id 获取该id的key(获取platform_id
	 */
	static function getPlatformId($id)
	{
		if(!$id)
			return 0;
		$strKey="in_$id";
		$pid=self::cache_get($strKey,$strKeyName);
		if($rid){ //alredy generated
			return $pid;
		}

		$sql="select keyvalue from `userid` where `id`=$id";
		$db=ServerConfig::connect_mysql(BasicConfig::$genid_mysql_db);
		$pid = $db->fetchOne($sql);
		if($pid){
		    self::cache_set($pid,$strKey,$strKeyName);
			return $pid;
		}
	}


	/*
	 *use by correct data
	 *
	 */
	static function setid($id,$strKey,$strKeyName='userid')
	{
		$db=ServerConfig::connect_mysql(BasicConfig::$genid_mysql_db);
		$db->delete($strKeyName,"`id`=$id");
		$db->delete($strKeyName,"keyvalue='$strKey'");

		$db->insert($strKeyName,array('keyvalue'=>$strKey,'id'=>$id));
		$id= $db->lastInsertId();
		if($id>0){ //alredy generated
			self::cache_set($id,$strKey,$strKeyName);
			return $id;
		}
		throw new Exception(__METHOD__." failed id=$id key='$strKeyName' value='$strKey'");
	}

}
