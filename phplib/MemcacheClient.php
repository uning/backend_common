<?php
/**
 * 
 * @author uning
 * just a wapper class of Memcache which comptible with Memcached
 */

class MemcacheClient  extends Memcache
{
	/**
	 * 
	 * @param unknown_type $servers
	 * @return unknown_type
	 */
	function addServers(&$servers)
	{
		foreach($servers as $h){
			$inst->addServer($h[0],$h[1]);
	 }
	}

	function getResultMessage()
	{
	}


	function setOption()
	{
	}


	function getOption()
	{
	}
}

