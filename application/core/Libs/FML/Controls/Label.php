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
	 * Protected Properties
	 */
	protected $text = '';
	protected $textId = '';
	protected $textPrefix = '';
	protected $textEmboss = 0;
	protected $translate = 0;
	protected $maxLines = -1;
	protected $action = '';
	protected $actionKey = -1;
	protected $url = '';
	protected $urlId = '';
	protected $manialink = '';
	protected $manialinkId = '';
	protected $autoNewLine = 0;
	protected $scriptEvents = 0;
	protected $style = '';
	protected $textSize = -1;
	protected $textColor = '';
	protected $focusAreaColor1 = '';
	protected $focusAreaColor2 = '';

	/**
	 * Construct a new Label Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'label';
		$this->setZ(1);
	}

	/**
	 * Create a new Label Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Label
	 */
	public static function create($id = null) {
		$label = new Label($id);
		return $label;
	}

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlLabel';
	}

	/**
	 * Set Text
	 *
	 * @param string $text Text Value
	 * @return \FML\Controls\Label
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Set Text Id to use from the Dico
	 *
	 * @param string $textId Text Id
	 * @return \FML\Controls\Label
	 */
	public function setTextId($textId) {
		$this->textId = (string)$textId;
		return $this;
	}

	/**
	 * Set Text Prefix
	 *
	 * @param string $textPrefix Text Prefix
	 * @return \FML\Controls\Label
	 */
	public function setTextPrefix($textPrefix) {
		$this->textPrefix = (string)$textPrefix;
		return $this;
	}

	/**
	 * Set Text Emboss
	 *
	 * @param bool $textEmboss Whether Text should be embossed
	 * @return \FML\Controls\Label
	 */
	public function setTextEmboss($textEmboss) {
		$this->textEmboss = ($textEmboss ? 1 : 0);
		return $this;
	}

	/**
	 * Set Translate
	 *
	 * @param bool $translate Whether Text should be translated
	 * @return \FML\Controls\Label
	 */
	public function setTranslate($translate) {
		$this->translate = ($translate ? 1 : 0);
		return $this;
	}

	/**
	 * Set Max Lines Count
	 *
	 * @param int $maxLines Max Lines Count
	 * @return \FML\Controls\Label
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
	 * Add a dynamic Feature showing the current Time
	 *
	 * @param bool $showSeconds  (optional) Whether the Seconds should be shown
	 * @param bool $showFullDate (optional) Whether the Date should be shown
	 * @return \FML\Controls\Label
	 */
	public function addClockFeature($showSeconds = true, $showFullDate = false) {
		$clock = new Clock($this, $showSeconds, $showFullDate);
		$this->addScriptFeature($clock);
		return $this;
	}

	/**
	 * @see \FML\Control::render()
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
