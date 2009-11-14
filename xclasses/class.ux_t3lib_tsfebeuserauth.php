<?php

class ux_t3lib_tsfeBeUserAuth extends t3lib_tsfeBeUserAuth {
	
	public function extPrintFeAdminDialog() {
		$adminPanel = parent::extPrintFeAdminDialog();
		if ($this->uc['TSFE_adminConfig']['display_top']) {
			$performance = $this->extGetCategory_performance();
			$adminPanel = str_replace('</table>'.chr(10).'</form>', $performance.'</table>'.chr(10).'</form>', $adminPanel);
		}
		return $adminPanel;
	}
	
	/**
	 * Creates the content for the "performance" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	public function extGetCategory_performance($out='')	{
		$out.= $this->extGetHead('performance');
		if ($this->uc['TSFE_adminConfig']['display_performance']) {
			$GLOBALS['TT']->getGraph();
			$fileName = $GLOBALS['TT']->getTickFileName();
			$graph = $this->extFw('<a href="'.$fileName.'">Download svg file</a><br />');
			$graph .= '<embed src="' . $GLOBALS['TT']->getTickFileName() . '" width=' . $GLOBALS['TT']->getTickConfig('svgWidth') . ' height=' . $GLOBALS['TT']->getTickConfig('svgHeight') . ' type="image/svg+xml">';
			
			$out.= 	'<tr><td colspan="4">'.$graph.'</td></tr>';
			
			
		}
		return $out;
	}
	
	public function extGetLL($key) {
		// quick'n'dirty :)
		switch ($key) {
			case 'performance' : $labelStr = 'Performance';	break;
			default: $labelStr = parent::extGetLL($key);
		}
		
		return $labelStr;
	}
	
}