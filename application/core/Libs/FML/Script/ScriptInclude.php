<?php

namespace FML\Script;

/**
 * Class representing an Include of the ManiaLink Script
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptInclude {
	/*
	 * Constants
	 */
	const MATHLIB = 'MathLib';
	const TEXTLIB = 'TextLib';

	/*
	 * Protected Properties
	 */
	protected $file = null;
	protected $namespace = null;

	/**
	 * Construct a new Script Include
	 *
	 * @param string $file      (optional) Include File
	 * @param string $namespace (optional) Include Namespace
	 */
	public function __construct($file = null, $namespace = null) {
		$this->setFile($file);
		$this->setNamespace($namespace);
	}

	/**
	 * Set the File
	 *
	 * @param string $file Include File
	 * @return \FML\Script\ScriptInclude
	 */
	public function setFile($file) {
		$this->file = (string)$file;
		return $this;
	}

	/**
	 * Set the Namespace
	 *
	 * @param string $namespace Include Namespace
	 * @return \FML\Script\ScriptInclude
	 */
	public function setNamespace($namespace) {
		$this->namespace = (string)$namespace;
		return $this;
	}

	/**
	 * Get the Namespace
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * Build the Script Include Text
	 *
	 * @return string
	 */
	public function __toString() {
		$scriptText = Builder::getInclude($this->file, $this->namespace);
		return $scriptText;
	}
}
