<?php



class CassandraCF {
	const DEFAULT_ROW_LIMIT = 1000; // default max # of rows for get_range()
	const DEFAULT_COLUMN_TYPE = "UTF8String";
	const DEFAULT_SUBCOLUMN_TYPE = null;

	const CT_BytesType = 'BytesType';
	const CT_AsciiType = 'AsciiType';
	const CT_LongType =  'LongType';
	const CT_TimeUUIDType = 'TimeUUIDType';
	const CT_LexicalUUIDType = 'LexicalUUIDType';
	const CT_UTF8Type = 'UTF8Type';

	public $keyspace;
	public $column_family;
	public $is_super;
	public $read_consistency_level;
	public $write_consistency_level;
	public $column_type; // CompareWith (TODO: actually use this)
	public $subcolumn_type; // CompareSubcolumnsWith (TODO: actually use this)
	public $parse_columns;

	/**
	 * value是否有复合结构
	 * 注意name
	 * @var bool
	 */
	public   $needSV=true;
	const    SV_PRE='@';//名称含该值的时候，value需要序列化
	public   $id_name = 'id';

	/*
	 BytesType: Simple sort by byte value. No validation is performed.
	 AsciiType: Like BytesType, but validates that the input can be parsed as US-ASCII.
	 UTF8Type: A string encoded as UTF8
	 LongType: A 64bit long
	 LexicalUUIDType: A 128bit UUID, compared lexically (by byte value)
	 TimeUUIDType: a 128bit version 1 UUID, compared by timestamp
	 */

	public function CassandraCF($keyspace, $column_family,
	$is_super=false,
	$column_type=self::DEFAULT_COLUMN_TYPE,
	$subcolumn_type=self::DEFAULT_SUBCOLUMN_TYPE,
	$read_consistency_level=cassandra_ConsistencyLevel::ONE,
	$write_consistency_level=cassandra_ConsistencyLevel::ONE ) {
		// Vars
		$this->keyspace = $keyspace;
		$this->column_family = $column_family;

		$this->is_super = $is_super;

		$this->column_type = $column_type;
		$this->subcolumn_type = $subcolumn_type;

		$this->read_consistency_level = $read_consistency_level;
		$this->write_consistency_level = $write_consistency_level;

		// Toggles parsing columns
		$this->parse_columns = true;
		//   $this->parse_columns = false;
	}

	/**
	 * 获取一列
	 * @param $key
	 * @param $super_column
	 * @param $column_reversed
	 * @param $column_count
	 * @param $slice_start
	 * @param $slice_finish
	 * @return unknown_type
	 */
	public function get($key, $super_column=NULL, $column_reversed=false, $column_count=self::DEFAULT_ROW_LIMIT, $slice_start="", $slice_finish="") {
		$column_parent = new cassandra_ColumnParent();
		$column_parent->column_family = $this->column_family;
		$column_parent->super_column = $this->unparse_column_name($super_column, $this->column_type);

		$slice_range = new cassandra_SliceRange();
		$slice_range->count = $column_count;
		$slice_range->reversed = $column_reversed;
		if($super_column==null){
		$slice_range->start  = $slice_start  ? $this->unparse_column_name($slice_start,   $this->column_type) : "";
		$slice_range->finish = $slice_finish ? $this->unparse_column_name($slice_finish,  $this->column_type) : "";
		}else{
			$slice_range->start  = $slice_start  ? $this->unparse_column_name($slice_start,   $this->subcolumn_type) : "";
		$slice_range->finish = $slice_finish ? $this->unparse_column_name($slice_finish,  $this->subcolumn_type) : "";
			
		}
		$predicate = new cassandra_SlicePredicate();
		$predicate->slice_range = &$slice_range;

		$client = CassandraConn::get_client();
		$resp = $client->get_slice($this->keyspace, $key, $column_parent, $predicate, $this->read_consistency_level);
		return self::supercolumns_or_columns_to_array($resp);
	}

