<?php

namespace FML\Stylesheet;

/**
 * Class representing a specific Style3d
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Style3d {
	/*
	 * Constants
	 */
	const MODEL_Box     = 'Box';
	const MODEL_Button  = 'Button';
	const MODEL_ButtonH = 'ButtonH';
	const MODEL_Title   = 'Title';
	const MODEL_Window  = 'Window';

	/*
	 * Protected Properties
	 */
	protected $tagName = 'style3d';
	protected $id = null;
	protected $model = self::MODEL_Box;
	protected $thickness = null;
	protected $color = null;
	protected $focusColor = null;
	protected $lightColor = null;
	protected $focusLightColor = null;
	protected $yOffset = null;
	protected $focusYOffset = null;
	protected $zOffset = null;
	protected $focusZOffset = null;

	/**
	 * Create a new Style3d Object
	 *
	 * @param string $id (optional) Style Id
	 * @return \FML\Stylesheet\Style3d
	 */
	public static function create($id = null) {
		$style3d = new Style3d($id);
		return $style3d;
	}

	/**
	 * Construct a new Style3d Object
	 *
	 * @param string $id (optional) Style Id
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Set Style Id
	 *
	 * @param string $id Style Id
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setId($id) {
		$this->id = (string)$id;
		return $this;
	}

	/**
	 * Check for Id and assign one if necessary
	 *
	 * @return \FML\Stylesheet\Style3d
	 */
	public function checkId() {
		if (!$this->id) {
			$this->id = uniqid();
		}
		return $this;
	}

	/**
	 * Get Style Id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set Model
	 *
	 * @param string $model Style Model
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setModel($model) {
		$this->model = (string)$model;
		return $this;
	}

	/**
	 * Set Thickness
	 *
	 * @param float $thickness Style Thickness
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setThickness($thickness) {
		$this->thickness = (float)$thickness;
		return $this;
	}

	/**
	 * Set Color
	 *
	 * @param string $color Style Color
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setColor($color) {
		$this->color = (string)$color;
		return $this;
	}

	/**
	 * Set Focus Color
	 *
	 * @param string $focusColor Style Focus Color
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setFocusColor($focusColor) {
		$this->focusColor = (string)$focusColor;
		return $this;
	}

	/**
	 * Set Light Color
	 *
	 * @param string $lightColor Light Color
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setLightColor($lightColor) {
		$this->lightColor = (string)$lightColor;
		return $this;
	}

	/**
	 * Set Focus Light Color
	 *
	 * @param string $focusLightColor Focus Light Color
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setFocusLightColor($focusLightColor) {
		$this->focusLightColor = (string)$focusLightColor;
		return $this;
	}

	/**
	 * Set Y-Offset
	 *
	 * @param float $yOffset Y-Offset
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setYOffset($yOffset) {
		$this->yOffset = (float)$yOffset;
		return $this;
	}

	/**
	 * Set Focus Y-Offset
	 *
	 * @param float $focusYOffset Focus Y-Offset
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setFocusYOffset($focusYOffset) {
		$this->focusYOffset = (float)$focusYOffset;
		return $this;
	}

	/**
	 * Set Z-Offset
	 *
	 * @param float $zOffset Z-Offset
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setZOffset($zOffset) {
		$this->zOffset = (float)$zOffset;
		return $this;
	}

	/**
	 * Set Focus Z-Offset
	 *
	 * @param float $focusZOffset Focus Z-Offset
	 * @return \FML\Stylesheet\Style3d
	 */
	public function setFocusZOffset($focusZOffset) {
		$this->focusZOffset = (float)$focusZOffset;
		return $this;
	}

	/**
	 * Render the Style3d XML Element
	 *
	 * @param \DOMDocument $domDocument DomDocument for which the Style3d XML Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$style3dXml = $domDocument->createElement($this->tagName);
		$this->checkId();
		if ($this->id) {
			$style3dXml->setAttribute('id', $this->id);
		}
		if ($this->model) {
			$style3dXml->setAttribute('model', $this->model);
		}
		if ($this->thickness) {
			$style3dXml->setAttribute('thickness', $this->thickness);
		}
		if ($this->color) {
			$style3dXml->setAttribute('color', $this->color);
		}
		if ($this->focusColor) {
			$style3dXml->setAttribute('fcolor', $this->focusColor);
		}
		if ($this->lightColor) {
			$style3dXml->setAttribute('lightcolor', $this->lightColor);
		}
		if ($this->focusLightColor) {
			$style3dXml->setAttribute('flightcolor', $this->focusLightColor);
		}
		if ($this->yOffset) {
			$style3dXml->setAttribute('yoffset', $this->yOffset);
		}
		if ($this->focusYOffset) {
			$style3dXml->setAttribute('fyoffset', $this->focusYOffset);
		}
		if ($this->zOffset) {
			$style3dXml->setAttribute('zoffset', $this->zOffset);
		}
		if ($this->focusZOffset) {
			$style3dXml->setAttribute('fzoffset', $this->focusZOffset);
		}
		return $style3dXml;
	}
}
