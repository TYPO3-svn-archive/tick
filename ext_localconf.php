<?php

$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php'] = t3lib_extMgm::extPath($_EXTKEY).'xclasses/class.ux_t3lib_db.php';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog']['tick'] = 'EXT:tick/classes/class.tx_tick_log.php:tx_tick_log->devLog';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog']['tick'] = 'EXT:tick/classes/class.tx_tick_log.php:tx_tick_log->sysLog';

$baseConfArr = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['tick']);
if (is_array($baseConfArr)) {
	require_once t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_tick_log.php';
	tx_tick_log::$minSysLogSeverity = $baseConfArr['minSysLogSeverity'];
	tx_tick_log::$minDevLogSeverity = $baseConfArr['minDevLogSeverity'];
}