<?php

/**
 * Simple ORM
 * OPERATION,cache for table
 *
 * insert
 * update
 * find
 *
 * delete
 * save
 *
 * note: some function must have datarow init to execute,db is a option parameter
 *
 * todo: add verbose ,error controll code
 *
 */

class DBModel
{

	protected $_db     ;//dbhanle use ZendDb
	protected $_dbrow  = array();//the row data recent fetch
	protected $_dbrowc = array();//the change of data


	protected $_use_cache  = true;//use cache or not


	public $table_name = '';
	
	
	

	/**
	 *
	 * @param string $class_name
	 * @param string $table_name
	 *
	 **/
	public function DBModel(  $table_name='')
	{
		$this->table_name    = $table_name;
	}

	/**
	 * 对一个对象进行互斥操作
	 * id=0,必须有数据
	 * @return true ,locked
	 */
	public function lock($id=0,$time_out=300)
	{
		if(!$id)
		  $id=$this->getAttr('id');
	
		$lkey=$this->table_name.'@l'.$id;
	    $cache=ServerConfig::connect_memcached('cache_server');
	    if(!$cache)
	      return true;
	      
	    
		$now = time();
		$r = $cache->increment($lkey,1);
		if($r>2){	
			$r=$cache->get($lkey.'t');
			if($r+$time_out<$now)
			 return false;
		}
		$r = $cache->set($lkey,2);
		$cache->set($lkey.'t',$now);
		return true;
	}
	
	/**
	 * 对一个对象进行互斥操作
	 * @param unknown_type $id
	 * @return unknown_type
	 */
	public function unlock($id=0)
	{
		if(!$id)
		  $id=$this->getAttr('id');
		$lkey=$this->table_name.'@l'.$id;
	    $cache=ServerConfig::connect_memcached('cache_server');
	    if($cache)
		$r = $cache->set($lkey,1);
	}
	
	
	

	/**
	 * when true ,open cache,default use
	 * @return current flag
	 *
	 */
	public function useCache($flag=true)
	{
		$this->_use_cache=$flag;
	}

	/**
	 * return current cache staus
	 *
	 */
	public function useCached()
	{
		return $this->_use_cache;
	}




	/**
	 * @return the record
	 */
	public function getDbrow()
	{
		return $this->_dbrow;
	}

	/**
	 * 
	 * @param $row
	 * @return null
	 */
	public function setDbrow($row)
	{
		$this->_dbrow = $row;
	}

   
	/**
	 * access recent dbrow data
	 *
	 */
	public function getAttr($name)
	{
		if(isset($this->_dbrow[$name]))
		return $this->_dbrow[$name];
	}
	

	/**
	 * update recent data
	 * $param bool will_save,is it need to save to database
	 */
	public function setAttr($name,$value, $will_save = true)
	{
		if(!is_array($this->_dbrow))
			return;
		if(array_key_exists($name,$this->_dbrow)&&$this->_dbrow[$name]!=$value){
			if($will_save)
			$this->_dbrowc[$name]=$value;
			else
			$this->_dbrow[$name]=$value;
		}
	}
	

	/**
	 * 获取结构化字段数据
	 * @param string $name
	 */
	public function getAttrO($name)
	{
		 $ret=unserialize($this->getAttr($name));
		 if(!$ret)
		   $ret=array();
		 return $ret;
	}
     /**
      * 
	  * 设置结构化字段数据
      * @param string $name
      * @param object $data
      */
	public function setAttrO($name,$data)
	{
		 $this->setAttr($name,serialize($data));
	}
	
	/**
	 * 获取DbText字段数据
	 * @param string $name
	 */
	public function getAttrText($name)
	{
		return DbText::getObject($this->getAttr($name));
	}
     /**
      * 
	  * 设置DbText字段数据
      * @param string $name
      * @param object $data
      */
	public function setAttrText($name,$data)
	{
		$id=DbText::saveObject($data);
		if($id>0){
		  $this->setAttr($name,$id);
		  return true;
		}
		return false;
	}
	

