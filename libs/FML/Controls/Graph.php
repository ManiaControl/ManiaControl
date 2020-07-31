<?php

namespace FML\Controls;

use FML\Script\Features\GraphCurve;
use FML\Script\Features\GraphSettings;

/**
 * Graph Control
 * (CMlGraph)
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Graph extends Control {

	/**
	 * @var GraphSettings $graphSettings Graph settings
	 */
	protected $graphSettings = null;

	/**
	 * @var GraphCurve[] $curves Curves
	 */
	protected $curves = array();

	/**
	 * Get the graph settings
	 *
	 * @api
	 * @return GraphSettings
	 */
	public function getSettings() {
		if (!$this->graphSettings) {
			$this->createSettings();
		}
		return $this->graphSettings;
	}

	/**
	 * Create new graph settings
	 *
	 * @return GraphSettings
	 */
	protected function createSettings() {
		$this->graphSettings = new GraphSettings($this);
		$this->addScriptFeature($this->graphSettings);
		return $this->graphSettings;
	}

	/**
	 * Get curves
	 *
	 * @api
	 * @return GraphCurve[]
	 */
	public function getCurves() {
		return $this->curves;
	}

	/**
	 * Add curve
	 *
	 * @api
	 * @param GraphCurve $curve Curve
	 * @return static
	 */
	public function addCurve(GraphCurve $curve) {
		$curve->setGraph($this);
		$this->addScriptFeature($curve);
		array_push($this->curves, $curve);
		return $this;
	}

	/**
	 * Add curves
	 *
	 * @api
	 * @param GraphCurve[] $curves Curves
	 * @return static
	 */
	public function addCurves(array $curves) {
		foreach ($curves as $curve) {
			$this->addCurve($curve);
		}
		return $this;
	}

	/**
	 * @see Control::getTagName()
	 */
	public function getTagName() {
		return "graph";
	}

	/**
	 * @see Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return "CMlGraph";
	}

}
