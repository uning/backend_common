<?php
include_once("httpsqs_client.php");

class AsyncQueue
{
	public $qname;
	protected $_host;
	protected $_port;
	public $hs;
	function AsyncQueue($qname='defque',$host='localhost',$port='1218')
	{
		$this->hs = new httpsqs;	
		$this->_host=$host;
		$this->_port=$port;
		$this->qname=$qname;
	}

	function get()
	{
		$r = $this->hs->get($this->_host,$this->_port,'utf-8',$this->qname);
		$sr = json_decode($r,true);
		if($sr)
			return $sr;
		return $r;
	}
	function put($data)
	{
		$r = json_encode($data);
		$r = $this->hs->put($this->_host,$this->_port,'utf-8',$this->qname,$r);
	}

	function status()
	{
		$r = $this->hs->status_json($this->_host,$this->_port,'utf-8',$this->qname);
		$sr = json_decode($r,true);
		return $sr;
	}
}