	/**
	 * 
	 * @param array $pairs ,key value pairs 
	 * @param bool $will_save ,是否需要保存到数据库
	 * @param bool $skip_null ,空值是否覆盖
	 * @return null
	 */
	public function setAttrs($pairs,$will_save = true,$skip_null=false)
	{
		if(!is_array($this->_dbrow))
		return;
		foreach($pairs as $k=>$v)
		if(array_key_exists($k,$this->_dbrow)&&$this->_dbrow[$k]!=$v){
			if($skip_null && $v=='')
			continue;
			if($will_save)
			$this->_dbrowc[$k]=$v;
			else{
				$this->_dbrow[$k]=$v;
			}
		}	
	}






	/**
	 *
	 * find by other unique key
	 *
	 * @param scalar $key
	 * @param string $keyname
	 * @return dbrow
	 *
	 * */
	public function findAll($key, $keyname, $db=null)
	{
		$this->check_db_die($db,__METHOD__);

		$rows =  $this->_db->fetchAll("SELECT * FROM {$this->table_name} where $keyname=?", $key);
		$ret = array();
		foreach($rows as $k=>$row){
		 $m = ModelFactory::getModel($this->class_name,$db);
		 $m->setDbrow($row);
		 $ret[] = $m;
		}
		return $ret;
	}
	
	/**
	 * 
	 * @param $db
	 * @return model array of the table
	 */

	public function getAll($db=null)
	{
		$this->check_db_die($db,__METHOD__);

		$rows =  $this->_db->fetchAll("SELECT * FROM {$this->table_name}");
		$ret = array();
		foreach($rows as $k=>$row){
		 $m = ModelFactory::getModel($this->class_name,$db);
		 $m->setDbrow($row);
		 $ret[] = $m;
		}
		return $ret;
	}

	/**
	 *
	 * find by other unique key
	 *
	 * @param scalar $key
	 * @param string $keyname
	 * @return dbrow
	 *
	 * */
	public function find($key,$keyname='id',$db=null)
	{
		$dbrow=$this->getCache($key,$keyname);
		if (!is_array($dbrow)){  //now from db
			$this->check_db_die($db,__METHOD__);
			$dbrow = $this->_db->fetchRow("SELECT * FROM {$this->table_name} where $keyname=? limit 1", $key);
			if($dbrow)
			$this->setCache($key,$dbrow,$keyname);
		}

		if($dbrow)
		$this->setDbrow($dbrow);
		return $dbrow;
	}

	/**
	 *
	 * @param dbrowpairs $data
	 *
	 * $return affected row nums,
	 *
	 * todo add write read cache logic
	 *
	 **/
	public function update($data,$key,$keyname='id',$db=null)
	{
		$this->check_db_die($db,__METHOD__);
		$where  = $this->_db->quoteInto("`$keyname` = ?", $key);
		$affect = $this->_db->update($this->table_name,$data,$where);
		//write read logic
		if($this->_dbrow){
			foreach($data as $k=>$v)
			$this->_dbrow[$k]=$v;
			$this->setCache($key,$this->_dbrow,$keyname);
		}
		else{
			if($affect>0&&$this->_use_cache){
			 $dbrow = $this->_db->fetchRow("SELECT * FROM {$this->table_name} where $keyname=? limit 1", $key);
			 if($dbrow){
			 	$this->setCache($key,$dbrow,$keyname);
			 	$this->_dbrow=$dbrow;
			 }
		 }
		}
		return $affect;
	}


	/**
	 * insert
	 * return insert_id
	 *
	 */
	public function insert($data,$db=null)
	{


		$this->_db->insert($this->table_name,$data);
		$id=$this->_db->lastInsertId();
		$data['id']=$id;
		$this->_dbrow=&$data;
		return $id;
	}


	/**
	 *
	 * 保存记录,必须有数据关联
	 *
	 */

	public function save($keyname='id')
	{
		$id=$this->_dbrow[$keyname];
		$ret=true;
		try{
			if($id ){
				if($this->_dbrowc){
					$ret=$this->update($this->_dbrowc,$id,$keyname);
					$this->_dbrowc=array();
				}
			}
		}catch(Exception $e){
			$ret=false;
			//throw $e; //we just throw it for  debug
		}
		return $ret;
	}



	/**
	 *
	 * delete
	 *
	 */

