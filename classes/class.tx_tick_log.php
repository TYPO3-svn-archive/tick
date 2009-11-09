<?php

class tx_tick_log {
	
	public static $minSysLogSeverity;
	
	public static $minDevLogSeverity;
	
	public static $labels = array(
		'-1' => 'OK',
	    '0' => 'Info',
     	'1' => 'Notice', 
     	'2' => 'Warning',
     	'3' => 'Error',
		'4' => 'Fatal Error', 
	);

	/**
     * Developer log
     *
     * Log array keys:
     * - 'msg'			string	Message (in english).
     * - 'extKey'    	string	Extension key (from which extension you are calling the log)
     * - 'dataVar'		array	Additional data you want to pass to the logger.
     * - 'severity'		integer 
     * 
     * Severity: 
     * - -1: "OK" message
     * - 0: info
     * - 1: notice 
     * - 2: warning
     * - 3: fatal error 
     * 
     * @param   array	log data array
     * @return 	void   
     * @author	Fabrizio Branca <mail@fabrizio-branca.de
     */
	public static function devLog(array $logArr) {
		if ($logArr['severity'] >= self::$minDevLogSeverity) {
			if ($GLOBALS['TT'] instanceof t3lib_timeTrack) {
				$message = sprintf('[%1$s] %2$s: %3$s', $logArr['extKey'], self::$labels[$logArr['severity']], $logArr['msg']);
				$GLOBALS['TT']->setTSlogMessage($message, $logArr['severity']);
			}
		}	
	}
	
	/**
	 * Syslog userfunction 
	 * 
	 * Log array keys:
     * - 'msg'			string	Message (in english).
     * - 'extKey'    	string	Extension key (from which extension you are calling the log)
     * - 'backTrace'	array	backtrace
     * - 'severity'		integer 
     * 
     * Severity:     
     * - 0: info
     * - 1: notice 
     * - 2: warning
     * - 3: error
     * - 4: fatal error
	 *
	 * @param 	array	log data array
	 * @return 	void 
	 */
	public static function sysLog(array $params) {
		if (!$params['initLog']) {
			// the "severity" is available only in TYPO3 versions > 4.3 (Or add patch this to your sources manually)
			if ((!isset($params['severity']) || $params['severity'] >= self::$minSysLogSeverity)) {
				if ($GLOBALS['TT'] instanceof t3lib_timeTrack) {
					$message = sprintf('[%1$s] %2$s: %3$s', $params['extKey'], self::$labels[$params['severity']], $params['msg']);
					$GLOBALS['TT']->setTSlogMessage($message, $params['severity']);
				}
			}
		}
	}
}
