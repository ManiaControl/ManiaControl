<?php

namespace FML\Script\Sections;

/**
 * Script feature using labels
 *
 * @author steeffeen
 */
interface Labels {
	/**
	 * Constants
	 */
	const ENTRYSUBMIT = 'EntrySubmit';
	const KEYPRESS = 'KeyPress';
	const LOOP = 'Loop';
	const MOUSECLICK = 'MouseClick';
	const MOUSEOUT = 'MouseOut';
	const MOUSEOVER = 'MouseOver';
	const ONINIT = 'OnInit';

	/**
	 * Return array of label implementations with label names as keys
	 *
	 * @return array
	 */
	public function getLabels();
}
