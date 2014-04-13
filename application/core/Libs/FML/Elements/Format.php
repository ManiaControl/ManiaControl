<?php

namespace FML\Elements;

use FML\Types\BgColorable;
use FML\Types\Renderable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Format Element
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Format implements BgColorable, Renderable, Styleable, TextFormatable {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'format';
	protected $bgColor = '';
	protected $style = '';
	protected $textSize = -1;
	protected $textColor = '';
	protected $focusAreaColor1 = '';
	protected $focusAreaColor2 = '';

	/**
	 * Create a new Format Element
	 *
	 * @return \FML\Elements\Format
	 */
	public static function create() {
		$format = new Format();
		return $format;
	}

	/**
	 * Construct a new Format Element
	 */
	public function __construct() {
	}

	/**
	 *
	 * @see \FML\Types\BgColorable::setBgColor()
	 * @return \FML\Elements\Format
	 */
	public function setBgColor($bgColor) {
		$this->bgColor = (string) $bgColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Styleable::setStyle()
	 * @return \FML\Elements\Format
	 */
	public function setStyle($style) {
		$this->style = (string) $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextSize()
	 * @return \FML\Elements\Format
	 */
	public function setTextSize($textSize) {
		$this->textSize = (int) $textSize;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextColor()
	 * @return \FML\Elements\Format
	 */
	public function setTextColor($textColor) {
		$this->textColor = (string) $textColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaColor()
	 * @return \FML\Elements\Format
	 */
	public function setAreaColor($areaColor) {
		$this->focusAreaColor1 = (string) $areaColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 * @return \FML\Elements\Format
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->focusAreaColor2 = (string) $areaFocusColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$formatXmlElement = $domDocument->createElement($this->tagName);
		if ($this->bgColor) {
			$formatXmlElement->setAttribute('bgcolor', $this->bgColor);
		}
		if ($this->style) {
			$formatXmlElement->setAttribute('style', $this->style);
		}
		if ($this->textSize >= 0) {
			$formatXmlElement->setAttribute('textsize', $this->textSize);
		}
		if ($this->textColor) {
			$formatXmlElement->setAttribute('textcolor', $this->textColor);
		}
		if ($this->focusAreaColor1) {
			$formatXmlElement->setAttribute('focusareacolor1', $this->focusAreaColor1);
		}
		if ($this->focusAreaColor2) {
			$formatXmlElement->setAttribute('focusareacolor2', $this->focusAreaColor2);
		}
		return $formatXmlElement;
	}
}
