<?php

/**
 * Overwriting t3lib_DB in order to add some calls to the tick function
 */
class ux_t3lib_DB extends t3lib_DB {
	
	public $trace = true;
	
	/**
	 * Displays the "path" of the function call stack in a string, using debug_backtrace
	 *
	 * @return	string
	 */
	public static function debug_trail()	{
		$trail = debug_backtrace();
		$trail = array_reverse($trail);
		array_pop($trail);

		$path = array();
		foreach($trail as $dat)	{
			
			if (isset($dat['args']) && is_array($dat['args'])) {
				$arguments = self::argumentArrayToString($dat['args']);
				$arguments = '('.$arguments.')';
			}
			$path[] = $dat['class'].$dat['type'].$dat['function'].$arguments.'#'.$dat['line'];
		}

		return implode(' // ',$path);
	}
	
	public static function argumentArrayToString(array $arguments, $depth=0) {
		$tmpArray = array();
		foreach ($arguments as $argument) {
			if (is_object($argument)) {
				$tmpArray[] = get_class($argument);
			} elseif (is_string($argument)) {
				$tmp = (strlen($argument) < 200) ? $argument : (substr($argument, 0, 20) . '...' . substr($argument, -20));
				$tmp = str_replace("\n", '[break]', $tmp);
				$tmp = "'".$tmp."'";
				$tmpArray[] = $tmp;
			} elseif (is_numeric($argument)) {
				$tmpArray[] = (string)$argument;
			} elseif (is_bool($argument)) {
				$tmpArray[] = $argument ? 'true' : 'false';
			} elseif (is_array($argument) && ($depth < 2)) {
				$tmpArray[] = '['.self::argumentArrayToString($argument, $depth+1).']';
			} else {
				$tmpArray[] = gettype($argument);
			}
		}
		return implode(', ', $tmpArray);
	}
	
	/**
	 * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 47
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE)	{
		$query = $this->INSERTquery($table,$fields_values,$no_quote_fields);
		if (function_exists('tick')) tick($query, '', 'insert_begin', 0, $table, $this->trace ? self::debug_trail() : '');
		$res = mysql_query($query, $this->link);
		if (function_exists('tick')) tick('', '', 'insert_end');
		if ($this->debugOutput)	$this->debug('exec_INSERTquery');
		return $res;
	}

	/**
	 * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 50
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_UPDATEquery($table,$where,$fields_values,$no_quote_fields=FALSE)	{
		$query = $this->UPDATEquery($table,$where,$fields_values,$no_quote_fields);
		if (function_exists('tick')) tick($query, '', 'update_begin', 0, $table, $this->trace ? self::debug_trail() : '');
		$res = mysql_query($query, $this->link);
		if (function_exists('tick')) tick('', '', 'update_end');
		if ($this->debugOutput)	$this->debug('exec_UPDATEquery');
		return $res;
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 40
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_DELETEquery($table,$where)	{
		$query = $this->DELETEquery($table,$where);
		if (function_exists('tick')) tick($query, '', 'delete_begin', 0, $table, $this->trace ? self::debug_trail() : '');
		$res = mysql_query($query, $this->link);
		if (function_exists('tick')) tick('', '', 'delete_end');
		if ($this->debugOutput)	$this->debug('exec_DELETEquery');
		return $res;
	}

	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 * Usage count/core: 340
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')	{
		$query = $this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
		if (function_exists('tick')) tick($query, '', 'select_begin', 0, $from_table, $this->trace ? self::debug_trail() : '');
		$res = mysql_query($query, $this->link);
		if (function_exists('tick')) tick('', '', 'select_end');

		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		if ($this->explainOutput) {
			$this->explain($query, $from_table, $this->sql_num_rows($res));
		}

		return $res;
	}
	
	
}