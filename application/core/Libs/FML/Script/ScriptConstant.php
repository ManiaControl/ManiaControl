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
	 * Protected Properties
	 */
	protected $name = null;
	protected $value = null;

	/**
	 * Construct a new Script Constant
	 *
	 * @param string $name  (optional) Constant Name
	 * @param string $value (optional) Constant Value
	 */
	public function __construct($name = null, $value = null) {
		$this->setName($name);
		$this->setValue($value);
	}

	/**
	 * Set the Name
	 *
	 * @param string $name Constant Name
	 * @return \FML\Script\ScriptConstant
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set the Value
	 *
	 * @param string $value Constant Value
	 * @return \FML\Script\ScriptConstant
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Build the Script Constant Text
	 *
	 * @return string
	 */
	public function __toString() {
		$scriptText = Builder::getConstant($this->name, $this->value);
		return $scriptText;
	}
}