	// Wrappers
	public function get_list($key,  $column_reversed=false,$key_name='key',  $column_count=self::DEFAULT_ROW_LIMIT,$slice_start="", $slice_finish="") {
		// Must be on supercols!
		$resp = $this->get($key, NULL,$reversed, $column_reversed, $column_count,$slice_start, $slice_finish);
		$ret = array();
		foreach($resp as $_key => $_value) {
			$_value[$key_name] = $_key;
			$ret[] = $_value;
		}
		return $ret;
	}

	/**
	 * 获取多行，可指定列
	 * @param $keys
	 * @param $colnames
	 * @return array
	 */
	public function multigetcols($keys, $colnames=array())
	{
		$predicate = new cassandra_SlicePredicate();
		if($colnames){
			$predicate->column_names =$colnames;
		}else{
			$predicate->slice_range = new cassandra_SliceRange();
			$predicate->slice_range->start = '';
			$predicate->slice_range->finish ='';
			$predicate->slice_range->count = self::DEFAULT_ROW_LIMIT;
			$predicate->slice_range->reversed = false;
		}
		$column_parent = new cassandra_ColumnParent();
		$column_parent->column_family = $this->column_family;
		$client = CassandraConn::get_client();
		$resp = $client->multiget_slice($this->keyspace, $keys, $column_parent, $predicate, $this->read_consistency_level);

		$ret = array();
		foreach($keys as $sk => $k) {
			$ret[$k] = $this->supercolumns_or_columns_to_array($resp[$k]);
		}
		return $ret;
	}
	
	/**
	 * 获取指定列
	 * @param $key
	 * @param $colnames
	 * @return unknown_type
	 */
    public function getcols($key, $colnames=array())
	{
		$ret = $this->multigetcols(array($key), $colnames);
		return $ret[$key];
	}

	/**
	 * 获取多行
	 * @param $keys
	 * @param $slice_start
	 * @param $slice_finish
	 * @return unknown_type
	 */
	public function multiget($keys, $reversed=false,$slice_start="", $slice_finish="") {
		$column_parent = new cassandra_ColumnParent();
		$column_parent->column_family = $this->column_family;
		$column_parent->super_column = NULL;

		$slice_range = new cassandra_SliceRange();
		$slice_range->reversed = $reversed;
		$slice_range->start  = $slice_start  ? $this->unparse_column_name($slice_start,  true) : "";
		$slice_range->finish = $slice_finish ? $this->unparse_column_name($slice_finish, true) : "";
		$predicate = new cassandra_SlicePredicate();
		$predicate->slice_range = $slice_range;

		$client = CassandraConn::get_client();
		$resp = $client->multiget_slice($this->keyspace, $keys, $column_parent, $predicate, $this->read_consistency_level);

		$ret = null;
		foreach($keys as $sk => $k) {
			$ret[$k] = $this->supercolumns_or_columns_to_array($resp[$k]);
		}
		return $ret;
	}

	/**
	 * 获取列数
	 * @param $key
	 * @param $super_column
	 * @return unknown_type
	 */
	public function get_count($key, $super_column=null) {
		$column_path = new cassandra_ColumnPath();
		$column_path->column_family = $this->column_family;
		$column_path->super_column = $this->unparse_column_name( $super_column,$this->column_type);

		$client = CassandraConn::get_client();
		$resp = $client->get_count($this->keyspace, $key, $column_path, $this->read_consistency_level);

		return $resp;
	}

