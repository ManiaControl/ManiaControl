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
 */
class Format implements BgColorable, Renderable, Styleable, TextFormatable {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'format';
	protected $bgColor = '';
	protected $style = '';
	protected $textSize = -1;
	protected $textColor = '';
	protected $areaColor = '';
	protected $areaFocusColor = '';

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
		$this->areaColor = (string) $areaColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 * @return \FML\Elements\Format
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->areaFocusColor = (string) $areaFocusColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->bgColor) {
			$xmlElement->setAttribute('bgcolor', $this->bgColor);
		}
		if ($this->style) {
			$xmlElement->setAttribute('style', $this->style);
		}
		if ($this->textSize >= 0) {
			$xmlElement->setAttribute('textsize', $this->textSize);
		}
		if ($this->textColor) {
			$xmlElement->setAttribute('textcolor', $this->textColor);
		}
		if ($this->areaColor) {
			$xmlElement->setAttribute('areacolor', $this->areaColor);
		}
		if ($this->areaFocusColor) {
			$xmlElement->setAttribute('areafocuscolor', $this->areaFocusColor);
		}
		return $xmlElement;
	}
}
