<?php

namespace FML\Controls;

use FML\Models\CheckBoxDesign;
use FML\Types\Actionable;
use FML\Types\BgColorable;
use FML\Types\Linkable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\SubStyleable;

/**
 * Quad Control
 * (CMlQuad)
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad extends Control implements Actionable, BgColorable, Linkable, Scriptable, Styleable, SubStyleable {
	/*
	 * Protected Properties
	 */
	protected $image = '';
	protected $imageId = '';
	protected $imageFocus = '';
	protected $imageFocusId = '';
	protected $colorize = '';
	protected $modulizeColor = '';
	protected $autoScale = 1;
	protected $action = '';
	protected $actionKey = -1;
	protected $bgColor = '';
	protected $url = '';
	protected $urlId = '';
	protected $manialink = '';
	protected $manialinkId = '';
	protected $scriptEvents = 0;
	protected $style = '';
	protected $subStyle = '';

	/**
	 * Create a new Quad Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quad
	 */
	public static function create($id = null) {
		$quad = new Quad($id);
		return $quad;
	}

	/**
	 * Construct a new Quad Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'quad';
		$this->setZ(-1);
	}

	/**
	 * Set Image Url
	 *
	 * @param string $image Image Url
	 * @return \FML\Controls\Quad
	 */
	public function setImage($image) {
		$this->image = (string)$image;
		return $this;
	}

	/**
	 * Set Image Id to use from the Dico
	 *
	 * @param string $imageId Image Id
	 * @return \FML\Controls\Quad
	 */
	public function setImageId($imageId) {
		$this->imageId = (string)$imageId;
		return $this;
	}

	/**
	 * Set Focus Image Url
	 *
	 * @param string $imageFocus Focus Image Url
	 * @return \FML\Controls\Quad
	 */
	public function setImageFocus($imageFocus) {
		$this->imageFocus = (string)$imageFocus;
		return $this;
	}

	/**
	 * Set Focus Image Id to use from the Dico
	 *
	 * @param string $imageFocusId Focus Image Id
	 * @return \FML\Controls\Quad
	 */
	public function setImageFocusId($imageFocusId) {
		$this->imageFocusId = (string)$imageFocusId;
		return $this;
	}

	/**
	 * Set Colorization
	 *
	 * @param string $colorize Colorize Value
	 * @return \FML\Controls\Quad
	 */
	public function setColorize($colorize) {
		$this->colorize = (string)$colorize;
		return $this;
	}

	/**
	 * Set Modulization
	 *
	 * @param string $modulizeColor Modulize Value
	 * @return \FML\Controls\Quad
	 */
	public function setModulizeColor($modulizeColor) {
		$this->modulizeColor = (string)$modulizeColor;
		return $this;
	}

	/**
	 * Disable the automatic Image Scaling
	 *
	 * @param bool $autoScale Whether the Image should scale automatically
	 * @return \FML\Controls\Quad
	 */
	public function setAutoScale($autoScale) {
		$this->autoScale = ($autoScale ? 1 : 0);
		return $this;
	}

	/**
	 * @see \FML\Types\Actionable::setAction()
	 */
	public function setAction($action) {
		$this->action = (string)$action;
		return $this;
	}

	/**
	 * @see \FML\Types\Actionable::getAction()
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @see \FML\Types\Actionable::setActionKey()
	 */
	public function setActionKey($actionKey) {
		$this->actionKey = (int)$actionKey;
		return $this;
	}

	/**
	 * @see \FML\Types\BgColorable::setBgColor()
	 */
	public function setBgColor($bgColor) {
		$this->bgColor = (string)$bgColor;
		return $this;
	}

	/**
	 * @see \FML\Types\Linkable::setUrl()
	 */
	public function setUrl($url) {
		$this->url = (string)$url;
		return $this;
	}

	/**
	 * @see \FML\Types\Linkable::setUrlId()
	 */
	public function setUrlId($urlId) {
		$this->urlId = (string)$urlId;
		return $this;
	}

	/**
	 * @see \FML\Types\Linkable::setManialink()
	 */
	public function setManialink($manialink) {
		$this->manialink = (string)$manialink;
		return $this;
	}

	/**
	 * @see \FML\Types\Linkable::setManialinkId()
	 */
	public function setManialinkId($manialinkId) {
		$this->manialinkId = (string)$manialinkId;
		return $this;
	}

	/**
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
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
	 * @see \FML\Types\SubStyleable::setSubStyle()
	 */
	public function setSubStyle($subStyle) {
		$this->subStyle = (string)$subStyle;
		return $this;
	}

	/**
	 * @see \FML\Types\SubStyleable::setStyles()
	 */
	public function setStyles($style, $subStyle) {
		$this->setStyle($style);
		$this->setSubStyle($subStyle);
		return $this;
	}

	/**
	 * Apply the given CheckBox Design
	 *
	 * @param CheckBoxDesign $checkBoxDesign CheckBox Design
	 * @return \FML\Controls\Quad
	 */
	public function applyCheckBoxDesign(CheckBoxDesign $checkBoxDesign) {
		$checkBoxDesign->applyToQuad($this);
		return $this;
	}

	/**
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->image) {
			$xmlElement->setAttribute('image', $this->image);
		}
		if ($this->imageId) {
			$xmlElement->setAttribute('imageid', $this->imageId);
		}
		if ($this->imageFocus) {
			$xmlElement->setAttribute('imagefocus', $this->imageFocus);
		}
		if ($this->imageFocusId) {
			$xmlElement->setAttribute('imagefocusid', $this->imageFocusId);
		}
		if ($this->colorize) {
			$xmlElement->setAttribute('colorize', $this->colorize);
		}
		if ($this->modulizeColor) {
			$xmlElement->setAttribute('modulizecolor', $this->modulizeColor);
		}
		if (!$this->autoScale) {
			$xmlElement->setAttribute('autoscale', $this->autoScale);
		}
		if (strlen($this->action) > 0) {
			$xmlElement->setAttribute('action', $this->action);
		}
		if ($this->actionKey >= 0) {
			$xmlElement->setAttribute('actionkey', $this->actionKey);
		}
		if ($this->bgColor) {
			$xmlElement->setAttribute('bgcolor', $this->bgColor);
		}
		if ($this->url) {
			$xmlElement->setAttribute('url', $this->url);
		}
		if ($this->manialink) {
			$xmlElement->setAttribute('manialink', $this->manialink);
		}
		if ($this->scriptEvents) {
			$xmlElement->setAttribute('scriptevents', $this->scriptEvents);
		}
		if ($this->style) {
			$xmlElement->setAttribute('style', $this->style);
		}
		if ($this->subStyle) {
			$xmlElement->setAttribute('substyle', $this->subStyle);
		}
		return $xmlElement;
	}
}
