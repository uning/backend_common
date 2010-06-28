<?php
require_once 'config.php';
CassandraConn::add_node('localhost', 9160);


//测试CT_TimeUUIDType 排序
$t = new CassandraCF('Keyspace1', 'Standard1',false,CassandraCF::CT_BytesType);
$t = new CassandraCF('Keyspace1', 'StandardByLongType',false,CassandraCF::CT_LongType);
$t = new CassandraCF('Keyspace1', 'StandardByUUID1',false,CassandraCF::CT_TimeUUIDType); // ColumnFamily

//print_r($t->get(1150));
$keyID = 'PandraTestUUID1';
echo 'print_r($t->get_list($keyID))'."\n";
print_r($t->get_list($keyID));

echo 'print_r($t->get_range())'."\n";
print_r($t->get_range(null,null,10));
//$date= date(time());
exit;
try{
for($u=1;$u<20;++$u){
	$data['id'] = "$u";
	$data['name'] = " name user $u";
	$data['insert_at'] = "$u";
   echo "$u id: ".$t->put($data)."\n";
}
}
catch (Exception $e){
	print_r($e);
	echo $e->getMessage()."\n";
	
}
echo 'print_r($t->get_list(1))'."\n";
print_r($t->get_list(1));

echo 'print_r($t->get_range())'."\n";
print_r($t->get_range(null,null,3));

echo 'print_r($t->get_range(10))'."\n";
print_r($t->get_range(10,null,3));
