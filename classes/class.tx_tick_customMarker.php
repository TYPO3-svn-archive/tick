<?php

require_once 'Image/Graph/Grid.php';

class tx_tick_customMarker extends Image_Graph_Grid {

	protected $points;
	protected $markerTitle;


    /**
     * [Constructor]
     */
    public function __construct()
    {
        parent::Image_Graph_Grid();
        $this->_lineStyle = false;
    }
	
	public function addVertex(array $point) {
		$this->points[] = $point;
	}
	
	
	
	public function setMarkerTitle($markerTitle) {
		$this->markerTitle = $markerTitle;
	}

    /**
     * Output the grid
     *
     * @return bool Was the output 'good' (true) or 'bad' (false).
     * @access private
     */
    function _done() {
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