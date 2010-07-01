<?php

/*
 一些常用方法
 */
define('ENCODE_BASE', 2101239848);
define('ENCODE_MAGIC', "uning");
class CrabTools
{

	/*
	 *加密字符串，解密decrypt_double_b64
	 */
	static function encrypt_double_b64($str)
	{
		$b1 = rtrim(base64_encode($str), "=");
		return base64_encode($b1 ^ ENCODE_MAGIC);
	}

	/*
	 *解密字符串，decrypt_double_b64
	 */
	static function decrypt_double_b64($str)
	{
		$b1 = base64_decode($str);
		//$bb = exchange_str($b1);
		return base64_decode($b1 ^ ENCODE_MAGIC);
	}

	/*
	 *加密整数到数字
	 */
	static function enc_num_to_num($n, $key=1)
	{
		$t1 = ENCODE_BASE - $n - $key;
		$last = $t1 & 0xFF;
		$offset = $last%22+1;
		$first3 = $t1 >> 8 & 0xFFFFFF;
		$mask = (1 << (31-$offset)) -1;
		$t2 =  (($first3 >> $offset)&$mask | ($first3 <<(24-$offset))&0xFFFFFFFF ) <<8 ;
		$first = $t2>>24 & 0xFF;
		$t2 = $t2 | ($last ^ $first);
		$t3 = ($t2<<28)&0xFFFFFFFF | ($t2>>4 & 0x0FFFFFFF);
		return $t3;
	}

	/*
	 *解密
	 */
	static function dec_num_to_num($n, $key=1)
	{
		$t1 = $n<<4 &0xFFFFFFFF | ($n>>28 & 0xF);
		$first = $t1 >> 24 & 0xFF;
		$last = $t1 & 0xFF;
		$last = $last ^ $first;
		$offset = $last%22 + 1;
		$first3 = $t1>>8 & 0xFFFFFF;
		$mask = (1 << (7+$offset)) -1;
		$t2 = ($first3<<$offset&0xFFFFFFFF | ($first3>>(24-$offset)) & $mask ) << 8 &0xFFFFFFFF | $last;
		$t3 = ENCODE_BASE  -$t2 - $key;
		return $t3;
	}

	// 根据最后一个字节的值，循环移位前面三个字节。
	static function encrypt_number($n, $key)
	{
		return  encrypt_double_b64(pack('i',enc_num_to_num($n, $key)));
	}

	static function decrypt_number($str, $key)
	{
		$arr = unpack('I',decrypt_double_b64($str));
		$n = $arr[1];
		return dec_num_to_num($n,$key);
	}

	/**
	 * dump to file
	 */
	static function mydump($data,$file="/tmp/mydump")
	{
		/*
		 ob_start();
		 var_dump($data);
		 $r=ob_get_contents();
		 ob_end_clean();
		 file_put_contents($file,$r);
		 */
		$dir=dirname($file);
		if( !is_writable($file) && !is_writable($dir))
		return;
		if( file_exists($file) && !is_writable($file))
		return;
		file_put_contents($file,serialize($data));
		self::myprint($data,$file.'.read');
	}
	
     static function myload($file="/tmp/mydump")
	 {
	
		return @unserialize(file_get_contents($file));
	

	}

	/**
	 *
	 *
	 */
	static function myprint($data,$file="/tmp/myprint")
	{
		$dir=dirname($file);
		if( !is_writable($file) && !is_writable($dir))
		return;
		if( file_exists($file) && !is_writable($file))
		return;
		file_put_contents($file,print_r($data,true));
	}

	/**
	 *
	 *  将逗号分隔的字符串转化为array。count指定切分范围，如 1,2,3,4,5,6按count=2分成 [1,2]  [3,4], [5,6]
	 *  其中各数组第一个数值作为key，剩余作为vaule，如果多个，如[1,2,3]，则为 array(1=> array(2,3) )
	 *
	 */
	static public function numpair_comma_from_string($string, $count=2)
	{
		if(!$string || $string == '')
		{
			return array();
		}
		$arr=explode(',',$string);
		$c=count($arr);
		if($c > 1)
		{
			for($i=0;$i<$c;$i+=$count)
			{
				if($count == 2)
				$pair[$arr[$i]]=$arr[$i+1];
				else
				{
					for($j=1;$j<$count;$j++)
					$pair[$arr[$i]][]=$arr[$i * $count+$j];
				}
			}
		}
		else
		{
			$pair = array();
		}
		return $pair;
	}

	/**
	 * 将数组转为逗号分隔
	 *
	 */
	static public function numpair_comma_to_string($pairs, $count=2)
	{
		if(!$pairs || count($pairs) == 0)
		{
			return '';
		}
		foreach ($pairs as $k=>$v)
		{
			if($v && $k)
			{
				if($ret)
				$ret.=',';
				if(is_array($v))
				{
					$ret.="$k," . implode(',', $v);
				}
				else
				{
					$ret.="$k,$v";
				}
			}
		}
		return $ret;
	}
}
