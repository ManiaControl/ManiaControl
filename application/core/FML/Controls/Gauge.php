<?php

namespace FML\Controls;

use FML\Types\Styleable;

/**
 * Class representing CMlGauge
 *
 * @author steeffeen
 */
class Gauge extends Control implements Styleable {
	/**
	 * Protected Properties
	 */
	protected $ratio = 1.;
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
	 * @param string $id
	 *        	Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'gauge';
	}

	/**
	 * Set Ratio
	 *
	 * @param float $ratio
	 *        	Ratio Value
	 * @return \FML\Controls\Gauge
	 */
	public function setRatio($ratio) {
		$this->ratio = $ratio;
		return $this;
	}

	/**
	 * Set Grading
	 *
	 * @param float $grading
	 *        	Grading Value
	 * @return \FML\Controls\Gauge
	 */
	public function setGrading($grading) {
		$this->grading = $grading;
		return $this;
	}

	/**
	 * Set Color
	 *
	 * @param string $color
	 *        	Gauge Color
	 * @return \FML\Controls\Gauge
	 */
	public function setColor($color) {
		$this->color = $color;
		return $this;
	}

	/**
	 * Set Rotation
	 *
	 * @param float $rotation
	 *        	Gauge Rotation
	 * @return \FML\Controls\Gauge
	 */
	public function setRotation($rotation) {
		$this->rotation = $rotation;
		return $this;
	}

	/**
	 * Set Centered
	 *
	 * @param bool $centered
	 *        	If Gauge is centered
	 * @return \FML\Controls\Gauge
	 */
	public function setCentered($centered) {
		$this->centered = ($centered ? 1 : 0);
		return $this;
	}

	/**
	 * Set Clan
	 *
	 * @param int $clan
	 *        	Clan number
	 * @return \FML\Controls\Gauge
	 */
	public function setClan($clan) {
		$this->clan = $clan;
		return $this;
	}

	/**
	 * Set Draw Background
	 *
	 * @param bool $drawBg
	 *        	If Gauge Background should be drawn
	 * @return \FML\Controls\Gauge
	 */
	public function setDrawBg($drawBg) {
		$this->drawBg = ($drawBg ? 1 : 0);
		return $this;
	}

	/**
	 * Set Draw Block Background
	 *
	 * @param bool $drawBlockBg
	 *        	If Gauge Block Background should be drawn
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
		$this->style = $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		$xml->setAttribute('ratio', $this->ratio);
		$xml->setAttribute('grading', $this->grading);
		if ($this->color) {
			$xml->setAttribute('color', $this->color);
		}
		if ($this->rotation) {
			$xml->setAttribute('rotation', $this->rotation);
		}
		if ($this->centered) {
			$xml->setAttribute('centered', $this->centered);
		}
		if ($this->clan) {
			$xml->setAttribute('clan', $this->clan);
		}
		$xml->setAttribute('drawbg', $this->drawBg);
		$xml->setAttribute('drawblockbg', $this->drawBlockBg);
		if ($this->style) {
			$xml->setAttribute('style', $this->style);
		}
		return $xml;
	}
}
