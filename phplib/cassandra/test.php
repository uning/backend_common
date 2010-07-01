<?php
require_once 'config.php';
CassandraConn::add_node('localhost', 9160);

function record_time($start,$usage="")
{
	$end  = microtime(true);
	$cost=$end-$start;
	$cost=ceil(1000000*$cost);
	if($usage)
	echo "$usage use time $cost us\n";
	return $end;
}

class Test{

	static function testUUID()
	{
		$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,'node','ns');
		echo "$id\n";
		$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,'node','ns');
		echo "$id\n";
		$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,'node','ns');
		echo "$id\n";
		$packid = UUID::convert($id, UUID::FMT_STRING, UUID::FMT_BINARY);
		echo strlen($packid)."\n";
		echo UUID::convert($packid, UUID::FMT_BINARY, UUID::FMT_STRING)."\n";
	}

	static public function  testSuper()
	{
		$key =  time();
		$t = new CassandraCF('mall', 'UserMessages',true,CassandraCF::CT_TimeUUIDType,CassandraCF::CT_BytesType);
		$cdata = array('name'=>'user1','time'=>time(),'arr'=>array('dfd','dfds'));
		echo "before insert \n";
		print_r($cdata);
		$scname = $t->put_super($cdata,$key);

		$retdata = $t->get($key,$scname);
		echo "after insert get\n";
		print_r($retdata);
		$udata['name'] = 'user12 modify';
		$udata['newcol'] = 'neww colvalue';
		$udata['id'] = $scname;
		$scname = $t->put_super($udata,$key);

		$retdata = $t->get($key,$scname);
		echo "after update get\n";
		print_r($retdata);





		echo '$t->get_count($key) = '.$t->get_count($key)."\n";
		$udata = array();
		$udata['name'] = 'user13 modify';
		$udata['newcol'] = 'neww colvalue';
		//$udata['id'] = $scname;
		echo "insert a new super col\n";
		$scname1 = $t->put_super($udata,$key);
		echo '$t->get_count($key) = '.$t->get_count($key)."\n";


		echo '$t->get_count($key,$scname) = '.$t->get_count($key,$scname)."\n";
		$t->erase($key,array('name','time'),$scname);
		$retdata = $t->get($key,$scname);
		echo "after delete cols  get\n";
		print_r($retdata);

		echo '$t->get_count($key,$scname) = '.$t->get_count($key,$scname)."\n";


		echo '$t->get_count($key) = '.$t->get_count($key)."\n";
		$udata['name'] = 'user13 modify';
		$udata['newcol'] = 'neww colvalue';
		//$udata['id'] = $scname;
		echo "after delete a new super col\n";
		$t->remove($key,$scname1);
		echo '$t->get_count($key) = '.$t->get_count($key)."\n";
	}

	static public function testMultiPutSupper()
	{
		$key =  time();
		$t = new CassandraCF('mall', 'UserMessages',true,CassandraCF::CT_TimeUUIDType,CassandraCF::CT_BytesType);
		for($u=1;$u<10;++$u){
			$data=array();
			$data['user'] = $u;
			$data['msg']="time $now";
			//$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,'node','ns');
			//$data[$id] = array('dfdf','dfd');
			// $t->put_super($data,$key);
			$ret[]=$data;
		}

		$ret=$t->putmulti_super($ret,$key);
		$retdata = $t->get($key,$scname);
		echo "after insert 10 super cols  get\n";
		print_r($retdata);

		echo 'print_r($t->get($newid,null,false,3))';
		print_r($t->get($key,null,false,3));
		echo 'print_r($t->get($key,null,true,3))=';
		print_r($t->get($key,null,true,3));
		echo 'print_r($t->get($$key)=';
		print_r($t->get($key));


	}



	static function testCF()
	{
		$key =  time();
		$t = new CassandraCF('mall', 'UserInfos');
		$cdata = array('name'=>'user1','time'=>time(),'arr'=>array('dfd','dfds'));
		echo "before insert \n";
		print_r($cdata);
		$t->put($cdata,$key);

		$retdata = $t->get($key);
		echo "after insert get\n";
		print_r($retdata);
		$udata['name'] = 'user12 modify';
		$udata['newcol'] = 'neww colvalue';
		$scname = $t->put($udata,$key);

		$retdata = $t->get($key);
		echo "after update get\n";
		print_r($retdata);








		echo '$t->get_count($key) = '.$t->get_count($key)."\n";
		$t->erase($key,array('name','time'));
		$retdata = $t->get($key);
		echo "after delete cols  get\n";
		print_r($retdata);

		echo '$t->get_count($key) = '.$t->get_count($key)."\n";
	}


	static function getSlice()
	{
		$key =  2;
		$t = new CassandraCF('mall', 'UserInfos');
		for($i=0;$i<13;$i++)
		{
			$arr["add$i"]="add$i";
		}
		for($i=0;$i<13;$i++)
		{
			$arr["bdd$i"]="bdd$i";
		}
		$t->put($arr,$key);

		echo "get slice from='' to=''\n";
		$ret = $t->get($key);
		print_r($ret);
		echo "get slice from='add' to=''\n";
		$ret = $t->get($key,null,false,1000,'add','');
		print_r($ret);
		echo "get slice from='add' to='bdd'\n";
		$ret = $t->get($key,null,false,1000,'add','bdd');
		print_r($ret);
	}
}
try{
	// Test::testSuperUpdate();
	Test::getSlice();
	// Test::testCF();

}catch (Exception $e){
	print_r($e);
	echo $e->getMessage()."\n";

}
exit;
/**test uuid

exit;
//*/
/*
 $keyID = '1';
 echo 'print_r($t->get_list($keyID))'."\n";
 print_r($t->get_list($keyID));
 exit;
 //*/

/*
 echo 'print_r($t->get_range())'."\n";
 print_r($t->get_range(null,null,3));


 exit;
 //*/

//* test get cout
echo '$t->get_count($newid) = '.$t->get_count($newid)."\n";
//*/
$now = time();
$date= date($now);
try{
	for($u=1;$u<10;++$u){



		$data=array();
		//$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,'node','ns');
		//$data['id'] = $id;
		$data['user'] = $u;
		$data['msg']="time $now";


		//$id = UUID::generate(UUID::UUID_TIME,UUID::FMT_STRING,'node','ns');
		//$data[$id] = array('dfdf','dfd');
		//echo "$u id: $id ".$t->_put($id,$data)."\n";
		//$t->remove($u);
		// echo "$newid $u id:  ".$t->put_super($data,$newid)."\n";

		$ret[] = $data;

	}
	//print_r($t->putmulti_super($ret,$newid));
}
catch (Exception $e){
	print_r($e);
	echo $e->getMessage()."\n";

}
//*
echo 'print_r($t->get($newid,null,false,3))=';
print_r($t->get($newid,null,false,3));
echo 'print_r($t->get($newid,null,true,3))=';
print_r($t->get($newid,null,true,3));
echo 'print_r($t->get($newid)=';
print_r($t->get($newid));
exit;
/*/
 echo 'print_r($t->get_range())'."\n";
 print_r($t->get_range(null,null,3));
 echo 'print_r($t->get_range(10))'."\n";
 print_r($t->get_range(5,null,3));
 exit;
 //*/
//* test get cout
echo '$t->get_count($newid) = '.$t->get_count($newid)."\n";
echo '$t->get_count(1,"7fffffff-7d1c-11df-9b94-6e6f64650000") = '.$t->get_count(1,'7fffffff-7d1c-11df-9b94-6e6f64650000')."\n";