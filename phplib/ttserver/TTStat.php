<?php

/**
 *
 */
class TTStat{
	public    $type ;

	public function TTStat($only_read=false)
	{
	

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
	 * 按uid获取,获取某几个值或全部
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
		$res = $t->get($keys);
                foreach($res as $k=>$v){
                   list($uid,$field) = explode(':',2);
                   self::checkBinary(&$v);
                   $ret[$field]=$v;
                }
	}
	protected static function checkBinary(&$data) {
		if( preg_match('/((?![\x20-\x7E]).)/', $data)){
                    //just int
                    $data = unpack('I',$data);
                    $data = $data[0];
                }
	}
}

