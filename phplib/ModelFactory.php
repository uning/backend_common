<?php

/**
 * 
 */

require_once LIB_ROOT.'DBModel.php';

class ModelFactory
{
	/**
	 * dynami get a model Obj
	 */

	public static function getModel($name,$db=null)
	{
		if(!class_exists($name))
		{
			$file=MODEL_ROOT.$name.'.php';
			if(!file_exists($file)){
				//Logger::error(__METHOD__."Use basic $file, file do not exists");
				return new DBModel($name);
			 }
			else
				require_once $file;
		}
		$ret =new $name;
		if($db)
			$ret->setDb($db);
		return $ret;
	}
	


		
		/**
		* aux functiong for get Model 
		*
		*/
    public static function Invitation($db=null){return self::getModel('Invitation',$db);}
    public static function Item($db=null){return self::getModel('Item',$db);}
    public static function Machine($db=null){return self::getModel('Machine',$db);}
    public static function MailArch($db=null){return self::getModel('MailArch',$db);}
    public static function Mail($db=null){return self::getModel('Mail',$db);}
    public static function OldPresent($db=null){return self::getModel('OldPresent',$db);}
    public static function Order($db=null){return self::getModel('Order',$db);}
    public static function Present($db=null){return self::getModel('Present',$db);}
    public static function Taskopstat($db=null){return self::getModel('Taskopstat',$db);}
    public static function UnionTask($db=null){return self::getModel('UnionTask',$db);}
    public static function UnionUser($db=null){return self::getModel('UnionUser',$db);}
    public static function Union($db=null){return self::getModel('Union',$db);}
    public static function UserAccount($db=null){return self::getModel('UserAccount',$db);}
    public static function UserProfile($db=null){return self::getModel('UserProfile',$db);}
    public static function UserTask($db=null){return self::getModel('UserTask',$db);}
    public static function Userop($db=null){return self::getModel('Userop',$db);}
    public static function BestRecord($db=null){return self::getModel('BestRecord',$db);}
    public static function Scene($db=null){return self::getModel('Scene',$db);}
	public static function Task($db=null){return self::getModel('Task',$db);}

}



