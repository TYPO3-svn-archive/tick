<?php

require_once 'Image/Graph/Axis/Marker/Line.php';

class tx_tick_captionLine extends Image_Graph_Axis_Marker_Line {

	protected $markerTitle;
	
	public function setMarkerTitle($markerTitle) {
		$this->markerTitle = $markerTitle;
	}
	
	function _done() {
        if (parent::_done() === false) {
            return false;
        }

        if (!$this->_primaryAxis) {
            return false;
        }
        
        $this->_canvas->startGroup(get_class($this));

        $i = 0;

        $this->_value = min($this->_primaryAxis->_getMaximum(), max($this->_primaryAxis->_getMinimum(), $this->_value));

        $secondaryPoints = $this->_getSecondaryAxisPoints();

        reset($secondaryPoints);
        list ($id, $previousSecondaryValue) = each($secondaryPoints);
        while (list ($id, $secondaryValue) = each($secondaryPoints)) {
            if ($this->_primaryAxis->_type == IMAGE_GRAPH_AXIS_X) {
                $p1 = array ('X' => $this->_value, 'Y' => $secondaryValue);
                $p2 = array ('X' => $this->_value, 'Y' => $previousSecondaryValue);
            } else {
                $p1 = array ('X' => $secondaryValue, 'Y' => $this->_value);
                $p2 = array ('X' => $previousSecondaryValue, 'Y' => $this->_value);
            }

            $x1 = $this->_pointX($p1);
            $y1 = $this->_pointY($p1);
            $x2 = $this->_pointX($p2);
            $y2 = $this->_pointY($p2);

            $previousSecondaryValue = $secondaryValue;

            $this->_getLineStyle();
			if (!empty($this->markerTitle)) {
				$this->_canvas->line(array('x0' => $x1, 'y0' => $y1, 'x1' => $x2, 'y1' => $y2, 'attrs' => array('title' => $this->markerTitle)));
			} else {
				$this->_canvas->line(array('x0' => $x1, 'y0' => $y1, 'x1' => $x2, 'y1' => $y2));
			}
        }
        
        $this->_canvas->endGroup();
        
        return true;
    }
	
}
