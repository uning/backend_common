<?php

// Setting up nodes:
//
// CassandraConn::add_node('192.168.1.1', 9160);
// CassandraConn::add_node('192.168.1.2', 5000);
//

// Querying:
//
// $users = new CassandraCF('Keyspace1', 'Users');
// $users->put('1', array('email' => 'hoan.tonthat@gmail.com', 'password' => 'test'));
// $users->get('1');
// $users->multiget(array(1, 2));
// $users->get_count('1');
// $users->get_range('1', '10');
// $users->remove('1');
// $users->remove('1', 'password');
//

class CassandraConn {
    const DEFAULT_THRIFT_PORT = 9160;

    static private $connections = array();
    static private $last_error;

    static public function add_node($host, $port=self::DEFAULT_THRIFT_PORT) {
        try {
            // Create Thrift transport and binary protocol cassandra client
            $transport = new TBufferedTransport(new TSocket($host, $port), 1024, 1024);
            $client    = new CassandraClient(new TBinaryProtocolAccelerated($transport));

            // Store it in the connections
            self::$connections[] = array(
                'transport' => $transport,
                'client'    => $client
            );

            // Done
            return TRUE;
        } catch (TException $tx) {
            self::$last_error = 'TException: '.$tx->getMessage() . "\n";
        }
        return FALSE;
    }

    // Default client
    static public function get_client($write_mode = false) {
        // * Try to connect to every cassandra node in order
        // * Failed connections will be retried
        // * Once a connection is opened, it stays open
        // * TODO: add random and round robin order
        // * TODO: add write-preferred and read-preferred nodes
        
    	static $last_get;
    	if($last_get instanceof CassandraClient && $last_get->isOpen())
    	   return $last_get;
        shuffle(self::$connections);
        foreach(self::$connections as $connection) {
            try {
                $transport = $connection['transport'];
                $client    = $connection['client'];

                if(!$transport->isOpen()) {
                    $transport->open();
                }
                
                

                $last_get = &$client;
                return $client;
            } catch (TException $tx) {
                self::$last_error = 'TException: '.$tx->getMessage() . "\n";
                continue;
            }
        }

        throw new Exception("Could not connect to a cassandra server");
    }
}

class CassandraUtil {
    // UUID
    static public function uuid1($node="", $ns="") {
        return UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING, $node, $ns);
    }

    // Time
    static public function get_time() {
        // By Zach Buller (zachbuller@gmail.com)
        $time1 = microtime();
        settype($time1, 'string'); //needs converted to string, otherwise will omit trailing zeroes
        $time2 = explode(" ", $time1);
        $time2[0] = preg_replace('/0./', '', $time2[0], 1);
        $time3 = ($time2[1].$time2[0])/100;
        return $time3;
    }
}

class CassandraCF {
    const DEFAULT_ROW_LIMIT = 100; // default max # of rows for get_range()
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

