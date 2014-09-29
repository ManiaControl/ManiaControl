<?php

namespace FML\Controls;

use FML\Types\Styleable;

/**
 * Gauge Control
 * (CMlGauge)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Gauge extends Control implements Styleable {
	/*
	 * Constants
	 */
	const STYLE_BgCard           = 'BgCard';
	const STYLE_EnergyBar        = 'EnergyBar';
	const STYLE_ProgressBar      = 'ProgressBar';
	const STYLE_ProgressBarSmall = 'ProgressBarSmall';

	/*
	 * Protected properties
	 */
	protected $tagName = 'gauge';
	protected $ratio = 0.;
	protected $grading = 1.;
	protected $color = null;
	protected $centered = null;
	protected $clan = null;
	protected $drawBg = 1;
	protected $drawBlockBg = 1;
	protected $style = null;

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlGauge';
	}

	/**
	 * Set ratio
	 *
	 * @param float $ratio Ratio value
	 * @return static
	 */
	public function setRatio($ratio) {
		$this->ratio = (float)$ratio;
		return $this;
	}

	/**
	 * Set grading
	 *
	 * @param float $grading Grading value
	 * @return static
	 */
	public function setGrading($grading) {
		$this->grading = (float)$grading;
		return $this;
	}

	/**
	 * Set color
	 *
	 * @param string $color Gauge color
	 * @return static
	 */
	public function setColor($color) {
		$this->color = (string)$color;
		return $this;
	}

	/**
	 * Set centered
	 *
	 * @param bool $centered Whether the Gauge is centered
	 * @return static
	 */
	public function setCentered($centered) {
		$this->centered = ($centered ? 1 : 0);
		return $this;
	}

	/**
	 * Set clan
	 *
	 * @param int $clan Clan number
	 * @return static
	 */
	public function setClan($clan) {
		$this->clan = (int)$clan;
		return $this;
	}

	/**
	 * Set draw background
	 *
	 * @param bool $drawBg Whether the Gauges background should be drawn
	 * @return static
	 */
	public function setDrawBg($drawBg) {
		$this->drawBg = ($drawBg ? 1 : 0);
		return $this;
	}

	/**
	 * Set draw block background
	 *
	 * @param bool $drawBlockBg Whether the Gauges block background should be drawn
	 * @return static
	 */
	public function setDrawBlockBg($drawBlockBg) {
		$this->drawBlockBg = ($drawBlockBg ? 1 : 0);
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
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->ratio) {
			$xmlElement->setAttribute('ratio', $this->ratio);
		}
		if ($this->grading != 1.) {
			$xmlElement->setAttribute('grading', $this->grading);
		}
		if ($this->color) {
			$xmlElement->setAttribute('color', $this->color);
		}
		if ($this->centered) {
			$xmlElement->setAttribute('centered', $this->centered);
		}
		if ($this->clan) {
			$xmlElement->setAttribute('clan', $this->clan);
		}
		if (!$this->drawBg) {
			$xmlElement->setAttribute('drawbg', $this->drawBg);
		}
		if (!$this->drawBlockBg) {
			$xmlElement->setAttribute('drawblockbg', $this->drawBlockBg);
		}
		if ($this->style) {
			$xmlElement->setAttribute('style', $this->style);
		}
		return $xmlElement;
	}
}
