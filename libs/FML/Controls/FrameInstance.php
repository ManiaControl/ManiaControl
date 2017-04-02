<?php

namespace FML\Controls;

use FML\Elements\FrameModel;

/**
 * Class representing an instance of a Frame Model
 * (CMlFrame)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class FrameInstance extends Control
{

    /**
     * @var string $modelId FrameModel id
     */
    protected $modelId = null;

    /**
     * @var FrameModel $model FrameModel
     */
    protected $model = null;

    /**
     * Create a new Frame Instance
     *
     * @api
     * @param string $controlId (optional) Control Id
     * @param string $modelId   (optional) Model Id
     * @return static
     */
    public static function create($controlId = null, $modelId = null)
    {
        return new static($controlId, $modelId);
    }

    /**
     * Construct a new Frame Instance
     *
     * @api
     * @param string $controlId (optional) Control Id
     * @param string $modelId   (optional) Model Id
     */
    public function __construct($controlId = null, $modelId = null)
    {
        parent::__construct($controlId);
        if ($modelId) {
            $this->setModelId($modelId);
        }
    }

    /**
     * Get the FrameModel id
     *
     * @api
     * @return string
     */
    public function getModelId()
    {
        return $this->modelId;
    }

    /**
     * Set the FrameModel id
     *
     * @api
     * @param string $modelId FrameModel id
     * @return static
     */
    public function setModelId($modelId)
    {
        $this->modelId = (string)$modelId;
        $this->model   = null;
        return $this;
    }

    /**
     * Get the FrameModel
     *
     * @api
     * @return FrameModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the FrameModel
     *
     * @api
     * @param FrameModel $frameModel FrameModel
     * @return static
     */
    public function setModel(FrameModel $frameModel)
    {
        $this->modelId = null;
        $this->model   = $frameModel;
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "frameinstance";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlFrame";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->model) {
            $this->model->checkId();
            $domElement->setAttribute("modelid", $this->model->getId());
        } else if ($this->modelId) {
            $domElement->setAttribute("modelid", $this->modelId);
        }
        return $domElement;
    }

}
