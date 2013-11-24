<?php

namespace FML\Controls;

/**
 * Class representing CMlEntry
 *
 * @author steeffeen
 */
class Entry extends Control implements NewLineable, Scriptable, Styleable, TextFormatable {
	/**
	 * Protected properties
	 */
	protected $name = '';
	protected $default = null;

	/**
	 * Construct a new entry control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'entry';
	}

	/**
	 * Set name
	 *
	 * @param string $name        	
	 * @return \FML\Controls\Entry
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set default
	 *
	 * @param string $default        	
	 * @return \FML\Controls\Entry
	 */
	public function setDefault($default) {
		$this->default = $default;
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		if ($this->name) {
			$xml->setAttribute('name', $this->name);
		}
		if ($this->default !== null) {
			$xml->setAttribute('default', $this->default);
		}
		return $xml;
	}
}

?>
