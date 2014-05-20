<?php

namespace FML\Script;

/**
 * Class representing a Function of the ManiaLink Script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptFunction {
	/*
	 * Protected Properties
	 */
	protected $name = null;
	protected $text = null;

	/**
	 * Construct a new Script Function
	 *
	 * @param string $name (optional) Function Name
	 * @param string $text (optional) Function Text
	 */
	public function __construct($name = null, $text = null) {
		$this->setName($name);
		$this->setText($text);
	}

	/**
	 * Set the Name
	 *
	 * @param string $name Function Name
	 * @return \FML\Script\ScriptFunction
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the Text
	 *
	 * @param string $text Function Text
	 * @return \FML\Script\ScriptFunction
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Get the Script Function Text
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->text;
	}
}
