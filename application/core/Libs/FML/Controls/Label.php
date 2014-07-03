<?php

namespace FML\Controls;

use FML\Script\Features\Clock;
use FML\Types\Actionable;
use FML\Types\Linkable;
use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Label Control
 * (CMlLabel)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Label extends Control implements Actionable, Linkable, NewLineable, Scriptable, Styleable, TextFormatable {
	/*
	 * Protected properties
	 */
	protected $tagName = 'label';
	protected $text = null;
	protected $textId = null;
	protected $textPrefix = null;
	protected $textEmboss = null;
	protected $translate = null;
	protected $maxLines = -1;
	protected $opacity = 1.;
	protected $action = null;
	protected $actionKey = -1;
	protected $url = null;
	protected $urlId = null;
	protected $manialink = null;
	protected $manialinkId = null;
	protected $autoNewLine = null;
	protected $scriptEvents = null;
	protected $style = null;
	protected $textSize = -1;
	protected $textColor = null;
	protected $focusAreaColor1 = null;
	protected $focusAreaColor2 = null;

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlLabel';
	}

	/**
	 * Set text
	 *
	 * @param string $text Text value
	 * @return static
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Set text id to use from Dico
	 *
	 * @param string $textId Text id
	 * @return static
	 */
	public function setTextId($textId) {
		$this->textId = (string)$textId;
		return $this;
	}

	/**
	 * Set text prefix
	 *
	 * @param string $textPrefix Text prefix
	 * @return static
	 */
	public function setTextPrefix($textPrefix) {
		$this->textPrefix = (string)$textPrefix;
		return $this;
	}

	/**
	 * Set text emboss
	 *
	 * @param bool $textEmboss Whether the text should be embossed
	 * @return static
	 */
	public function setTextEmboss($textEmboss) {
		$this->textEmboss = ($textEmboss ? 1 : 0);
		return $this;
	}

	/**
	 * Set translate
	 *
	 * @param bool $translate Whether the text should be translated
	 * @return static
	 */
	public function setTranslate($translate) {
		$this->translate = ($translate ? 1 : 0);
		return $this;
	}

	/**
	 * Set max lines count
	 *
	 * @param int $maxLines Max lines count
	 * @return static
	 */
	public function setMaxLines($maxLines) {
		$this->maxLines = (int)$maxLines;
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
	 * @see \FML\Types\NewLineable::setAutoNewLine()
	 */
	public function setAutoNewLine($autoNewLine) {
		$this->autoNewLine = ($autoNewLine ? 1 : 0);
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
	 * @see \FML\Types\TextFormatable::setTextSize()
	 */
	public function setTextSize($textSize) {
		$this->textSize = (int)$textSize;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setTextColor()
	 */
	public function setTextColor($textColor) {
		$this->textColor = (string)$textColor;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setAreaColor()
	 */
	public function setAreaColor($areaColor) {
		$this->focusAreaColor1 = (string)$areaColor;
		return $this;
	}

	/**
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->focusAreaColor2 = (string)$areaFocusColor;
		return $this;
	}

	/**
	 * Add a dynamic Feature showing the current time
	 *
	 * @param bool $showSeconds  (optional) Whether the seconds should be shown
	 * @param bool $showFullDate (optional) Whether the date should be shown
	 * @return static
	 */
	public function addClockFeature($showSeconds = true, $showFullDate = false) {
		$clock = new Clock($this, $showSeconds, $showFullDate);
		$this->addScriptFeature($clock);
		return $this;
	}

	/**
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if (strlen($this->text) > 0) {
			$xmlElement->setAttribute('text', $this->text);
		}
		if ($this->textId) {
			$xmlElement->setAttribute('textid', $this->textId);
		}
		if ($this->textPrefix) {
			$xmlElement->setAttribute('textprefix', $this->textPrefix);
		}
		if ($this->textEmboss) {
			$xmlElement->setAttribute('textemboss', $this->textEmboss);
		}
		if ($this->translate) {
			$xmlElement->setAttribute('translate', $this->translate);
		}
		if ($this->maxLines >= 0) {
			$xmlElement->setAttribute('maxlines', $this->maxLines);
		}
		if ($this->opacity != 1.) {
			$xmlElement->setAttribute('opacity', $this->opacity);
		}
		if (strlen($this->action) > 0) {
			$xmlElement->setAttribute('action', $this->action);
		}
		if ($this->actionKey >= 0) {
			$xmlElement->setAttribute('actionkey', $this->actionKey);
		}
		if ($this->url) {
			$xmlElement->setAttribute('url', $this->url);
		}
		if ($this->manialink) {
			$xmlElement->setAttribute('manialink', $this->manialink);
		}
		if ($this->autoNewLine) {
			$xmlElement->setAttribute('autonewline', $this->autoNewLine);
		}
		if ($this->scriptEvents) {
			$xmlElement->setAttribute('scriptevents', $this->scriptEvents);
		}
		if ($this->style) {
			$xmlElement->setAttribute('style', $this->style);
		}
		if ($this->textSize >= 0) {
			$xmlElement->setAttribute('textsize', $this->textSize);
		}
		if ($this->textColor) {
			$xmlElement->setAttribute('textcolor', $this->textColor);
		}
		if ($this->focusAreaColor1) {
			$xmlElement->setAttribute('focusareacolor1', $this->focusAreaColor1);
		}
		if ($this->focusAreaColor2) {
			$xmlElement->setAttribute('focusareacolor2', $this->focusAreaColor2);
		}
		return $xmlElement;
	}
}