	/**
	 * 获取多行
	 * @param $start_key
	 * @param $finish_key
	 * @param $row_count
	 * @param $reversed
	 * @param $slice_start
	 * @param $slice_finish
	 * @return unknown_type
	 */
	public function get_range($start_key="", $finish_key="", $row_count=self::DEFAULT_ROW_LIMIT,$reversed=false, $slice_start="", $slice_finish="") {
		$column_parent = new cassandra_ColumnParent();
		$column_parent->column_family = $this->column_family;
		$column_parent->super_column = NULL;

		$slice_range = new cassandra_SliceRange();
		$slice_range->start  = $slice_start  ? $this->unparse_column_name($slice_start,  true) : "";
		$slice_range->finish = $slice_finish ? $this->unparse_column_name($slice_finish, true) : "";
		$slice_range->reversed = $reversed;
		$predicate = new cassandra_SlicePredicate();
		$predicate->slice_range = &$slice_range;


		$client = CassandraConn::get_client();
		$resp = $client->get_range_slice($this->keyspace, $column_parent, $predicate,
		$start_key, $finish_key, $row_count,
		$this->read_consistency_level);


		return $this->keyslices_to_array($resp);
	}

	/**
	 * 以list的形式获取
	 * @param $key_name
	 * @param $start_key
	 * @param $finish_key
	 * @param $row_count
	 * @param $slice_start
	 * @param $slice_finish
	 * @return unknown_type
	 */
	public function get_range_list($key_name='key', $start_key="", $finish_key="",
	$row_count=self::DEFAULT_ROW_LIMIT,$rev, $slice_start="", $slice_finish="") {
		$resp = $this->get_range($start_key, $finish_key, $row_count,$rev ,$slice_start, $slice_finish);
		$ret = array();
		foreach($resp as $_key => $_value) {
			if(!empty($_value)) { // filter nulls
				$_value[$key_name] = $_key;
				$ret[] = $_value;
			}
		}
		return $ret;
	}


	/**
	 * 插入或更新一行，不指定id，则生成id
	 * @param $data
	 * @param $id
	 * @return unknown_type
	 */
	public function put(&$data,$id = null,$idname='id')
	{
		$id = $this->checkid($this->column_type,$data,$id,$idname);
		$this->_put($id,$data);
		return $id;
	}


	public function update(&$data,$keyname='key')
	{
		$id = $data[$keyname];
		if($id){
			$this->_put($id,$data);
		}
		throw new Exception("no $keyname field set");
	}

	/**
	 * 插入一个子行
	 * @param $data
	 * @param $pid
	 * @param $id
	 * @return unknown_type
	 */
	public function put_super($data,$key,$idname='id',$id = null)
	{
		$id = $this->checkid($this->column_type,$data,$id,$idname);
		$p[$id] = &$data;
		$this->_put($key,$p);
		return $id;
	}

	/**
	 * 插入多个子行
	 * @param $datas
	 * @param $pid
	 * @return unknown_type
	 */
	public function putmulti_super(&$datas,$key,$idname='id')
	{
		foreach($datas as $k=>$data){
			$id = null;
			$id = $this->checkid($this->column_type,$data,$id,$idname);
			$ret[$k] = $id;
			$p[$id] = $data;
			 
		}
		 
		$this->_put($key,$p);
		 
		return $ret;
	}

