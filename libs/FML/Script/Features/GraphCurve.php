<?php

namespace FML\Script\Features;

use FML\Controls\Graph;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;

/**
 * Script Feature adding a Curve to a Graph
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class GraphCurve extends ScriptFeature
{

    /**
     * @var Graph $graph Graph
     */
    protected $graph = null;

    /**
     * @var array[] $points Points
     */
    protected $points = array();

    /**
     * @var bool $sortPoints Sort points
     */
    protected $sortPoints = false;

    /**
     * @var float[] $color Color
     */
    protected $color = null;

    /**
     * @var string $style Style
     */
    protected $style = null;

    /**
     * @var float $width Width
     */
    protected $width = null;

    /**
     * Construct a new Graph Curve
     *
     * @api
     * @param Graph   $graph  (optional) Graph
     * @param array[] $points (optional) Points
     */
    public function __construct(Graph $graph = null, array $points = null)
    {
        if ($graph) {
            $this->setGraph($graph);
        }
        if ($points) {
            $this->setPoints($points);
        }
    }

    /**
     * Get the Graph
     *
     * @api
     * @return Graph
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * Set the Graph
     *
     * @api
     * @param Graph $graph Graph
     * @return static
     */
    public function setGraph(Graph $graph)
    {
        $graph->checkId();
        $this->graph = $graph;
        return $this;
    }

    /**
     * Get the points
     *
     * @api
     * @return array[]
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * Add point
     *
     * @api
     * @param float|float[] $coordX X-coordinate or point
     * @param float         $coordY (optional) Y-coordinate
     * @return static
     */
    public function addPoint($coordX, $coordY = null)
    {
        if (is_array($coordX)) {
            $coordY = (isset($coordX[1]) ? $coordX[1] : 0.);
            $coordX = (isset($coordX[0]) ? $coordX[0] : 0.);
        }
        array_push($this->points, array($coordX, $coordY));
        return $this;
    }

    /**
     * Add points
     *
     * @api
     * @param array[] $points Points
     * @return static
     */
    public function addPoints(array $points)
    {
        foreach ($points as $point) {
            $this->addPoint($point);
        }
        return $this;
    }

    /**
     * Set the points
     *
     * @api
     * @param array[] $points Points
     * @return static
     */
    public function setPoints(array $points)
    {
        return $this->removeAllPoints()
                    ->addPoints($points);
    }

    /**
     * Remove all points
     *
     * @api
     * @return static
     */
    public function removeAllPoints()
    {
        $this->points = array();
        return $this;
    }

    /**
     * Get if point should be sorted
     *
     * @api
     * @return bool
     */
    public function getSortPoints()
    {
        return $this->sortPoints;
    }

    /**
     * Set if point should be sorted
     *
     * @api
     * @param bool $sortPoints If point should be sorted
     * @return static
     */
    public function setSortPoints($sortPoints)
    {
        $this->sortPoints = (bool)$sortPoints;
        return $this;
    }

    /**
     * Get the color
     *
     * @api
     * @return float[]
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the color
     *
     * @api
     * @param float[] $color (optional) Color
     * @return static
     */
    public function setColor(array $color = null)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Get the style
     *
     * @api
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set the style
     *
     * @api
     * @return static
     */
    public function setStyle($style)
    {
        $this->style = (string)$style;
        return $this;
    }

    /**
     * Get the width
     *
     * @api
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set the width
     *
     * @api
     * @return static
     */
    public function setWidth($width)
    {
        $this->width = (float)$width;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->appendGenericScriptLabel(ScriptLabel::ONINIT, $this->getScriptText(), true);
        return $this;
    }

    /**
     * Get the script text
     *
     * @return string
     */
    protected function getScriptText()
    {
        $graphId    = Builder::escapeText($this->graph->getId(), false);
        $scriptText = "
declare Graph <=> (Page.GetFirstChild(\"{$graphId}\") as CMlGraph);
if (Graph != Null) {
    declare GraphCurve <=> Graph.AddCurve();
";
        foreach ($this->points as $point) {
            $pointVec2  = Builder::getVec2($point);
            $scriptText .= "
    GraphCurve.Points.add({$pointVec2});";
        }
        if ($this->sortPoints) {
            $scriptText .= "
    GraphCurve.SortPoints();";
        }
        if ($this->color) {
            $colorVec3  = Builder::getVec3($this->color);
            $scriptText .= "
    GraphCurve.Color = {$colorVec3};";
        }
        if ($this->style) {
            $scriptText .= "
    GraphCurve.Style = {$this->style};";
        }
        if ($this->width) {
            $scriptText .= "
    GraphCurve.Width = {$this->width};";
        }
        return $scriptText . "
}";
    }

}
