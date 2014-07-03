<?php

namespace FML\Script;

/**
 * Class representing a Constant of the ManiaLink Script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptConstant {
	/*
	 * Protected properties
	 */
	protected $name = null;
	protected $value = null;

	/**
	 * Construct a new Script Constant
	 *
	 * @param string $name  (optional) Constant name
	 * @param string $value (optional) Constant value
	 */
	public function __construct($name = null, $value = null) {
		$this->setName($name);
		$this->setValue($value);
	}

	/**
	 * Set the name
	 *
	 * @param string $name Constant name
	 * @return static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the value
	 *
	 * @param string $value Constant value
	 * @return static
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Build the Script Constant text
	 *
	 * @return string
	 */
	public function __toString() {
		return Builder::getConstant($this->name, $this->value);
	}
}