	public function delete($key=null,$keyname='id',$db=null)
	{
		if(!$key)
		$key=$this->_dbrow[$keyname];
		if(!$key)
		return true;


		$this->check_db_die($db,__METHOD__);
		$where = $this->_db->quoteInto("$keyname = ?", $key);
		$this->_db->delete($this->table_name,$where);
	}


	/**
	 *
	 * checkChange money or gem or other int value
	 * 如改变后小于0,则不改变
	 *
	 */
	public function  checkChange($name,$num)
	{
		$m=$this->getAttr($name)+$num;
		if($num==0)
		return true;
		if($m<0)
		return false;
		$this->setAttr($name,$m);
		return true;
	}


	/**
	 *
	 *  count db rows with some condition
	 *
	 * @param
	 *
	 * */
	public function countWith($column, $value, $db=null)
	{
		$this->check_db_die($db,__CLASS__.'.'.__METHOD__);
		$obj = $this->_db->fetchRow("SELECT COUNT(*)  as count FROM {$this->table_name} where $column=?", $value);
		if($obj)
		return $obj['count'];
		return 0;
	}

	/**
	 *
	 * cache support
	 * 缓存对应表的一列,关联的其他表的数据不做处理,缓存返回和数据返回数据格式一致
	 *
	 * @return dbrow
	 *
	 * todo 缓存处理好的object对象，取出可以直接返回给客户端
	 *
	 **/
	public function getCache($key,$keyname='id')
	{
		if(!$this->_use_cache)
		return false;
		return $this->getCacheData($key,$keyname);
	}
	

	 /**
	  * 
	  * @param $key
	  * @param $keyname
	  * @return 
	  */

	public function getCacheData($key,$keyname='id')
	{
		$cache=ServerConfig::connect_memcached('cache_server');
		if($cache){
			$cid   = "{$this->table_name}-$keyname-$key";
			return $cache->get($cid);
		}
		return null;
	}

	/**
	 * 
	 * @param $key
	 * @param $keyname
	 * @return 
	 */
	public function updateCache($key,$keyname='id')
	{
		if(!$this->_use_cache)
		  return false;
		$use=$this->_use_cache;
		$this->_use_cache=false;
		$data=$this->find($key,$keyname);
		$this->_use_cache=$use;
		if($data){
			$cache=ServerConfig::connect_memcached('cache_server');
		 if($cache){
		 	$cid   = "{$this->table_name}-$keyname-$key";
		 	return $cache->set($cid,$data);
		 }
		}
		return $data;
	}

	/**
	 *
	 * cache save
	 *
	 * */
	public function setCache($key,&$data,$keyname='id')
	{
		if(!$this->_use_cache)
		return false;
		$this->setCacheData($key,$data,$keyname);
	}

	/**
	 * 仅仅用来和memcache打交道，请勿加 use_cache的判断！！
	 * @param $key
	 * @param $data
	 * @param $keyname
	 * @return unknown_type
	 */
	public function setCacheData($key,&$data,$keyname='id')
	{
		$cache=ServerConfig::connect_memcached('cache_server');
		if($cache){
			$cid   = "{$this->table_name}-$keyname-$key";
			return $cache->set($cid,$data);
		}
	}






	/**
	 *
	 */
	public function setDb($db)
	{
		if($db)
		$this->_db=$db;
	}

	/**
	 * throw Exception
	 */
	public  function check_db_die($db,$mess='')
	{
		if($db){
			$this->_db=$db;
		}

		//if($this->_db->isConnected()){
		if($this->_db){
			return ;
		}
		throw new Exception($mess." db is null");
	}

	
	/**
	 * 
	 * @param $col_name 
	 * @param int $value 
	 * @return 
	 */
	public function add($col_name, $value)
	{
		if(!$this->_db||!$value)
		return;
		$stmt = $this->_db->query(
            "UPDATE {$this->table_name} SET $col_name = $col_name + $value WHERE id = ?",
		array($this->getAttr('id'))
		);
		$ret=$stmt->rowCount();
		if($ret){
			$this->_dbrow[$col_name]+=$value;
			$this->updateCache($this->getAttr('id'));
		}
		return $ret;
	}

