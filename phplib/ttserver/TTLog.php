<?php

/**
 *
 */
class TTLog{



	/**
	 * 保存单条物品，返回id
	 * @param $data
	 * @return unknown_type
	 */
	public function  record($data)
	{
		$t = BasicConfig::get_logTT($uid,$this->type);
		$t->putCat(null,$data);
	}
}
