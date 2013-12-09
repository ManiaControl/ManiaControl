<?php

namespace FML\Controls;

use FML\Types\Actionable;
use FML\Types\BgColorable;
use FML\Types\Linkable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\SubStyleable;

/**
 * Class representing CMlQuad
 *
 * @author steeffeen
 */
class Quad extends Control implements Actionable, BgColorable, Linkable, Scriptable, Styleable, SubStyleable {
	/**
	 * Protected properties
	 */
	protected $image = '';
	protected $imageFocus = '';
	protected $colorize = '';
	protected $modulizeColor = '';
	protected $action = '';
	protected $bgColor = '';
	protected $url = '';
	protected $manialink = '';
	protected $scriptEvents = 0;
	protected $style = '';
	protected $subStyle = '';

	/**
	 * Construct a new quad control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'quad';
		$this->setZ(-1);
	}

	/**
	 * Set image
	 *
	 * @param string $image        	
	 * @return \FML\Controls\Quad
	 */
	public function setImage($image) {
		$this->image = $image;
		return $this;
	}

	/**
	 * Set focus image
	 *
	 * @param string $imageFocus        	
	 * @return \FML\Controls\Quad
	 */
	public function setImageFocus($imageFocus) {
		$this->imageFocus = $imageFocus;
		return $this;
	}

	/**
	 * Set colorize
	 *
	 * @param string $colorize        	
	 * @return \FML\Controls\Quad
	 */
	public function setColorize($colorize) {
		$this->colorize = $colorize;
		return $this;
	}

	/**
	 * Set modulize color
	 *
	 * @param string $modulizeColor        	
	 * @return \FML\Controls\Quad
	 */
	public function setModulizeColor($modulizeColor) {
		$this->modulizeColor = $modulizeColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Actionable::setAction()
	 * @return \FML\Controls\Quad
	 */
	public function setAction($action) {
		$this->action = $action;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\BgColorable::setBgColor()
	 * @return \FML\Controls\Quad
	 */
	public function setBgColor($bgColor) {
		$this->bgColor = $bgColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Linkable::setUrl()
	 * @return \FML\Controls\Quad
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Linkable::setManialink()
	 * @return \FML\Controls\Quad
	 */
	public function setManialink($manialink) {
		$this->manialink = $manialink;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 * @return \FML\Controls\Quad
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Styleable::setStyle()
	 * @return \FML\Controls\Quad
	 */
	public function setStyle($style) {
		$this->style = $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\SubStyleable::setSubStyle()
	 * @return \FML\Controls\Quad
	 */
	public function setSubStyle($subStyle) {
		$this->subStyle = $subStyle;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\SubStyleable::setStyles()
	 * @return \FML\Controls\Quad
	 */
	public function setStyles($style, $subStyle) {
		$this->setStyle($style);
		$this->setSubStyle($subStyle);
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		if ($this->image) {
			$xml->setAttribute('image', $this->image);
		}
		if ($this->imageFocus) {
			$xml->setAttribute('imagefocus', $this->imageFocus);
		}
		if ($this->colorize) {
			$xml->setAttribute('colorize', $this->colorize);
		}
		if ($this->modulizeColor) {
			$xml->setAttribute('modulizecolor', $this->modulizeColor);
		}
		if ($this->action) {
			$xml->setAttribute('action', $this->action);
		}
		if ($this->bgColor) {
			$xml->setAttribute('bgcolor', $this->bgColor);
		}
		if ($this->url) {
			$xml->setAttribute('url', $this->url);
		}
		if ($this->manialink) {
			$xml->setAttribute('manialink', $this->manialink);
		}
		if ($this->scriptEvents) {
			$xml->setAttribute('scriptevents', $this->scriptEvents);
		}
		if ($this->style) {
			$xml->setAttribute('style', $this->style);
		}
		if ($this->subStyle) {
			$xml->setAttribute('substyle', $this->subStyle);
		}
		return $xml;
	}
}
