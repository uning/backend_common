<?php

/**
 *
 */
class TTGenid{

	const IDX_F='@';

	/**
	 * 生成id，返回id
	 * @param $data 数组
	 * @return unknown_type
	 */
	public function  genid($data,$field='pid')
	{

		if(is_array($data)){
			$key = $data[$field];

		}else{
			$key=$data;
			$data =array($field=>$key);
		} 

		$t = TT::get_genidTT();
		$q=$t->getQuery();
		$q->addCond(self::IDX_F,TokyoTyrant::RDBQC_STREQ,$key);
		$res = $q->search();
		if($res){
			foreach($res as $k=>$v){
				$v['id']=$k;
				return $v;
			}
		}
		$data[self::IDX_F]=$key;
		$data['id'] = $t->put(null,$data);
		return $id;
	}

	/**
	 */
	public function  getid($data,$field='pid')
	{

		if(is_array($data)){
			$key = $data[$field];

		}else{
			$key=$data;
			$data =array($field=>$key);
		} 

		$t = TT::get_genidTT();
		$q=$t->getQuery();
		$q->addCond(self::IDX_F,TokyoTyrant::RDBQC_STREQ,$key);
		$res = $q->search();
		if($res){
			foreach($res as $k=>$v){
				$v['id']=$k;
				return $v;
			}
		}
		$data[self::IDX_F]=$key;
		$data['id'] = $t->put(null,$data);
		return $id;
	}

}