	/**
	 * 
	 * @param $col_name
	 * @param int $value
	 * @return int
	 */

	public function minus($col_name, $value)
	{
		if(!$this->_db||!$value)
		return;
		$stmt = $this->_db->query(
            "UPDATE {$this->table_name} SET $col_name = $col_name - $value WHERE id = ? and $col_name - $value >= 0",
		array($this->getAttr('id'))
		);

		$ret=$stmt->rowCount();
		if($ret){
			$this->_dbrow[$col_name]-=$value;
			$this->updateCache($this->getAttr('id'));
		}
		return $ret;
	}


	/**
	 * 更改重要数据,利用数据库互斥
	 */
	public function changeNumA($arr)
	{
		$id=$this->getAttr('id');
		$sql="UPDATE {$this->table_name} SET ";
		foreach($arr as $k=>$v){
			if($v)
			$sql.="$k = $k + $v,";
				
		}
		$sql.=" id=$id where id=$id";
		foreach($arr as $k=>$v){
			if($v)
			$sql.="  and  $k + $v >=0 ";
		}
		$stmt=$this->_db->query($sql);
		$ret=$stmt->rowCount();
		if($ret){
		foreach($arr as $k=>$v){
			$this->_dbrow[$k]+=$v;
		}
			$this->updateCache($this->getAttr('id'));
		}
		return $ret;
	}
	
	
	//=========================some test function====================
	/**
	 *
	 * get Table fields ,
	 * todo when production ,get it as config for performance
	 *
	 **/
	public function getTableFields($db=null)
	{
		$sql="desc $this->table_name";
		return $this->_db->fetchAll($sql);

	}
	
	/**
	 * 
	 * @param $str
	 * @return unknown_type
	 */

	public function genTestData(&$str)
	{
		$fields=$this->getTableFields();
		$str= "\${$this->table_name}_data=array(\n";
		foreach($fields as $v)
		{
			$type=$v['Type'];
			$name=$v['Field'];
			if(strstr($type,'int'))
			$value=rand();
			else if(strstr($type,'char'))
			$value='str'.time()."stri";
			else if(strstr($type,'time'))
			$value=Date('Y-m-d H:i:s', time());
			else if(strstr($type,'float'))
			$value=time().'1232';

			$str.="     '$name'=>'$value',\n";
			$data[$name]=$value;	
		}
		$str .= ");\n";
		return $data;
	}
	
	/**
	 * 位操作，一个字符标示5位，对需要大量一次性标记时有用
	 * char(128)可标记640位id 1，2,...,640
	 * @param $flagstr
	 * @return $oldflag
	 */
	static function setBitMask(&$flagstr,$id,$flag=1,$maxlen=640)
	{
		  if($id<1||$id>640){
             return 0;
          }
         $id   -= 1;
         $idx  = floor($id / 5);
         $iidx = $id % 5;
         static $fa=array(0x10,0x8,0x4,0x2,0x1);

         $len=strlen($flagstr);
         if($len<$idx){
          $flagstr=str_pad($flagstr,$idx,0);
         }
         $c=base_convert($flagstr[$idx],32,10);

         $ret=$c&$fa[$iidx];
         $c|=$fa[$iidx];
         if(!$flag)//clear
          $c^=$fa[$iidx];
         $flagstr[$idx]=base_convert($c,10,32);
         return $ret;
	}
	
	static function  getBitSet(&$flagstr,$id=0)
	{
		 static $fa=array(0x10,0x8,0x4,0x2,0x1);
		 $len=strlen($flagstr);
		 $bn=1;
		 if($id){
		 	$id   -= 1;
            $idx  = floor($id / 5);
            $iidx = $id % 5;
            if($idx>$len)
                return 0;
            $c=base_convert($flagstr[$idx],32,10);
            return $c&$fa[$iidx];
		 }
		 $ret=array();
		 for($i=0;$i<$len;$i++){
		 	$c=base_convert($flagstr[$i],32,10);
		 	foreach($fa as $k=>$v){
		 		if($v&$c){
		 			$ret[$i*5+$k+1]=1;
		 		}
		 	}	
		 }
		 return $ret;
	}

}

