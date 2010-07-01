<?php
if(!defined('LOG_PATH') ){
	echo("error:zend not defined");
	exit();
}

require_once 'Zend/Log.php';
require_once 'Zend/Log/Writer/Stream.php';

class Logger {
	/**
	 * Array of Zend Log instances
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Holds logical name of the default logger
	 * @var string
	 */
	private static $defaultLogger = null;


	public static function initDefault($logFilePath){
		$writer = new Zend_Log_Writer_Stream($logFilePath);
		$format = '[%timestamp%] [%priorityName% %priority%] %message%' . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		Logger::registerLogger('default', $writer ,null, true);

	}

	/**
	 * Returns a logger and ensure that there is only one instance created in the process
	 *
	 * @access public
	 * @static
	 * @param Zend_Log_Writer Child of Zend_Log_Writer
	 * @param boolean $isDefault Indicates if this logger is the default one to use
	 *
	 */
	public static function registerLogger($loggerName, $zendWriter = null, $zendFilter=null, $isDefault = false)
	{
		if (!isset(self::$instances[$loggerName])) {
			$logger= new Zend_Log($zendWriter);
			if($zendFilter){
				$logger->addFilter($zendFilter);
			}
			self::$instances[$loggerName]  = $logger;

			//$filter = new Zend_Log_Filter_Priority(Zend_Log::CRIT);
			//

		}

		// If we weren't told this is the default logger yet none exists then force it
		if (!self::getDefaultLoggerName() AND !$isDefault) $isDefault = true;

		if ($isDefault) {
			if (isset(self::$defaultLogger)) {
				return;
				//throw new Exception('Default logger is already defined');
			}
			self::$defaultLogger = $loggerName;
		}
	}

	/**
	 * Useful for debugging and caching
	 *
	 * @static
	 * @return array Collection of Zend_Log descendents.
	 *
	 */
	public static function getLoggerInstances()
	{
		return self::$instances;
	}

	/**
	 * Useful for caching
	 *
	 * @static
	 * @return array Collection of Zend_Log descendents.
	 *
	 */
	public static function setLoggerInstances($instances)
	{
		self::$instances = $instances;
	}

	/**
	 * Returns the name of the default logger
	 *
	 * @static
	 * @param boolean $throwIfNull Indicater if we should throw an exception if the default logger
	 * is null
	 * @return string Logical name of the default logger otherwise false
	 *
	 */
	public static function getDefaultLoggerName($throwIfNull = true)
	{
		if (is_null(self::$defaultLogger) AND $throwIfNull) return false;
		return self::$defaultLogger;
	}

	/**
	 * Sets name of the default logger (useful for restoring from cache)
	 *
	 * @param string $loggerName Name of default logger
	 *
	 */
	public static function setDefaultLoggerName($loggerName)
	{
		self::$defaultLogger = $loggerName;
	}

	/**
	 * Unregister's a logger
	 *
	 * @static
	 * @access public
	 * @param string $loggerName Logical name of the logger
	 */
	public static function unregisterLogger($loggerName)
	{
		if (!in_array($loggerName,array_keys(self::$instances))) {
			return false;
		}

		unset(self::$instances[$loggerName]);
		if (self::getDefaultLoggerName() == $loggerName) {
			self::$defaultLogger = null;
		}
	}

	/**
	 * Add a filter that will be applied before all log writers.
	 *
	 * Before a message will be received by any of the writers, it must be accepted by all filters
	 * added with this method.
	 *
	 * @param Zend_Log_Filter_Interface $filter
	 * @param string $loggerName Logical name of logger to add filter to
	 *
	 */
	public static function addFilter($filter, $loggerName = null)
	{
		if (is_null($loggerName)) $loggerName = self::getDefaultLoggerName();
		$logger = self::$instances[$loggerName];
		$logger->addFilter($filter);
		self::$instances[$loggerName] = $logger;
	}

	/**
	 * Add a writer.
	 *
	 * A writer is responsible for taking a log message and writing it out to storage.
	 *
	 * @param Zend_Log_Writer $writer Child of Zend_Log_Writer
	 * @param string $loggerName Logical name of logger to add writer to
	 *
	 */
	public static function addWriter($writer, $loggerName = null)
	{
		if (is_null($loggerName)) $loggerName = self::getDefaultLoggerName();

		$logger = self::$instances[$loggerName];
		$logger->addWriter($writer);
		self::$instances[$loggerName] = $logger;
	}

