<?php
/**
 * Fuel patch for Google App Engine.
 *
 * @package    Fuel
 * @target_ver FuelPHP 1.6
 * @version    0.1
 * @author     Isao Tanji
 * @license    MIT License
 */

namespace Fuel\Core;


/**
 * Log core class facade for the Syslog composer package.
 *
 * This class will provide the interface between the Fuel v1.x class API
 * for Google App Engine.
 */
class Log
{
	/**
	 * container for the this class instance
	 */
	protected static $logger = null;

	/**
	 * Copy of the Monolog log levels
	 */
	protected static $levels = array(
		100 => 'DEBUG',
		200 => 'INFO',
		250 => 'NOTICE',
		300 => 'WARNING',
		400 => 'ERROR',
		500 => 'CRITICAL',
		550 => 'ALERT',
		600 => 'EMERGENCY',
	);
	
	/**
	 * Mapping Monolog level a pair syslog level
	 */
	protected static $syslog_levels = array(
		100 => LOG_DEBUG,
		200 => LOG_INFO,
		250 => LOG_NOTICE,
		300 => LOG_WARNING,
		400 => LOG_ERR,
		500 => LOG_CRIT,
		550 => LOG_ALERT,
		600 => LOG_EMERG,
	);

	/**
	 * Initialize the class
	 */
	private static function _init()
	{
		static::$logger = new Log();
	}

	/**
	 * Return the monolog instance
	 */
	public static function instance()
	{
		// return the created instance
		
		if (empty(static::$logger)) {
			static::$logger = new Log();
		}
		
		return static::$logger;
	}
	
	
	public static function log($level, $message) {
		syslog(static::$syslog_levels[$level], $message);
	}
	

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function info($msg, $method = null)
	{
		return static::write(\Fuel::L_INFO, $msg, $method);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function debug($msg, $method = null)
	{
		return static::write(\Fuel::L_DEBUG, $msg, $method);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function warning($msg, $method = null)
	{
		return static::write(\Fuel::L_WARNING, $msg, $method);
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function error($msg, $method = null)
	{
		return static::write(\Fuel::L_ERROR, $msg, $method);
	}


	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @access	public
	 * @param	int|string	the error level
	 * @param	string	the error message
	 * @param	string	information about the method
	 * @return	bool
	 */
	public static function write($level, $msg, $method = null)
	{
		// defined default error labels
		static $oldlabels = array(
			1  => 'Error',
			2  => 'Warning',
			3  => 'Debug',
			4  => 'Info',
		);

		// get the levels defined to be logged
		$loglabels = \Config::get('log_threshold');

		// bail out if we don't need logging at all
		if ($loglabels == \Fuel::L_NONE)
		{
			return false;
		}

		// if it's not an array, assume it's an "up to" level
		if ( ! is_array($loglabels))
		{
			$a = array();
			foreach (static::$levels as $l => $label)
			{
				$l >= $loglabels and $a[] = $l;
			}
			$loglabels = $a;
		}

		// if profiling is active log the message to the profile
		if (\Config::get('profiling'))
		{
			\Console::log($method.' - '.$msg);
		}

		// convert the level to monolog standards if needed
		if (is_int($level) and isset($oldlabels[$level]))
		{
			$level = strtoupper($oldlabels[$level]);
		}
		if (is_string($level))
		{
			if ( ! $level = array_search($level, static::$levels))
			{
				$level = 250;	// can't map it, convert it to a NOTICE
			}
		}

		// make sure $level has the correct value
		if ((is_int($level) and ! isset(static::$levels[$level])) or (is_string($level) and ! array_search(strtoupper($level), static::$levels)))
		{
			throw new \FuelException('Invalid level "'.$level.'" passed to logger()');
		}

		// do we need to log the message with this level?
		if ( ! in_array($level, $loglabels))
		{
			return false;
		}

		// log the message
		syslog(static::$syslog_levels[$level], (empty($method) ? '' : $method.' - ').$msg);

		return true;
	}

}
