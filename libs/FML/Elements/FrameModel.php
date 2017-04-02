<?php

namespace FML\Elements;

use FML\Stylesheet\Style;
use FML\Types\Container;
use FML\Types\Identifiable;
use FML\Types\Renderable;
use FML\UniqueID;

/**
 * Class representing a Frame Model
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class FrameModel implements Container, Identifiable, Renderable
{

    /**
     * @var string $modelId Model id
     */
    protected $modelId = null;

    /**
     * @var Renderable[] $children Children
     */
    protected $children = array();

    /**
     * @var Format $format Format
     */
    protected $format = null;

    /**
     * Create a new Frame Model
     *
     * @api
     * @param string       $modelId  Model id
     * @param Renderable[] $children Children
     * @return static
     */
    public static function create($modelId = null, array $children = null)
    {
        return new static($modelId, $children);
    }

    /**
     * Construct a new Frame Model
     *
     * @api
     * @param string       $modelId  Model id
     * @param Renderable[] $children Children
     */
    public function __construct($modelId = null, array $children = null)
    {
        if ($modelId) {
            $this->setId($modelId);
        }
        if ($children) {
            $this->addChildren($children);
        }
    }

    /**
     * @see Identifiable::getId()
     */
    public function getId()
    {
        if (!$this->modelId) {
            return $this->createId();
        }
        return $this->modelId;
    }

    /**
     * @see Identifiable::setId()
     */
    public function setId($modelId)
    {
        $this->modelId = (string)$modelId;
        return $this;
    }

    /**
     * @see Identifiable::checkId()
     */
    public function checkId()
    {
        return UniqueID::check($this);
    }

    /**
     * Create a new model id
     *
     * @return string
     */
    protected function createId()
    {
        $modelId = UniqueID::create();
        $this->setId($modelId);
        return $this->getId();
    }

    /**
     * @see Container::getChildren()
     */
    public function getChildren()
    {
        return $this->children;
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
     * @deprecated Use addChild()
     * @see        FrameModel::addChild()
     */
    public function add(Renderable $child)
    {
        return $this->addChild($child);
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
     * @deprecated Use removeAllChildren()
     * @see        FrameModel::removeAllChildren()
     */
    public function removeChildren()
    {
        return $this->removeAllChildren();
    }

    /**
     * @deprecated Use Style
     * @see        Style
     */
    public function getFormat($createIfEmpty = true)
    {
        if (!$this->format && $createIfEmpty) {
            $this->setFormat(new Format());
        }
        return $this->format;
    }

    /**
     * @deprecated Use Style
     * @see        Style
     */
    public function setFormat(Format $format = null)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("framemodel");
        $domElement->setAttribute("id", $this->getId());

        if ($this->format) {
            $formatElement = $this->format->render($domDocument);
            $domElement->appendChild($formatElement);
        }

        foreach ($this->children as $child) {
            $childElement = $child->render($domDocument);
            $domElement->appendChild($childElement);
        }

        return $domElement;
    }

}