	/**
	 * Add a custom priority
	 *
	 * @param string $loggerName Logical name of the logger to add priority to
	 * @param string $name Priority name
	 * @param integer $priority Value for the priority
	 *
	 */
	public static function addPriority($name, $priority, $loggerName = null)
	{
		if (is_null($loggerName)) $loggerName = self::getDefaultLoggerName();
		$logger = self::$instances[$loggerName];
		$logger->addPriority($name, $priority);
		self::$instances[$loggerName] = $logger;
	}

	/**
	 * Set an extra item to pass to the log writers.
	 *
	 * @param string $loggerName Logical name of the logger to set event for
	 * @param unknown_type $name Name of the field
	 * @param unknown_type $value Value of the field
	 *
	 */
	public static function setEventItem($name, $value, $loggerName = null)
	{
		if (is_null($loggerName)) $loggerName = self::getDefaultLoggerName();

		$logger = self::$instances[$loggerName];
		$logger->setEventItem($name, $value);
		self::$instances[$loggerName] = $logger;
	}

	/**
	 * Gets the requested logger
	 *
	 * I can't think of a good reason to need this other than possibly handing the logger off to
	 * some ZF compatible library/app.
	 *
	 * @param string $loggerName Logical name of the logger to get
	 * @return Zend_Log Instance of Zend_Log requested.
	 */
	public static function &getLogger($loggerName = null)
	{
		if (is_null($loggerName)) $loggerName = self::getDefaultLoggerName();

		return self::$instances[$loggerName];
	}
	/**
	 * Logs a message
	 *
	 * For reference here are the default log levels available as class constants in Zend_Log:
	 *
	 * NOTE: see the helper function below that make logging direct to a log level easier.
	 *
	 * @param string $message Message to log
	 * @param string $loggerName Logical name of logger instance
	 * @param int $priority Log priority
	 *
	 */
	public static function log($message, $priority = Zend_Log::INFO, $loggerName = null)
	{
		if (is_null($loggerName)) $loggerName = self::getDefaultLoggerName();
		if (!$loggerName) {
			$file=LOG_ROOT.date('Y-m-d',time()).'.log';
			self::initDefault($file);
			$loggerName = self::getDefaultLoggerName();
		}
		if (!$loggerName) {
			throw new Exception('log() was not given a logger name and no default logger exists');
		}

		if (!array_key_exists($loggerName, self::$instances)) {
			throw new Exception("Logger $loggerName is not defined. Registered loggers: " . implode(',',array_keys(self::$instances)));
		}
		$logger = self::$instances[$loggerName];
		$logger->log($message, $priority);
	}
	public static function error($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::ERR;
		self::log($message, $logLevel, $loggerName);
	}
	public static function alert($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::ALERT;
		self::log($message, $logLevel, $loggerName);
	}
	public static function crit($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::CRIT;
		self::log($message, $logLevel, $loggerName);
	}
	public static function emerge($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::EMERG;
		self::log($message, $logLevel, $loggerName);
	}
	public static function warn($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::WARN;
		self::log($message, $logLevel, $loggerName);
	}

	public static function debug($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::DEBUG;
		self::log($message, $logLevel, $loggerName);
	}

	public static function notice($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::NOTICE;
		self::log($message, $logLevel, $loggerName);
	}
	public static function info($message,  $loggerName = null)
	{
		$logLevel = Zend_Log::INFO;
		self::log($message, $logLevel, $loggerName);
	}

	/* php 5.3 and above
	 public static function __callStatic($methodCalled, $arguments)
	 {
	 switch ($methodCalled) {
	 case 'emerge':
	 $logLevel = Zend_Log::EMERG;
	 break;
	 case 'alert':
	 $logLevel = Zend_Log::ALERT;
	 break;
	 case 'crit':
	 $logLevel = Zend_Log::CRIT;
	 break;
	 case 'error':
	 $logLevel = Zend_Log::ERR;
	 break;
	 case 'warn':
	 $logLevel = Zend_Log::WARN;
	 break;
	 case 'notice':
	 $logLevel = Zend_Log::NOTICE;
	 break;
	 case 'info':
	 $logLevel = Zend_Log::INFO;
	 break;
	 case 'debug':
	 default:
	 $logLevel = Zend_Log::DEBUG;
	 }
	 if (!array_key_exists(1,$arguments)) $arguments[1] = null;
	 self::log($arguments[0], $logLevel, $arguments[1]);
	 }
	 */
}

?>
