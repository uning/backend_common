<?php

/**
 *
 */
class TTkv{
	public    $type ;


	public function TTKv($only_read=false)
	{
		$this->needSV= $needSV;
		$this->type = $only_read?self::SLAVE_SERVER:self::MASTER_SERVER;

	}

	/**
         * 增加一个字段的值
	 * @param $data
	 * @return the new value
	 */
	public function  incr($uid,$field,$num=1)
	{
                $k = "$uid:$field";
		$t = BasicConfig::get_statTT($uid,$this->type);
		$ret = $t->add($k,$data);
		return $ret;
	}

	public function remove($uid,$fields=array())
	{
		$t = BasicConfig::get_statTT($uid,$this->type);
		foreach($fields as $f){
			$keys []= $uid.':'.$f;
		}
		$t->out($keys);
	}

	/**
	 * 按uid获取,获取某一类型
	 * @param $id
	 * @return unknown_type
	 * @param $uid
	 * @param $item_group
	 * @return unknown_type
	 */
	public function get($uid,$fields=array())
	{
		$t = BasicConfig::get_statTT($uid,$this->type);
		if(!$fields){
                     $keys = $t->fwmKeys($uid);
		}
		foreach($fields as $f){
			$keys []= $uid.':'.$f;
		}
		return $t->get($keys);
	}
}