    public function __construct($keyspace, $column_family,
                                $is_super=false,
                                $column_type=self::DEFAULT_COLUMN_TYPE,
                                $subcolumn_type=self::DEFAULT_SUBCOLUMN_TYPE,
                                $read_consistency_level=cassandra_ConsistencyLevel::ONE,
                                $write_consistency_level=cassandra_ConsistencyLevel::ZERO) {
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

    public function get($key, $super_column=NULL, $slice_start="", $slice_finish="", $column_reversed=False, $column_count=100) {
        $column_parent = new cassandra_ColumnParent();
        $column_parent->column_family = $this->column_family;
        $column_parent->super_column = $this->unparse_column_name($super_column, false);

        $slice_range = new cassandra_SliceRange();
        $slice_range->count = $column_count;
        $slice_range->reversed = $column_reversed;
        $slice_range->start  = $slice_start  ? $this->unparse_column_name($slice_start,  true) : "";
        $slice_range->finish = $slice_finish ? $this->unparse_column_name($slice_finish, true) : "";
        $predicate = new cassandra_SlicePredicate();
        $predicate->slice_range = $slice_range;

        $client = CassandraConn::get_client();
        $resp = $client->get_slice($this->keyspace, $key, $column_parent, $predicate, $this->read_consistency_level);
        return $this->supercolumns_or_columns_to_array($resp);
    }
    
    /**
     * 
     * @param $keys
     * @param $colnames
     * @return array
     */
    public function get_cols( $keys, $colnames=array())
    {
         $predicate = new cassandra_SlicePredicate();
		 if($colnames){
		 	$predicate->column_names =$colnames;	
		 }else{
		        $predicate->slice_range = new cassandra_SliceRange();
		        $predicate->slice_range->start = '';
                $predicate->slice_range->finish ='';
                $predicate->slice_range->count ='300';
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

    public function multiget($keys, $slice_start="", $slice_finish="") {
        $column_parent = new cassandra_ColumnParent();
        $column_parent->column_family = $this->column_family;
        $column_parent->super_column = NULL;

        $slice_range = new cassandra_SliceRange();
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

    public function get_count($key, $super_column=null) {
        $column_path = new cassandra_ColumnPath();
        $column_path->column_family = $this->column_family;
        $column_path->super_column = $super_column;

        $client = CassandraConn::get_client();
        $resp = $client->get_count($this->keyspace, $key, $column_path, $this->read_consistency_level);

        return $resp;
    }

    public function get_range($start_key="", $finish_key="", $row_count=self::DEFAULT_ROW_LIMIT, $slice_start="", $slice_finish="") {
        $column_parent = new cassandra_ColumnParent();
        $column_parent->column_family = $this->column_family;
        $column_parent->super_column = NULL;

        $slice_range = new cassandra_SliceRange();
        $slice_range->start  = $slice_start  ? $this->unparse_column_name($slice_start,  true) : "";
        $slice_range->finish = $slice_finish ? $this->unparse_column_name($slice_finish, true) : "";
        $predicate = new cassandra_SlicePredicate();
        $predicate->slice_range = $slice_range;

        $client = CassandraConn::get_client();
        $resp = $client->get_range_slice($this->keyspace, $column_parent, $predicate,
                                         $start_key, $finish_key, $row_count,
                                         $this->read_consistency_level);

        return $this->keyslices_to_array($resp);
    }
    
    /**
     * 插入一行，不指定id，则生成id
     * @param $data
     * @param $id
     * @return unknown_type
     */
    public function put($data,$id = null,$idname='id')
    {
    	$id = $this->checkid($this->column_type,$data,$id,$idname);
    	$this->_put($id,$data);
    	return $id;
    }
    
    /**
     * 插入一个子行
     * @param $data
     * @param $pid
     * @param $id
     * @return unknown_type
     */
    public function putSuper($data,$pid,$id = null,$idname='id')
    {
    	$id = $this->checkid($this->subcolumn_type,$data,$id,$idname);
    	$p[$id] = &$data;
    	$this->_put($pid,$p);
    	return $id;
    }
    
    /**
     * 插入多个子行
     * @param $datas
     * @param $pid
     * @return unknown_type
     */
   public function putSuperMutil($datas,$pid,$idname='id')
    {
    	foreach($datas as $k=>$data){
          $id = $this->checkid($this->subcolumn_type,$data,$id,$idname);
    	  $ret[$k] = $id;
    	  $p[$id] = &$data;
        }
    	$this->_put($pid,$p);
    	return $ret;
    }
    
    protected function checkid($type,&$data,$id=null,$idname='id'){
        
         if(!$id){
         	 $id = $data[$idname];
          
         	  if(!$id){
    	    if($type == self::CT_TimeUUIDType 
    	    || $type == self::CT_LexicalUUIDType){
    	    	$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING);
    	    }else{
    	    	$id = uniqid();
    	    }
         	  }
         }
    	 $data[$idname] = $id;
    	 //print_r($data);
    	 return $id;
    }
    	

    public function _put($key, $columns) {
        $timestamp = CassandraUtil::get_time();

        $cfmap = array();
        //print_r($columns);
        $cfmap[$this->column_family] = $this->array_to_supercolumns_or_columns($columns, $timestamp);

        $client = CassandraConn::get_client();
        $key = $this->unparse_column_name($key);
        $resp = $client->batch_insert($this->keyspace, $key, $cfmap, $this->write_consistency_level);

        return $resp;
    }

    public function remove($key, $column_name=null) {
        $timestamp = CassandraUtil::get_time();

        $column_path = new cassandra_ColumnPath();
        $column_path->column_family = $this->column_family;
        if($this->is_super) {
            $column_path->super_column = $this->unparse_column_name($column_name, true);
        } else {
            $column_path->column = $this->unparse_column_name($column_name, false);
        }

        $client = CassandraConn::get_client();
        $resp = $client->remove($this->keyspace, $key, $column_path, $timestamp, $this->write_consistency_level);

        return $resp;
    }

    // Wrappers
    public function get_list($key, $key_name='key', $slice_start="", $slice_finish="") {
        // Must be on supercols!
        $resp = $this->get($key, NULL, $slice_start, $slice_finish);
        $ret = array();
        foreach($resp as $_key => $_value) {
            $_value[$key_name] = $_key;
            $ret[] = $_value;
        }
        return $ret;
    }

    public function get_range_list($key_name='key', $start_key="", $finish_key="",
                                   $row_count=self::DEFAULT_ROW_LIMIT, $slice_start="", $slice_finish="") {
        $resp = $this->get_range($start_key, $finish_key, $row_count, $slice_start, $slice_finish);
        $ret = array();
        foreach($resp as $_key => $_value) {
            if(!empty($_value)) { // filter nulls
                $_value[$key_name] = $_key;
                $ret[] = $_value;
            }
        }
        return $ret;
    }

    // Helpers for parsing Cassandra's thrift objects into PHP arrays
    protected  function keyslices_to_array($keyslices) {
        $ret = null;
        foreach($keyslices as $keyslice) {
            $key     = $keyslice->key;
            $columns = $keyslice->columns;

            $ret[$key] = $this->supercolumns_or_columns_to_array($columns);
        }
        return $ret;
    }

    protected function supercolumns_or_columns_to_array($array_of_c_or_sc) {
        $ret = array();
        foreach($array_of_c_or_sc as $c_or_sc) {
            if($c_or_sc->column) { // normal columns
                $this->from_column_value( $ret,$c_or_sc->column,true);
            } else if($c_or_sc->super_column) { // super columns
                 $name    = $this->parse_column_name($c_or_sc->super_column->name, true);
                 $this->columns_to_array($ret[$name] ,$c_or_sc->super_column->columns,false);
            }
        }
        return $ret;
    }

    protected function columns_to_array(&$ret,$array_of_c,$is_column) {
        foreach($array_of_c as $c) {
            $this->from_column_value($ret,$c,$is_column);
        }
       
    }

    // Helpers for turning PHP arrays into Cassandra's thrift objects
    protected function array_to_supercolumns_or_columns($array, $timestamp=null) {
        if(empty($timestamp)) $timestamp = CassandraUtil::get_time();

        $ret = null;
        foreach($array as $name => $value){
        	$c_or_sc = new cassandra_ColumnOrSuperColumn();
            if($this->is_super && is_array($value)) {
                $c_or_sc->super_column = new cassandra_SuperColumn();
                $c_or_sc->super_column->name = $this->unparse_column_name($name, true);
                
                $c_or_sc->super_column->columns = $this->array_to_columns($value, $timestamp);

                $c_or_sc->super_column->timestamp = $timestamp;
            } else {
                $c_or_sc->column = new cassandra_Column();
                $this->to_column_value($c_or_sc->column,$name,$value,true);
                $c_or_sc->column->timestamp = $timestamp;
            }
            $ret[] = $c_or_sc;
        }

        return $ret;
    }

    protected function array_to_columns($array, $timestamp=null) {
        if(empty($timestamp)) $timestamp = CassandraUtil::get_time();

        $ret = null;
        foreach($array as $name => $value) {
            $column = new cassandra_Column();
            $this->to_column_value($column,$name,$value,false);
            $column->timestamp = $timestamp;

            $ret[] = $column;
        }
        return $ret;
    }

    protected  function to_column_value(&$col,&$name,&$value,$is_column) {
       
        if($this->needSV
           ||(is_array($value)||is_object($value))
        ){
        	$col->name = self::SV_PRE.$name;
        	$col->value = json_encode($value);
        }else{
            $col->name = $this->unparse_column_name($name, $is_column);
        	$col->value = $value;
        }
    }
    
    
   protected  function from_column_value(&$arr,&$col,$is_column) {
   	  
        if($this->needSV && substr_compare($col->name,self::SV_PRE,0,1,false)==0){
        	
        	$arr[substr($col->name,1)] = json_decode($col->value,true);
        }else{
          $arr[$this->unparse_column_name($col->name, $is_column)] = json_decode($col->value,true);
        }
    }

    // ARGH
    protected function parse_column_name($column_name, $is_column=true) {
        if(!$this->parse_columns) return $column_name;
        if(!$column_name) return NULL;

        $type = $is_column ? $this->column_type : $this->subcolumn_type;
        if($type == self::CT_TimeUUIDType 
    	    || $type == self::CT_LexicalUUIDType) {
        	
            return UUID::convert($column_name, UUID::FMT_BINARY, UUID::FMT_STRING);
        } else if($type == self::CT_LongType) {
            $tmp = unpack("N2", $column_name); // FIXME: currently only supports 32 bit unsigned
            return $tmp[1];
        } else {
            return $column_name;
        }
    }

    protected function unparse_column_name($column_name, $is_column=true) {
        if(!$this->parse_columns) return $column_name;
        if(!$column_name) return NULL;

        $type = $is_column ? $this->column_type : $this->subcolumn_type;
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
