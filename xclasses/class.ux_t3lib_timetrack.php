<?php

require_once(PATH_t3lib.'class.t3lib_timetrack.php');

/**
 * Tick function 
 * 
 * @param string (optional) message
 * @param int (optional) stack leel
 * @param string|false (optional) sqlQuery type
 * @param int (optional) message type
 * @return void
 */
function tick($str='', $level='', $sqlQuery=false, $messageType=0) {
	$GLOBALS['TT']->tick($str, $level, $sqlQuery, $messageType);
}


class ux_t3lib_timeTrack extends t3lib_timeTrack {
	
	/**
	 * @var int memory usage at start of tick logging
	 */
	protected $tick_startMemoryUsage;
	
	/**
	 * @var array tick log data
	 */
	protected $tick_logData = array();
	
	/**
	 * Extending the start method to initialize ticking
	 * 
	 * @param void
	 * @return void
	 */
	public function start() {
		$this->tick_startMemoryUsage = memory_get_usage();
		
		register_tick_function('tick');
		declare(ticks = 50);
		
		parent::start();
	}
	
	/**
	 * Tick data logger
	 * 
	 * @param string (optional) message
	 * @param int (optional) stack leel
	 * @param string|false (optional) sqlQuery type
	 * @param int (optional) message type
	 * @return void
	 */
	public function tick($str='', $level='', $sqlQuery=false, $messageType=0) {
		$this->tick_logData[] = array(
			/* 'time' 	   => */ $this->mtime(), 
			/* 'memory'    => */ round((memory_get_usage() - $this->tick_startMemoryUsage) / 1024),
			/* 'message'   => */ htmlentities($str),
			/* 'level'     => */ $level,
			/* 'querytype' => */ $sqlQuery,
			/* 'msg_type'  => */ $messageType
		);
	}
	
	/**
	 * Overridden method
	 */
	public function setTSlogMessage($content, $num=0) {
		if (!empty($content)) {
			$this->tick($content, '', false, $num);
		}
		parent::setTSlogMessage($content, $num);
	}
	
	/**
	 * Overridden method
	 */
	public function push($tslabel, $value='') {
		parent::push($tslabel, $value);
		$this->tick($tslabel);
	}
	
	/**
	 * Overridden method
	 */
	/*
	public function pull($content='')  {
		$this->tick('', $this->tsStackLevel . '_end');
		parent::pull($content);
	}
	*/
	
	/**
	 * Class destructor
	 * The sg is generated here
	 * 
	 * @param void
	 * @return void
	 */
	public function __destruct() {
		unregister_tick_function('tick');
		
		require(PATH_typo3conf.'localconf.php');
		$conf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['tick']);
		
		// including pear classes
		ini_set('include_path', t3lib_extMgm::extPath('tick').'lib/pear/' .PATH_SEPARATOR. ini_get('include_path'));
				
		// drawing the graph
		require_once 'Image/Graph.php';
		
		$Graph = Image_Graph::factory('graph', array(array('width' => $conf['svgWidth'], 'height' => $conf['svgHeight'], 'canvas' => 'svg')));
		
		$Font = $Graph->addNew('font', 'Verdana'); 
		$Font->setSize(8);
		$Graph->setFont($Font); 
				
		$Plotarea = $Graph->addNew('plotarea', array('Image_Graph_Axis'));
		
		$memoryDataset = Image_Graph::factory('dataset');
		
		$dbOperationColors = array(
			'select' => 'blue', 
			'insert' => 'green', 
			'delete' => 'red', 
			'update' => 'lightgreen'
		);
		
		$messageTypeColors_default = 'black@0.3';
		$messageTypeColors = array(
			'2' => 'orange@0.3',
			'3' => 'red@0.3'
		);
		
		$internalStack = array();
		
		$thickness = 200;
		
		require_once t3lib_extMgm::extPath('tick').'classes/class.tx_tick_captionLine.php';
		require_once t3lib_extMgm::extPath('tick').'classes/class.tx_tick_customMarker.php';
		
