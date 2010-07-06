<?php

/**
 *
 */
class TTable{
	const    SV_PRE='@';//名称含该值的时候，value需要序列化


	const    IDX_UID =  'u';
	const    IDX_ITEM_GROUP = '_v';

	const    MASTER_SERVER = 'master';
	const    SLAVE_SERVER = 'Slave';

	public    $type ;


	public function TTable($needSV=true,$only_read=false)
	{
		$this->needSV= $needSV;
		$this->type = $only_read?self::SLAVE_SERVER:self::MASTER_SERVER;

	}

	protected function before_save(&$data)
	{
		if(!$this->needSV || $data == null)
			return;
		foreach($data as $k=>$v){
			if(is_array($v)||is_object($v)){
				$data[$k] = self::SV_PRE.json_encode($v);
			}
		}
	}

	protected function after_get(&$data)
	{
		if(!$this->needSV || $data == null)
			return;
		foreach($data as $k=>$v){
			if($v && substr_compare($v,self::SV_PRE,0,1,false)==0){
				try{
					$data[$k] = json_decode(substr($v,1),true);
				}catch (Exception $e){
				}
			}
		}
	}


	/**
	 * 保存单条物品，返回id
	 * @param $data
	 * @return unknown_type
	 */
	public function  put($data,$uid=null,$item_group=null)
	{
		$this->before_save($data);
		$id = $data['id'];
		if($uid)
			$data[self::IDX_UID] = $uid;
		else{
			$uid = $data['u'];
		}
		if($item_group){
			$data[self::IDX_ITEM_GROUP] = $uid.':'.$item_group;
		}


		$t = BasicConfig::get_mainTT($uid,$this->type);
		if($id){
			$t->putCat($id,$data);
		}else{
			$id = $t->putCat(null,$data);
		}
		return $id;
	}

	/**
	 * 保存多个物品，返回id
	 * @param $data
	 * @return unknown_type
	 */
	public function  multiput($datas,$uid='',$item_group=null)
	{
		foreach($datas as $k=>$data){
			$id[$k] = $this->put($data,$uid,$item_group);
		}
		return $id;
	}


	/**
	 * 按id获取
	 * @param $id
	 * @return unknown_type
	 */
	public function getbyid($uid,$id)
	{
		$t = BasicConfig::get_mainTT($uid,$this->type);
		$r = $t->get($id);
		$this->after_get($r);
		$r['id'] = $id;
		return $r;
	}


	/**
	 * 删除
	 * @param $uid
	 * @param $ids
	 * @return unknown_type
	 */
	public function remove($uid,$ids)
	{
		$t = BasicConfig::get_mainTT($uid,$this->type);
		$t->out($ids);
	}

	/**
	 * 按uid获取,获取某一类型
	 * @param $id
	 * @return unknown_type
	 * @param $uid
	 * @param $item_group
	 * @return unknown_type
	 */

	public function get($uid,$item_group='')
	{
		$t = BasicConfig::get_mainTT($uid,$this->type);
		$q=$t->getQuery();
                $q->setLimit(100000);
		$q->addCond(self::IDX_UID,TokyoTyrant::RDBQC_STREQ,$uid);
		if($item_group){
			$q->addCond(self::IDX_ITEM_GROUP,TokyoTyrant::RDBQC_STREQ,$uid.':'.$item_group);
		}
		$res = $q->search();
		if(!is_array($res))
                    return $res;
		$ret = array();
		foreach($res as $k=>$v){
			$this->after_get($v);
			$v['id'] = $k;
			$ret[]= $v;
		}
		return $ret;
	}
}
