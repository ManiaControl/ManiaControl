<?php

namespace FML\Controls;

use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Class representing CMlEntry
 *
 * @author steeffeen
 */
class Entry extends Control implements NewLineable, Scriptable, Styleable, TextFormatable {
	/**
	 * Protected Properties
	 */
	protected $name = '';
	protected $default = null;
	protected $autoNewLine = 0;
	protected $scriptEvents = 0;
	protected $style = '';
	protected $textColor = '';
	protected $textSize = -1;
	protected $areaColor = '';
	protected $areaFocusColor = '';

	/**
	 * Construct a new Entry Control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'entry';
	}

	/**
	 * Set Entry Name
	 *
	 * @param string $name
	 *        	Entry Name
	 * @return \FML\Controls\Entry
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set Default Value
	 *
	 * @param string $default
	 *        	Default Value
	 * @return \FML\Controls\Entry
	 */
	public function setDefault($default) {
		$this->default = $default;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\NewLineable::setAutoNewLine()
	 * @return \FML\Controls\Entry
	 */
	public function setAutoNewLine($autoNewLine) {
		$this->autoNewLine = ($autoNewLine ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 * @return \FML\Controls\Entry
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Styleable::setStyle()
	 * @return \FML\Controls\Entry
	 */
	public function setStyle($style) {
		$this->style = $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextColor()
	 * @return \FML\Controls\Entry
	 */
	public function setTextColor($textColor) {
		$this->textColor = $textColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextSize()
	 * @return \FML\Controls\Entry
	 */
	public function setTextSize($textSize) {
		$this->textSize = $textSize;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaColor()
	 * @return \FML\Controls\Entry
	 */
	public function setAreaColor($areaColor) {
		$this->areaColor = $areaColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 * @return \FML\Controls\Entry
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->areaFocusColor = $areaFocusColor;
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
		if ($this->autoNewLine) {
			$xml->setAttribute('autonewline', $this->autoNewLine);
		}
		if ($this->scriptEvents) {
			$xml->setAttribute('scriptevents', $this->scriptEvents);
		}
		if ($this->style) {
			$xml->setAttribute('style', $this->style);
		}
		if ($this->textColor) {
			$xml->setAttribute('textcolor', $this->textColor);
		}
		if ($this->textSize >= 0.) {
			$xml->setAttribute('textsize', $this->textSize);
		}
		if ($this->areaColor) {
			$xml->setAttribute('areacolor', $this->areaColor);
		}
		if ($this->areaFocusColor) {
			$xml->setAttribute('areafocuscolor', $this->areaFocusColor);
		}
		return $xml;
	}
}
