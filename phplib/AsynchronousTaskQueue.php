<?php

/*
 * 
 * 异步任务,提供一个任务储存，仅存储任务运行所需参数
 * 通过不同队列名，提高优先级
 * 如发邮件、发消息、写log、异步更新数据库(不需要实时)完成的耗时操作
 *
 */

class  ATask{
	public  $name;
	public  $params=array();
};

class  AsynchronousTaskQueue{

	/**
	 * add a asynchronous task to a queue
	 *
	 * @param Atask $task
	 * @param string $queue_name
	 *
	 */
	static public function add($task,$queue_name='defaultq')
	{
		$q=ServerConfig::connect_memcached('queue_memcacheq');
		if($q)
		$q->set($queue_name,serialize($task));
	}

	/**
	 * 
	 * get a task from queue
	 * called in worker client
	 *
	 * @param string $queue_name
	 * @return ATask or something support serializing 
	 *
	 */

	static public function get($queue_name='defaultq')
	{
	    
		$q=ServerConfig::connect_memcached('queue_memcacheq');
		if($q)
		$m=$q->get($queue_name);
		return unserialize($m);
	}

}