	/**
	 * 检查行id，无自动生成
	 * @param $type
	 * @param $data
	 * @param $id
	 * @param $idname
	 * @return unknown_type
	 */
	protected function checkid($type,&$data,$id=null,$idname='id'){

		if(!$id){
			$id = $data[$idname];

			if(!$id){
				if($type == self::CT_TimeUUIDType
				|| $type == self::CT_LexicalUUIDType){

					$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,$this->column_family,$this->column_type);
					 
				}else{
					$id = uniqid();
				}
			}
		}
		$data[$idname] = $id;
		// unset($data[$idname]);
		return $id;
	}
	 

	/**
	 * 插入数据
	 * @param $key
	 * @param $columns
	 * @return unknown_type
	 */
	public function _put($key, &$columns) {
		$timestamp = CassandraUtil::get_time();
		$cfmap = array();
		// $cfmap[$this->column_family] =$this->array_to_mutations($columns, $this->is_super,$timestamp,$this->column_type);
		$cfmap[$key][$this->column_family]  =  &$this->array_to_mutations($columns, $this->is_super,$timestamp,$this->column_type
		,$this->subcolumn_type);


		$client = CassandraConn::get_client();
		$resp = $client->batch_mutate($this->keyspace,$cfmap, $this->write_consistency_level);
		return $resp;
	}

	/**
	 *
	 * @param $key
	 * @param $column_name
	 * @return unknown_type
	 */
	public function remove($key, $column_name=null) {
		$timestamp = CassandraUtil::get_time();
		$column_path = new cassandra_ColumnPath();
		$column_path->column_family = $this->column_family;
		if($this->is_super) {
			$column_path->super_column = $this->unparse_column_name($column_name, $this->column_type);
		} else {
			$column_path->column = $this->unparse_column_name($column_name, $this->column_type);
		}
		$client = CassandraConn::get_client();
		$resp = $client->remove($this->keyspace, $key, $column_path, $timestamp, $this->write_consistency_level);
		return $resp;
	}


	/**
	 * 清除指定colnames
	 * @param $key
	 * @param $super
	 * @param $colnames
	 * @return
	 */
	public function erase($key, $colnames,$sc_name=null) {

		$mu  = new cassandra_Mutation();
		$del = new cassandra_Deletion();
		$slice  = new cassandra_SlicePredicate();
		$slice->column_names = array('name','time');

		$del->predicate = $slice;

		$del->super_column = $this->unparse_column_name($sc_name,$this->column_type);
		$del->timestamp = CassandraUtil::get_time();
		$mu->deletion = $del;

		$mus []=$mu;
		$client = CassandraConn::get_client();
		$mmap = array();
		$mmap[$key][$this->column_family] = $mus;

		$client->batch_mutate($this->keyspace,$mmap,$this->write_consistency_level);
	}



	 
	// Helpers for parsing Cassandra's thrift objects into PHP arrays
	static  protected  function keyslices_to_array($keyslices) {
		$ret = array();
		foreach($keyslices as $keyslice) {
			$key     = $keyslice->key;
			$columns = $keyslice->columns;

			$ret[$key] = self::supercolumns_or_columns_to_array($columns);
		}
		return $ret;
	}

	static protected function supercolumns_or_columns_to_array($array_of_c_or_sc){
		$ret = array();

		 
		foreach($array_of_c_or_sc as $c_or_sc) {
			if($c_or_sc->column) { // normal columns
				self::from_column_value( $ret,$c_or_sc->column);
			} else if($c_or_sc->super_column) { // super columns
				$name    = self::parse_column_name($c_or_sc->super_column->name);
				self::columns_to_array($ret[$name] ,$c_or_sc->super_column->columns);
			}
		}
		return $ret;
	}

	static protected function columns_to_array(&$ret,$array_of_c) {
		foreach($array_of_c as $c) {
			self::from_column_value($ret,$c);
		}

	}

	// Helpers for turning PHP arrays into Cassandra's thrift objects
	static  protected function array_to_supercolumns_or_columns(&$array,$is_super =false, $timestamp=null,$type=CassandraCF::CT_BytesType) {
		if(empty($timestamp)) $timestamp = CassandraUtil::get_time();

		$ret = null;
		foreach($array as $name => $value){
			$c_or_sc = new cassandra_ColumnOrSuperColumn();
			 
			if($is_super && is_array($value)) {
				$c_or_sc->super_column = new cassandra_SuperColumn();
				$c_or_sc->super_column->name = self::unparse_column_name($name,$type);

				$c_or_sc->super_column->columns = self::array_to_columns($value, $timestamp,$type);

				$c_or_sc->super_column->timestamp = $timestamp;
			} else {
				$c_or_sc->column = new cassandra_Column();
				self::to_column_value($c_or_sc->column,$name,$value,$type);
				$c_or_sc->column->timestamp = $timestamp;
			}
			$ret[] = $c_or_sc;
		}
		return $ret;
	}

	 

	static protected function array_to_columns($array,  $timestamp=null,$type) {
		if(empty($timestamp)) $timestamp = CassandraUtil::get_time();
		$ret = null;
		foreach($array as $name => $value) {
			$column = new cassandra_Column();
			self::to_column_value($column,$name,$value,$type);
			$column->timestamp = $timestamp;
			$ret[] = $column;
		}
		return $ret;
	}


	/**
	 * 生成mutation
	 * @param $array
	 * @param $timestamp
	 * @return unknown_type
	 */
	static protected function array_to_mutations(&$array,$is_super =false, $timestamp=null,
	$type=CassandraCF::CT_BytesType,$subtype=CassandraCF::CT_BytesType
	) {
		if(empty($timestamp)) $timestamp = CassandraUtil::get_time();
		$ret = array();
		foreach($array as $name => $value){
			$mutation  = new cassandra_Mutation();
			$c_or_sc = &$mutation->column_or_supercolumn ;
			$c_or_sc = new cassandra_ColumnOrSuperColumn();
			if($is_super && is_array($value)) {
				$c_or_sc->super_column = new cassandra_SuperColumn();
				$c_or_sc->super_column->name = self::unparse_column_name($name,$type);

				$c_or_sc->super_column->columns = self::array_to_columns($value, $timestamp,$subtype);

				$c_or_sc->super_column->timestamp = $timestamp;
			} else {
				$c_or_sc->column = new cassandra_Column();
				self::to_column_value($c_or_sc->column,$name,$value,$type);
				$c_or_sc->column->timestamp = $timestamp;
			}

			 
			$ret[] = $mutation;
			 
		}

		return $ret;
	}




	static protected  function to_column_value(&$col,&$name,&$value,$type=CassandraCF::CT_BytesType,$needSV=true) {
		 
		//$col =new cassandra_Column();
		$col->name = self::unparse_column_name($name, $type);

		if($needSV
		&& (is_array($value)||is_object($value))
		){
			$col->value = self::SV_PRE.json_encode($value);
		}else{
			$col->value = $value;
		}

	}


	/**
	 * 将一个col转化为一个pair
	 * @param $arr
	 * @param $col
	 * @param $type
	 * @return unknown_type
	 */
	static protected  function from_column_value(&$arr,&$col,$type=CassandraCF::CT_BytesType,$needSV=true) {

		$name = self::parse_column_name($col->name,$type);

		if($this->needSV && substr_compare($col->value,self::SV_PRE,0,1,false)==0){
			try{
				$arr[$name] = json_decode(substr($col->value,1),true);
			}catch (Exception $e){
				$arr[$name] = $col->value;
			}
		}
		if($arr[$name]=='' && !is_array($arr[$name])){
			$arr[$name] = $col->value;
		}

		 
	}

	// ARGH
	static protected function parse_column_name($column_name, $type=CassandraCF::CT_BytesType){
		if(!$column_name) return NULL;
		if(!UUID::isBinary($column_name))
		return $column_name;

		$s = strlen($column_name);
		if($s==16){
			return UUID::toStr($column_name);
		}
		//default LongType
		if($s==8){
			$tmp = unpack("N2", $column_name); // FIXME: currently only supports 32 bit unsigned
			return $tmp[1];
		}
		return $column_name;
	}

	static protected function unparse_column_name($column_name, $type=CassandraCF::CT_BytesType) {

		if(!$column_name) return NULL;
		if(UUID::isBinary($column_name))
		return $column_name;


		if($type == self::CT_TimeUUIDType
		|| $type == self::CT_LexicalUUIDType) {
			return UUID::convert($column_name, UUID::FMT_STRING, UUID::FMT_BINARY);
		} else if($type == self::CT_LongType) {
			return pack("N2", $column_name, 0); // FIXME: currently only supports 32 bit unsigned
		} else {
			return $column_name;
		}
	}
}

?>