		// loop over collected data
		foreach ($this->tick_logData as $key => $data) {
		
			// add point
			$memoryDataset->addPoint($data[0], $data[1]);
				
			// add database operation area
			if (!empty($data[4])) {
				list($dbOperation, $position) = explode('_', $data[4]);
				if (!is_array($internalStack[$dbOperation])) {
					$internalStack[$dbOperation] = array();
				}
				if ($position == 'begin') {
					// store on stack
					array_push($internalStack[$dbOperation], $data);
				} elseif ($position == 'end') {
					// get begin from stack
					$beginData = array_pop($internalStack[$dbOperation]);
					
					$db[$key] = new tx_tick_customMarker();
					$Plotarea->add($db[$key]);
					$db[$key]->addVertex(array('X' => $beginData[0], 'Y' => $beginData[1]-$thickness));
					$db[$key]->addVertex(array('X' => $beginData[0], 'Y' => $beginData[1]+$thickness));
					$db[$key]->addVertex(array('X' => $data[0], 'Y' => $data[1]+$thickness));
					$db[$key]->addVertex(array('X' => $data[0], 'Y' => $data[1]-$thickness));
					$db[$key]->setMarkerTitle($beginData[2]);
					$db[$key]->setFillColor($dbOperationColors[$dbOperation].'@0.5');
				}
			}
			
			// add vertical marker with message
			if (!empty($data[2]) && empty($data[4])) {
				$myCaptionLine[$key] = new tx_tick_captionLine();
				$Plotarea->add($myCaptionLine[$key], IMAGE_GRAPH_AXIS_X);
				$color = array_key_exists($data[5], $messageTypeColors) ? $messageTypeColors[$data[5]] : $messageTypeColors_default;
				$myCaptionLine[$key]->setLineColor($color);
				$myCaptionLine[$key]->setValue($data[0]);
				$myCaptionLine[$key]->setMarkerTitle($data[2]);
			}
		}
		
		$tsStackLogCopy = $this->tsStackLog;
		
		$height = 1;
		
		foreach($tsStackLogCopy as $uniqueId => $data) {
			$tsStackLogCopy[$uniqueId]['endtime'] = $this->convertMicrotime($tsStackLogCopy[$uniqueId]['endtime'])-$this->starttime;
			$tsStackLogCopy[$uniqueId]['starttime'] = $this->convertMicrotime($tsStackLogCopy[$uniqueId]['starttime'])-$this->starttime;
			$tsStackLogCopy[$uniqueId]['deltatime'] = $tsStackLogCopy[$uniqueId]['endtime']-$tsStackLogCopy[$uniqueId]['starttime'];
			$tsStackLogCopy[$uniqueId]['key'] = implode($tsStackLogCopy[$uniqueId]['stackPointer']?'.':'/', end($data['tsStack']));

			// adding an area for this stack level
			$stack[$uniqueId] = new tx_tick_customMarker();
			$Plotarea->add($stack[$uniqueId], IMAGE_GRAPH_AXIS_Y_SECONDARY);
			$stack[$uniqueId]->addVertex(array('X' => $tsStackLogCopy[$uniqueId]['starttime'], 'Y' => ($tsStackLogCopy[$uniqueId]['level']-1) * $height));
			$stack[$uniqueId]->addVertex(array('X' => $tsStackLogCopy[$uniqueId]['starttime'], 'Y' => $tsStackLogCopy[$uniqueId]['level'] * $height));
			$stack[$uniqueId]->addVertex(array('X' => $tsStackLogCopy[$uniqueId]['endtime'], 'Y' => $tsStackLogCopy[$uniqueId]['level'] * $height));
			$stack[$uniqueId]->addVertex(array('X' => $tsStackLogCopy[$uniqueId]['endtime'], 'Y' => ($tsStackLogCopy[$uniqueId]['level']-1) * $height));
			$stack[$uniqueId]->setMarkerTitle($tsStackLogCopy[$uniqueId]['key'] .' ('.$tsStackLogCopy[$uniqueId]['deltatime'] . 'ms)');
			$stack[$uniqueId]->setFillColor('lightblue@'. (0.3 + 0.2*$tsStackLogCopy[$uniqueId]['level']));
			$stack[$uniqueId]->setLineColor('lightblue');
		}
			
		// setting up the lines
		$memory = $Plotarea->addNew('line', array($memoryDataset), IMAGE_GRAPH_AXIS_Y);
		$memory->setLineColor('blue');
		
		// formatting the axis
		$yAxis = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$yAxis->setLabelInterval(1024);
		$yAxis->setTitle('Memory usage [kb]', 'vertical');
		
		$xAxis = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
		$xAxis->setTitle('Time [ms]');
		
		$yAxisSecond = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$yAxisSecond ->setTitle('Stack level', 'vertical2');
		$yAxisSecond ->forceMaximum(15);
		
		$filename = str_replace('###TIMESTAMP###', time(), $conf['svgFilePath']);
		
		$Graph->done(array('filename' => PATH_site . $filename));
	}
	
}