<?php

namespace FML\Controls;

use FML\Elements\Format;
use FML\Types\Container;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * Frame Control
 * (CMlFrame)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Frame extends Control implements Container
{

    /**
     * @var Renderable[] $children Children
     */
    protected $children = array();

    /**
     * @var Format $format Format
     */
    protected $format = null;

    /**
     * @see Container::getChildren()
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @see        Container::addChild()
     * @deprecated use addChild() instead
     */
    public function add(Renderable $child)
    {
        return $this->addChild($child);
    }

    /**
     * @see Container::addChild()
     */
    public function addChild(Renderable $child)
    {
        if (!in_array($child, $this->children, true)) {
            array_push($this->children, $child);
        }
        return $this;
    }

    /**
     * @see Container::addChildren()
     */
    public function addChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
        return $this;
    }

    /**
     * @see Container::removeAllChildren()
     */
    public function removeAllChildren()
    {
        $this->children = array();
        return $this;
    }

    /**
     * @see Container::getFormat()
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @see Container::setFormat()
     */
    public function setFormat(Format $format = null)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "frame";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlFrame";
    }

    /**
     * @see Control::getScriptFeatures()
     */
    public function getScriptFeatures()
    {
        $scriptFeatures = $this->scriptFeatures;
        foreach ($this->children as $child) {
            if ($child instanceof ScriptFeatureable) {
                $scriptFeatures = array_merge($scriptFeatures, $child->getScriptFeatures());
            }
        }
        return $scriptFeatures;
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->format) {
            $formatXml = $this->format->render($domDocument);
            $domElement->appendChild($formatXml);
        }
        foreach ($this->children as $child) {
            $childXmlElement = $child->render($domDocument);
            $domElement->appendChild($childXmlElement);
        }
        return $domElement;
    }

}
