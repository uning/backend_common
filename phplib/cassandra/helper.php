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
	static private $last_get = null;

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

		 
		if(self::$last_get instanceof CassandraClient )
		return self::$last_get;

		shuffle(self::$connections);
		foreach(self::$connections as $connection) {
			try {
				$transport = $connection['transport'];
				$client    = $connection['client'];

				if(!$transport->isOpen()) {
					$transport->open();
				}


				self::$last_get = $client;
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