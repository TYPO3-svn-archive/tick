<?php

if (TYPO3_MODE == 'FE') {
	
	if ($_COOKIE['tick'] || $_GET['tick'] || $_GET['TSFE_ADMIN_PANEL']['display_performance']) {

		$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php'] = t3lib_extMgm::extPath($_EXTKEY).'xclasses/class.ux_t3lib_db.php';
		
		// this does not work in TYPO3 4.3.3
		// $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsfebeuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY).'xclasses/class.ux_t3lib_tsfebeuserauth.php';
		
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog']['tick'] = 'EXT:tick/classes/class.tx_tick_log.php:tx_tick_log->devLog';
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog']['tick'] = 'EXT:tick/classes/class.tx_tick_log.php:tx_tick_log->sysLog';
		
		$baseConfArr = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['tick']);
		if (is_array($baseConfArr)) {
			require_once t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_tick_log.php';
			tx_tick_log::$minSysLogSeverity = $baseConfArr['minSysLogSeverity'];
			tx_tick_log::$minDevLogSeverity = $baseConfArr['minDevLogSeverity'];
		}
		
		// including pear classes
		$success = ini_set('include_path', PATH_typo3conf.'ext/tick/lib/pear/' .PATH_SEPARATOR. ini_get('include_path'));
		
		if ($success === false) {
			// throw new Exception('Error while setting include_path');
		}
		
		require_once t3lib_extMgm::extPath($_EXTKEY).'xclasses/class.ux_t3lib_timetrack.php';
		
		$oldTT = $GLOBALS['TT'];

			// overwrite the current timetrack object with this new one
		$GLOBALS['TT'] = new ux_t3lib_timeTrack;
		$GLOBALS['TT']->start();

			// faking what happened before (to keep the stack valid)
		$GLOBALS['TT']->push('');
		$GLOBALS['TT']->push('Include config files');
		$GLOBALS['TT']->push('Loading localconf.php extensions');
		
	}
	
}
