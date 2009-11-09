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
		
		// drawing the graph
		require_once 'Image/Graph.php';

		/* @var Graph Image_Graph */
		$Graph = Image_Graph::factory('graph', array(array('width' => $conf['svgWidth'], 'height' => $conf['svgHeight'], 'canvas' => 'svg')));

		
		/* @var $Font Image_Graph_Font */
		$Font = $Graph->addNew('font', 'Verdana'); 
		$Font->setSize(10);
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
			'-1' => 'green@0.3',		// sysLog: -, devLog: OK
			'0' => 'black@0.3', 	// Info
			'1' => 'black@0.3', 	// Notice
			'2' => 'orange@0.3', 	// Warning
			'3' => 'red@0.3', 		// sysLog: Error, devLog: Fatal Error
			'4' => 'red@0.3' 		// sysLog: Fatal Error, devLog: -
		);
		
		$dbOperationCount = array();
		$dbOperationDuration = array();
		
		$internalStack = array();
		
		$thickness = 500;
		
		require_once PATH_typo3conf.'ext/tick/classes/class.tx_tick_captionLine.php';
		require_once PATH_typo3conf.'ext/tick/classes/class.tx_tick_customMarker.php';
		
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
					
					// collect some statistics
					$dbOperationCount[$dbOperation]++;
					$dbOperationDuration[$dbOperation] += ($data[0]-$beginData[0]);
										
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
				$myCaptionLine[$key]->setValue($data[0]);
				$myCaptionLine[$key]->setMarkerTitle($data[2]);
				$myCaptionLine[$key]->setLineStyle($MarkerLineStyle);
				$color = array_key_exists($data[5], $messageTypeColors) ? $messageTypeColors[$data[5]] : $messageTypeColors_default;
				$myCaptionLine[$key]->setLineColor($color);
			}
		}
		
		$tsStackLogCopy = $this->tsStackLog;
		
		$height = 1;
		
		// adding tslog stack trace
		
		/* @var $Fill Image_Graph_Fill_Gradient */
		$Fill = Image_Graph::factory('gradient', array(IMAGE_GRAPH_GRAD_HORIZONTAL, 'green@0.2', 'green@0.5'));
		$StackLineStyle = Image_Graph::factory('Image_Graph_Line_Solid', array('green@0.9'));
		$StackLineStyle->setThickness(0.1);
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
			//$stack[$uniqueId]->setFillColor('lightblue@'. (0.3 + 0.2*$tsStackLogCopy[$uniqueId]['level']));
			$stack[$uniqueId]->setLineStyle($StackLineStyle);
			$stack[$uniqueId]->setFillStyle($Fill);
		}
			
		// setting up the lines
		
		
		
		/* @var $memory Image_Graph_Plot_Line */
		$memory = $Plotarea->addNew('line', array($memoryDataset), IMAGE_GRAPH_AXIS_Y);
		$LineStyle = Image_Graph::factory('Image_Graph_Line_Solid', array('blue'));
		$LineStyle->setThickness(1.5);
		$memory->setLineStyle($LineStyle);
		
		// formatting the axis
		$yAxis = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$yAxis->setLabelInterval(2048);
		$yAxis->setTitle('Memory usage [kb]', 'vertical');

		$xAxis = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
		$xAxis->setTitle('Time [ms]');
		
		$yAxisSecond = $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$yAxisSecond->setTitle('Stack level', 'vertical2');
		$yAxisSecond->forceMaximum(15);
		
		// add more information
		/* @var $canvas Image_Canvas_SVG */
		$canvas = $Graph->_getCanvas();
		
		$info = array(
			sprintf('Max. memory usage: %s kb', $memoryDataset->maximumY()),
			sprintf('Total duration: %s ms', $memoryDataset->maximumX())
		);
		$info[] = '';
		$info[] = 'Database operations:';
		foreach ($dbOperationCount as $dbOperation => $count) {
			if ($count > 0) {
				$info[] = sprintf('%sx %s: %s ms', $count, $dbOperation, $dbOperationDuration[$dbOperation]);
 			}
		}
		
		$canvas->rectangle(array(
			'x0' => 140,
			'y0' => 40,
			'x1' => 380,
			'y1' => 10 + 50 + 15*count($info),
			'fill' => '#EEEEEE',
			'line' => 'gray'
		));
		
		$y = 50;
		foreach ($info as $entry) {
			// font needs to be defined every time :(
			$canvas->setFont(array(
				'size' => 12,
				'name' => 'Verdana'
			));
			$canvas->addText(array(
				'x' => 150,
	     		'y' => $y,
	     		'text' => $entry
			));	
			$y += 15;
		}
		
		$filename = str_replace('###TIMESTAMP###', time(), $conf['svgFilePath']);
		
		$Graph->done(array('filename' => PATH_site . $filename));
	}
	
}