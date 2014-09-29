<?php

namespace FML\Elements;

use FML\Types\BgColorable;
use FML\Types\Renderable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Format Element
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Format implements BgColorable, Renderable, Styleable, TextFormatable {
	/*
	 * Protected properties
	 */
	protected $tagName = 'format';
	protected $bgColor = null;
	protected $style = null;
	protected $textSize = -1;
	protected $textFont = null;
	protected $textColor = null;
	protected $focusAreaColor1 = null;
	protected $focusAreaColor2 = null;

	/**
	 * Create a new Format Element
	 *
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * @see \FML\Types\BgColorable::setBgColor()
	 */
	public function setBgColor($bgColor) {
		$this->bgColor = (string)$bgColor;
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
	 * @see \FML\Types\TextFormatable::setTextSize()
	 */
	public function setTextSize($textSize) {
		$this->textSize = (int)$textSize;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setTextFont()
	 */
	public function setTextFont($textFont) {
		$this->textFont = (string)$textFont;
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
	 * @see \FML\Types\Renderable::render()
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
		if ($this->textFont) {
			$formatXmlElement->setAttribute('textfont', $this->textFont);
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
