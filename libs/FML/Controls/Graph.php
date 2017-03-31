<?php

namespace FML\Controls;

use FML\Script\Features\GraphCurve;

// TODO: check CoordsMin & CoordsMax properties of CMlGraph

/**
 * Graph Control
 * (CMlGraph)
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Graph extends Control
{

    /**
     * @var GraphCurve[] $curves Curves
     */
    protected $curves = array();

    /**
     * Get curves
     *
     * @api
     * @return GraphCurve[]
     */
    public function getCurves()
    {
        return $this->curves;
    }

    /**
     * Add curve
     *
     * @api
     * @param GraphCurve $curve Curve
     * @return static
     */
    public function addCurve(GraphCurve $curve)
    {
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
    public function addCurves(array $curves)
    {
        foreach ($curves as $curve) {
            $this->addCurve($curve);
        }
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "graph";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlGraph";
    }

}
