<?php

require_once 'Image/Graph/Grid.php';

class tx_tick_customMarker extends Image_Graph_Grid {

	/**
	 * @var array 
	 */
	protected $points;
	
	/**
	 * @var string
	 */
	protected $markerTitle;


    /**
     * [Constructor]
     */
    public function __construct() {
        parent::Image_Graph_Grid();
        $this->_lineStyle = false;
    }
	
	/**
	 * Add vertex
	 * 
	 * @param array $point
	 * @return void
	 */
	public function addVertex(array $point) {
		$this->points[] = $point;
	}
	
	/**
	 * Set marker title 
	 * 
	 * @param string $markerTitle
	 * @return void
	 */
	public function setMarkerTitle($markerTitle) {
		$this->markerTitle = $markerTitle;
	}

    /**
     * Output the grid
     *
     * @return bool Was the output 'good' (true) or 'bad' (false).
     * @access private
     */
    public function _done() {
        if (parent::_done() === false) {
            return false;
        }

        $this->_canvas->startGroup(get_class($this));
        
        foreach ($this->points as $point) {
            $this->_canvas->addVertex(array('x' => $this->_pointX($point), 'y' => $this->_pointY($point)));
		}

		$this->_getLineStyle();
		$this->_getFillStyle();
		if (!empty($this->markerTitle)) {
			$this->_canvas->polygon(array('connect' => true, 'attrs' => array('title' => $this->markerTitle)));
		} else {
			$this->_canvas->polygon(array('connect' => true));
		}
                
        $this->_canvas->endGroup();
        
        return true;
    }

}

?>