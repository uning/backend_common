<?php

/**
 * 
 * use mysql and memcache to save dbtext  
 *
 */

class DbText
{
	/**
	 *
	 * @param string $strKey 
	 * @param int    $lvid  ,text last version id 
	 *
	 * @return the record;
	 *
	 **/
	static function save($strKey,$lvid=0)
	{

	    if(!$strKey)
		 return array();
		 //if(!$lvid)
		 //$lvid=0;
		$db=ServerConfig::connect_mysql(BasicConfig::$text_mysql_db);
		$md5str=md5($strKey);
		$data=array(
			'md5sign'=>$md5str,
			'string'=>$strKey,
			'lvid'=>$lvid
		);
		
		$row = self::getMd5($md5str);
		if($row)
			return $row;

		$db->insert('texts',$data);
		$id=$db->lastInsertId();
		if($id){
			$data['id']=$id;
			$mc=ServerConfig::connect_memcached('cache_server');
			if($mc)
			$mc->set($id,$data);
			return $data;
		}
		return array();
	}

	/**
	 * 
	 * @param int id
	 * @return md5,id,lvid 
	 *
	 */
	static function get($id)
	{
		if(!$id)
		  return array();
		$mc=ServerConfig::connect_memcached('cache_server');
		$key='dt_'.$id;
		if($mc)
		$ret=$mc->get($key,null);
		if($ret){
			return $ret;
		}
		$db=ServerConfig::connect_mysql(BasicConfig::$text_mysql_db);
		$sql="select * from `texts` where id='$id' ";
		$row = $db->fetchRow($sql);
		if($row){ //alredy generated
		   if($mc)
			$mc->add($key,$row);
			return $row;
		}
		return $row;
	}
	
	/**
	 * 
	 * @param $md5
	 * @return unknown_type
	 */
    static function getMd5($md5)
	{
		if(!$md5)
		  return array();
		$mc=ServerConfig::connect_memcached('cache_server');
		$key=$md5;
		if($mc)
		$ret=$mc->get($key,null);
		if($ret){
			return $ret;
		}
		$db=ServerConfig::connect_mysql(BasicConfig::$text_mysql_db);
		$sql="select * from `texts` where md5sign='$md5' ";
		$row = $db->fetchRow($sql);
		if($row){ //alredy generated
		   if($mc)
			$mc->add($key,$row);
			return $row;
		}
		return $row;
	}
	
	
	/**
	 * 
	 * @param $id
	 * @return mixed
	 */
	static public function getObject($id)
	{
		$data=self::get($id);
		if(isset($data['string']))
		 return unserialize($data['string']);
		return array();
	}
	
	/**
	 * @param bigint $id
	 * @param bigint $lvid
	 * @return the id to get the obj
	 */
    static public function saveObject($obj,$lvid=0)
	{
		$str=serialize($obj);
		$ret=self::save($str,lvid);
		return $ret['id'];		
	}
	
}

//return;


//DbText::save();


