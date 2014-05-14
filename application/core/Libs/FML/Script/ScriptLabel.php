<?php

namespace FML\Script;

/**
 * Class representing a Part of the ManiaLink Script
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptLabel {
	/*
	 * Constants
	 */
	const ONINIT      = 'FML_OnInit';
	const LOOP        = 'FML_Loop';
	const TICK        = 'FML_Tick';
	const ENTRYSUBMIT = 'FML_EntrySubmit';
	const KEYPRESS    = 'FML_KeyPress';
	const MOUSECLICK  = 'FML_MouseClick';
	const MOUSEOUT    = 'FML_MouseOut';
	const MOUSEOVER   = 'FML_MouseOver';

	/*
	 * Protected Properties
	 */
	protected $name = null;
	protected $text = null;
	protected $isolated = null;

	/**
	 * Construct a new ScriptLabel
	 *
	 * @param string $name     (optional) Label Name
	 * @param string $text     (optional) Script Text
	 * @param bool   $isolated (optional) Isolate the Label Script
	 */
	public function __construct($name = self::LOOP, $text = '', $isolated = false) {
		$this->setName($name);
		$this->setText($text);
		$this->setIsolated($isolated);
	}

	/**
	 * Set the Name
	 *
	 * @param string $name Label Name
	 * @return \FML\Script\ScriptLabel
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the Text
	 *
	 * @param string $text Script Text
	 * @return \FML\Script\ScriptLabel
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Set Isolation
	 *
	 * @param bool $isolated Whether the Code should be isolated in an own Block
	 * @return \FML\Script\ScriptLabel
	 */
	public function setIsolated($isolated) {
		$this->isolated = (bool)$isolated;
		return $this;
	}

	/**
	 * Build the full Script Label Text
	 *
	 * @return string
	 */
	public function __toString() {
		$scriptText = Builder::getLabelImplementationBlock($this->name, $this->text, $this->isolated);
		return $scriptText;
	}
}
