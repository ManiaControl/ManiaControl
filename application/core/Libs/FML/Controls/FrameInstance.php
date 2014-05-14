<?php

namespace FML\Controls;

use FML\Elements\FrameModel;

/**
 * Class representing an Instance of a Frame Model
 * (CMlFrame)
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class FrameInstance extends Control {
	/*
	 * Protected Properties
	 */
	protected $modelId = '';
	/** @var FrameModel $model */
	protected $model = null;

	/**
	 * Create a new Frame Instance
	 *
	 * @param string $modelId   (optional) Frame Model Id
	 * @param string $controlId (optional) Control Id
	 * @return \FML\Controls\Frame
	 */
	public static function create($modelId = null, $controlId = null) {
		$frameInstance = new FrameInstance($modelId, $controlId);
		return $frameInstance;
	}

	/**
	 * Construct a new Frame Instance
	 *
	 * @param string $modelId   (optional) Frame Model Id
	 * @param string $controlId (optional) Control Id
	 */
	public function __construct($modelId = null, $controlId = null) {
		parent::__construct($controlId);
		$this->tagName = 'frameinstance';
		if ($modelId !== null) {
			$this->setModelId($modelId);
		}
	}

	/**
	 * Set Model Id
	 *
	 * @param string $modelId Model Id
	 * @return \FML\Controls\FrameInstance
	 */
	public function setModelId($modelId) {
		$this->modelId = (string)$modelId;
		$this->model   = null;
		return $this;
	}

	/**
	 * Set Frame Model to use
	 *
	 * @param FrameModel $frameModel Frame Model
	 * @return \FML\Controls\FrameInstance
	 */
	public function setModel(FrameModel $frameModel) {
		$this->model   = $frameModel;
		$this->modelId = '';
		return $this;
	}

	/**
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->model) {
			$this->model->checkId();
			$xmlElement->setAttribute('modelid', $this->model->getId());
		} else if ($this->modelId) {
			$xmlElement->setAttribute('modelid', $this->modelId);
		}
		return $xmlElement;
	}
}
