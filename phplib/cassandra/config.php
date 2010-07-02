<?php
define('CASSANDRA_ROOT',dirname(__FILE__));

$GLOBALS['THRIFT_ROOT'] = CASSANDRA_ROOT. '/thrift/';
//require_once CASSANDRA_ROOT.'/thrift/autoload.php';
require_once CASSANDRA_ROOT.'/thrift/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/packages/cassandra/Cassandra.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TTransport.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TSocket.php';
require_once CASSANDRA_ROOT.'/thrift/protocol/TBinaryProtocol.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TFramedTransport.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TBufferedTransport.php';



<<<<<<< HEAD

include_once CASSANDRA_ROOT. '/phpcassa.php';
include_once CASSANDRA_ROOT. '/uuid.php';
=======
require_once CASSANDRA_ROOT. '/uuid.php';
require_once CASSANDRA_ROOT. '/phpcassa.php';
require_once CASSANDRA_ROOT. '/helper.php';
>>>>>>> 4019c588905308f616e8a78f1b949c36856b0a75
