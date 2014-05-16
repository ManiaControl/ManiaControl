<?php
// TODO: add entry styles

namespace FML\Controls;

use FML\Script\Features\EntrySubmit;
use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Entry Control
 * (CMlEntry)
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
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
	protected $autoComplete = null;

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
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlEntry';
	}

	/**
	 * Get the Entry Name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set Entry Name
	 *
	 * @param string $name Entry Name
	 * @return \FML\Controls\Entry
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Get the Default Value
	 *
	 * @return mixed
	 */
	public function getDefault() {
		return $this->default;
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
	 * @see \FML\Types\NewLineable::setAutoNewLine()
	 */
	public function setAutoNewLine($autoNewLine) {
		$this->autoNewLine = ($autoNewLine ? 1 : 0);
		return $this;
	}

	/**
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 * @see \FML\Types\Styleable::setStyle()
	 */
	public function setStyle($style) {
		$this->style = (string)$style;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setTextColor()
	 */
	public function setTextColor($textColor) {
		$this->textColor = (string)$textColor;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setTextSize()
	 */
	public function setTextSize($textSize) {
		$this->textSize = (int)$textSize;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setAreaColor()
	 */
	public function setAreaColor($areaColor) {
		$this->focusAreaColor1 = (string)$areaColor;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->focusAreaColor2 = (string)$areaFocusColor;
		return $this;
	}

	/**
	 * Set Auto Completion
	 *
	 * @param bool $autoComplete Whether the Default Value should be automatically completed based on the current Request Parameters
	 * @return \FML\Controls\Entry
	 */
	public function setAutoComplete($autoComplete) {
		$this->autoComplete = (bool)$autoComplete;
		return $this;
	}

	/**
	 * Add a dynamic Feature submitting the Entry
	 *
	 * @param string $url Submit Url
	 * @return \FML\Controls\Entry
	 */
	public function addSubmitFeature($url) {
		$entrySubmit = new EntrySubmit($this, $url);
		$this->addScriptFeature($entrySubmit);
		return $this;
	}

	/**
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->name) {
			$xmlElement->setAttribute('name', $this->name);
		}
		if ($this->default !== null) {
			$xmlElement->setAttribute('default', $this->default);
		} else if ($this->autoComplete) {
			$value = null;
			if (array_key_exists($this->name, $_GET)) {
				$value = $_GET[$this->name];
			} else if (array_key_exists($this->name, $_POST)) {
				$value = $_POST[$this->name];
			}
			if ($value) {
				$xmlElement->setAttribute('default', $value);
			}
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
