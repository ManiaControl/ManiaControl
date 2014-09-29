<?php

namespace FML\Script;

/**
 * Class representing a part of the ManiaLink Script
 *
 * @author    steeffeen <mail@steeffeen.com>
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
	 * Protected properties
	 */
	protected $name = null;
	protected $text = null;
	protected $isolated = null;

	/**
	 * Construct a new ScriptLabel
	 *
	 * @param string $name     (optional) Label name
	 * @param string $text     (optional) Script text
	 * @param bool   $isolated (optional) Isolate the Label Script
	 */
	public function __construct($name = self::LOOP, $text = null, $isolated = false) {
		$this->setName($name);
		$this->setText($text);
		$this->setIsolated($isolated);
	}

	/**
	 * Set the name
	 *
	 * @param string $name Label name
	 * @return static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the text
	 *
	 * @param string $text Script text
	 * @return static
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Set isolation
	 *
	 * @param bool $isolated Whether the code should be isolated in an own block
	 * @return static
	 */
	public function setIsolated($isolated) {
		$this->isolated = (bool)$isolated;
		return $this;
	}

	/**
	 * Check if the given label is an event label
	 *
	 * @param string $label Label name
	 * @return bool
	 */
	public static function isEventLabel($label) {
		if (in_array($label, static::getEventLabels())) {
			return true;
		}
		return false;
	}

	/**
	 * Get the possible event label names
	 *
	 * @return string[]
	 */
	public static function getEventLabels() {
		return array(self::ENTRYSUBMIT, self::KEYPRESS, self::MOUSECLICK, self::MOUSEOUT, self::MOUSEOVER);
	}

	/**
	 * Build the full Script Label text
	 *
	 * @return string
	 */
	public function __toString() {
		return Builder::getLabelImplementationBlock($this->name, $this->text, $this->isolated);
	}
}
