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
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad extends Control implements Actionable, BgColorable, Linkable, Scriptable, Styleable, SubStyleable {
	/*
	 * Constants
	 */
	const KEEP_RATIO_INACTIVE = 'inactive';
	const KEEP_RATIO_CLIP     = 'Clip';
	const KEEP_RATIO_FIT      = 'Fit';

	/*
	 * Protected properties
	 */
	protected $tagName = 'quad';
	protected $image = null;
	protected $imageId = null;
	protected $imageFocus = null;
	protected $imageFocusId = null;
	protected $colorize = null;
	protected $modulizeColor = null;
	protected $autoScale = 1;
	protected $keepRatio = null;
	protected $action = null;
	protected $actionKey = -1;
	protected $bgColor = null;
	protected $url = null;
	protected $urlId = null;
	protected $manialink = null;
	protected $manialinkId = null;
	protected $scriptEvents = null;
	protected $style = null;
	protected $subStyle = null;
	protected $styleSelected = null;
	protected $opacity = null;

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlQuad';
	}

	/**
	 * Set image url
	 *
	 * @param string $image Image url
	 * @return static
	 */
	public function setImage($image) {
		$this->image = (string)$image;
		return $this;
	}

	/**
	 * Set image id to use from Dico
	 *
	 * @param string $imageId Image id
	 * @return static
	 */
	public function setImageId($imageId) {
		$this->imageId = (string)$imageId;
		return $this;
	}

	/**
	 * Set focus image url
	 *
	 * @param string $imageFocus Focus image url
	 * @return static
	 */
	public function setImageFocus($imageFocus) {
		$this->imageFocus = (string)$imageFocus;
		return $this;
	}

	/**
	 * Set focus image id to use from Dico
	 *
	 * @param string $imageFocusId Focus image id
	 * @return static
	 */
	public function setImageFocusId($imageFocusId) {
		$this->imageFocusId = (string)$imageFocusId;
		return $this;
	}

	/**
	 * Set colorization
	 *
	 * @param string $colorize Colorize value
	 * @return static
	 */
	public function setColorize($colorize) {
		$this->colorize = (string)$colorize;
		return $this;
	}

	/**
	 * Set modulization
	 *
	 * @param string $modulizeColor Modulize value
	 * @return static
	 */
	public function setModulizeColor($modulizeColor) {
		$this->modulizeColor = (string)$modulizeColor;
		return $this;
	}

	/**
	 * Disable the automatic image scaling
	 *
	 * @param bool $autoScale Whether the image should scale automatically
	 * @return static
	 */
	public function setAutoScale($autoScale) {
		$this->autoScale = ($autoScale ? 1 : 0);
		return $this;
	}

	/**
	 * Set Keep Ratio Mode
	 *
	 * @param string $keepRatio Keep Ratio Mode
	 * @return static
	 */
	public function setKeepRatio($keepRatio) {
		$this->keepRatio = (string)$keepRatio;
		return $this;
	}

	/**
	 * @see \FML\Types\Actionable::getAction()
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @see \FML\Types\Actionable::setAction()
	 */
	public function setAction($action) {
		$this->action = (string)$action;
		return $this;
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
	 * @see \FML\Types\SubStyleable::setStyles()
	 */
	public function setStyles($style, $subStyle) {
		$this->setStyle($style);
		$this->setSubStyle($subStyle);
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
	 * Set selected mode
	 *
	 * @param bool $styleSelected
	 * @return static
	 */
	public function setStyleSelected($styleSelected) {
		$this->styleSelected = ($styleSelected ? 1 : 0);
		return $this;
	}

	/**
	 * Set opacity
	 *
	 * @param float $opacity
	 * @return static
	 */
	public function setOpacity($opacity) {
		$this->opacity = (float)$opacity;
		return $this;
	}

	/**
	 * Apply the given CheckBox Design
	 *
	 * @param CheckBoxDesign $checkBoxDesign CheckBox Design
	 * @return static
	 */
	public function applyCheckBoxDesign(CheckBoxDesign $checkBoxDesign) {
		$checkBoxDesign->applyToQuad($this);
		return $this;
	}

	/**
	 * @see \FML\Types\Renderable::render()
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
		if ($this->keepRatio) {
			$xmlElement->setAttribute('keepratio', $this->keepRatio);
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
		if ($this->styleSelected) {
			$xmlElement->setAttribute('styleselected', $this->styleSelected);
		}
		if ($this->opacity !== 1.) {
			$xmlElement->setAttribute('opacity', $this->opacity);
		}
		return $xmlElement;
	}
}
