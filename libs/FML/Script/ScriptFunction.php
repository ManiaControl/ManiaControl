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
	 * Protected properties
	 */
	protected $name = null;
	protected $text = null;

	/**
	 * Construct a new Script Function
	 *
	 * @param string $name (optional) Function name
	 * @param string $text (optional) Function text
	 */
	public function __construct($name = null, $text = null) {
		$this->setName($name);
		$this->setText($text);
	}

	/**
	 * Set the name
	 *
	 * @param string $name Function name
	 * @return static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the text
	 *
	 * @param string $text Function text
	 * @return static
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Get the Script Function text
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->text;
	}
}
