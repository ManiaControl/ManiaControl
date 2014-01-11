<?php

namespace FML\Controls;

use FML\Types\Styleable;

/**
 * Gauge Element
 * (CMlGauge)
 *
 * @author steeffeen
 */
class Gauge extends Control implements Styleable {
	/**
	 * Protected Properties
	 */
	protected $ratio = 1.;
	// TODO: validate grading
	protected $grading = 1.;
	protected $color = '';
	protected $rotation = 0.;
	protected $centered = 0;
	protected $clan = 0;
	protected $drawBg = 1;
	protected $drawBlockBg = 1;
	protected $style = '';

	/**
	 * Construct a new Gauge Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'gauge';
	}

	/**
	 * Set Ratio
	 *
	 * @param float $ratio Ratio Value
	 * @return \FML\Controls\Gauge
	 */
	public function setRatio($ratio) {
		$this->ratio = (float) $ratio;
		return $this;
	}

	/**
	 * Set Grading
	 *
	 * @param float $grading Grading Value
	 * @return \FML\Controls\Gauge
	 */
	public function setGrading($grading) {
		$this->grading = (float) $grading;
		return $this;
	}

	/**
	 * Set Color
	 *
	 * @param string $color Gauge Color
	 * @return \FML\Controls\Gauge
	 */
	public function setColor($color) {
		$this->color = (string) $color;
		return $this;
	}

	/**
	 * Set Rotation
	 *
	 * @param float $rotation Gauge Rotation
	 * @return \FML\Controls\Gauge
	 */
	public function setRotation($rotation) {
		$this->rotation = (float) $rotation;
		return $this;
	}

	/**
	 * Set Centered
	 *
	 * @param bool $centered Whether Gauge is centered
	 * @return \FML\Controls\Gauge
	 */
	public function setCentered($centered) {
		$this->centered = ($centered ? 1 : 0);
		return $this;
	}

	/**
	 * Set Clan
	 *
	 * @param int $clan Clan number
	 * @return \FML\Controls\Gauge
	 */
	public function setClan($clan) {
		$this->clan = (int) $clan;
		return $this;
	}

	/**
	 * Set Draw Background
	 *
	 * @param bool $drawBg Whether Gauge Background should be drawn
	 * @return \FML\Controls\Gauge
	 */
	public function setDrawBg($drawBg) {
		$this->drawBg = ($drawBg ? 1 : 0);
		return $this;
	}

	/**
	 * Set Draw Block Background
	 *
	 * @param bool $drawBlockBg Whether Gauge Block Background should be drawn
	 * @return \FML\Controls\Gauge
	 */
	public function setDrawBlockBg($drawBlockBg) {
		$this->drawBlockBg = ($drawBlockBg ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Styleable::setStyle()
	 * @return \FML\Controls\Gauge
	 */
	public function setStyle($style) {
		$this->style = (string) $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		// TODO: validate default values
		$xmlElement->setAttribute('ratio', $this->ratio);
		$xmlElement->setAttribute('grading', $this->grading);
		if ($this->color) {
			$xmlElement->setAttribute('color', $this->color);
		}
		if ($this->rotation) {
			$xmlElement->setAttribute('rotation', $this->rotation);
		}
		if ($this->centered) {
			$xmlElement->setAttribute('centered', $this->centered);
		}
		if ($this->clan) {
			$xmlElement->setAttribute('clan', $this->clan);
		}
		$xmlElement->setAttribute('drawbg', $this->drawBg);
		$xmlElement->setAttribute('drawblockbg', $this->drawBlockBg);
		if ($this->style) {
			$xmlElement->setAttribute('style', $this->style);
		}
		return $xmlElement;
	}
}
