<?php

namespace FML\Controls;

/**
 * Class representing CMlGauge
 *
 * @author steeffeen
 */
class Gauge extends Control implements Styleable {
	/**
	 * Protected properties
	 */
	protected $ratio = 1.;
	protected $grading = 1.;
	protected $rotation = 0.;
	protected $centered = 0;
	protected $clan = 0;
	protected $drawBg = 1;
	protected $drawBlockBg = 1;

	/**
	 * Construct a new gauge control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->name = 'gauge';
	}

	/**
	 * Set ratio
	 *
	 * @param real $ratio        	
	 */
	public function setRatio($ratio) {
		$this->ratio = $ratio;
	}

	/**
	 * Set grading
	 *
	 * @param real $grading        	
	 */
	public function setGrading($grading) {
		$this->grading = $grading;
	}

	/**
	 * Set rotation
	 *
	 * @param real $rotation        	
	 */
	public function setRotation($rotation) {
		$this->rotation = $rotation;
	}

	/**
	 * Set centered
	 *
	 * @param bool $centered        	
	 */
	public function setCentered($centered) {
		$this->centered = ($centered ? 1 : 0);
	}

	/**
	 * Set clan
	 *
	 * @param int $clan        	
	 */
	public function setClan($clan) {
		$this->clan = $clan;
	}

	/**
	 * Set draw background
	 *
	 * @param bool $drawBg        	
	 */
	public function setDrawBg($drawBg) {
		$this->drawBg = ($drawBg ? 1 : 0);
	}

	/**
	 * Set draw block background
	 *
	 * @param bool $drawBlockBg        	
	 */
	public function setDrawBlockBg($drawBlockBg) {
		$this->drawBlockBg = ($drawBlockBg ? 1 : 0);
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		$xml->setAttribute('ratio', $this->ratio);
		$xml->setAttribute('grading', $this->grading);
		$xml->setAttribute('rotation', $this->rotation);
		$xml->setAttribute('centered', $this->centered);
		$xml->setAttribute('clan', $this->clan);
		$xml->setAttribute('drawbg', $this->drawBg);
		$xml->setAttribute('drawblockbg', $this->drawBlockBg);
		return $xml;
	}
}

?>
