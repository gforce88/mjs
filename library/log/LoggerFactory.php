<?php
require_once 'log/Logger.php';
class LoggerFactory {
	private static $sysLogger = null;
	private static $tropoLogger = null;
	
	public static function getSysLogger() {
		if (self::$sysLogger == null) {
			self::$sysLogger = new Logger ( Zend_Registry::get ( 'SYS_LOGGER' ) );
		}
		return self::$sysLogger;
	}
	public static function getTropoLogger() {
		if (self::$tropoLogger == null) {
			self::$tropoLogger = new Logger ( Zend_Registry::get ( 'TROPO_LOGGER' ) );
		}
		return self::$tropoLogger;
	}
}

