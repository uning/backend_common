<?php
define('CASSANDRA_ROOT',dirname(__FILE__));

$GLOBALS['THRIFT_ROOT'] = CASSANDRA_ROOT. '/thrift/';
//require_once CASSANDRA_ROOT.'/thrift/autoload.php';
<<<<<<< HEAD:phplib/cassandra/config.php
require_once CASSANDRA_ROOT.'/thrift/Thrift.php';
=======
require_once CASSANDRA_ROOT.'/thrift/thrift.php';
>>>>>>> 9b2350d9a285f55773f5f179fcc2def60327a700:phplib/cassandra/config.php
require_once $GLOBALS['THRIFT_ROOT'].'/packages/cassandra/Cassandra.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TTransport.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TSocket.php';
require_once CASSANDRA_ROOT.'/thrift/protocol/TBinaryProtocol.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TFramedTransport.php';
require_once CASSANDRA_ROOT.'/thrift/transport/TBufferedTransport.php';




include_once CASSANDRA_ROOT. '/phpcassa.php';
<<<<<<< HEAD:phplib/cassandra/config.php
include_once CASSANDRA_ROOT. '/uuid.php';
=======
include_once CASSANDRA_ROOT. '/uuid.php';
>>>>>>> 9b2350d9a285f55773f5f179fcc2def60327a700:phplib/cassandra/config.php
