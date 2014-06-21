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
	 * Protected properties
	 */
	protected $file = null;
	protected $namespace = null;

	/**
	 * Construct a new Script Include
	 *
	 * @param string $file      (optional) Include file
	 * @param string $namespace (optional) Include namespace
	 */
	public function __construct($file = null, $namespace = null) {
		$this->setFile($file);
		$this->setNamespace($namespace);
	}

	/**
	 * Set the file
	 *
	 * @param string $file Include file
	 * @return \FML\Script\ScriptInclude|static
	 */
	public function setFile($file) {
		$this->file = (string)$file;
		return $this;
	}

	/**
	 * Set the namespace
	 *
	 * @param string $namespace Include namespace
	 * @return \FML\Script\ScriptInclude|static
	 */
	public function setNamespace($namespace) {
		$this->namespace = (string)$namespace;
		return $this;
	}

	/**
	 * Get the namespace
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * Build the Script Include text
	 *
	 * @return string
	 */
	public function __toString() {
		return Builder::getInclude($this->file, $this->namespace);
	}
}
