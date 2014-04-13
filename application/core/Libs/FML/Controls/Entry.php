<?php

namespace FML\Controls;

use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Entry Control
 * (CMlEntry)
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Entry extends Control implements NewLineable, Scriptable, Styleable, TextFormatable {
	/*
	 * Protected Properties
	 */
	protected $name = '';
	protected $default = null;
	protected $autoNewLine = 0;
	protected $scriptEvents = 0;
	protected $style = '';
	protected $textColor = '';
	protected $textSize = -1;
	protected $focusAreaColor1 = '';
	protected $focusAreaColor2 = '';

	/**
	 * Create a new Entry Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Entry
	 */
	public static function create($id = null) {
		$entry = new Entry($id);
		return $entry;
	}

	/**
	 * Construct a new Entry Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'entry';
	}

	/**
	 * Set Entry Name
	 *
	 * @param string $name Entry Name
	 * @return \FML\Controls\Entry
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set Default Value
	 *
	 * @param string $default Default Value
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
		$this->style = (string) $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextColor()
	 * @return \FML\Controls\Entry
	 */
	public function setTextColor($textColor) {
		$this->textColor = (string) $textColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextSize()
	 * @return \FML\Controls\Entry
	 */
	public function setTextSize($textSize) {
		$this->textSize = (int) $textSize;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaColor()
	 * @return \FML\Controls\Entry
	 */
	public function setAreaColor($areaColor) {
		$this->focusAreaColor1 = (string) $areaColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 * @return \FML\Controls\Entry
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->focusAreaColor2 = (string) $areaFocusColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->name) {
			$xmlElement->setAttribute('name', $this->name);
		}
		if ($this->default !== null) {
			$xmlElement->setAttribute('default', $this->default);
		}
		if ($this->autoNewLine) {
			$xmlElement->setAttribute('autonewline', $this->autoNewLine);
		}
		if ($this->scriptEvents) {
			$xmlElement->setAttribute('scriptevents', $this->scriptEvents);
		}
		if ($this->style) {
			$xmlElement->setAttribute('style', $this->style);
		}
		if ($this->textColor) {
			$xmlElement->setAttribute('textcolor', $this->textColor);
		}
		if ($this->textSize >= 0.) {
			$xmlElement->setAttribute('textsize', $this->textSize);
		}
		if ($this->focusAreaColor1) {
			$xmlElement->setAttribute('focusareacolor1', $this->focusAreaColor1);
		}
		if ($this->focusAreaColor2) {
			$xmlElement->setAttribute('focusareacolor2', $this->focusAreaColor2);
		}
		return $xmlElement;
	}
}
