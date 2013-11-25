<?php

namespace FML\Controls;

use FML\Types\Linkable;
use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Class representing CMlLabel
 *
 * @author steeffeen
 */
class Label extends Control implements Linkable, NewLineable, Scriptable, Styleable, TextFormatable {
	/**
	 * Protected properties
	 */
	protected $text = '';
	protected $textPrefix = '';
	protected $textEmboss = 0;
	protected $maxLines = 0;
	protected $url = '';
	protected $manialink = '';
	protected $autoNewLine = 0;
	protected $scriptEvents = 0;
	protected $style = '';
	protected $textSize = -1;
	protected $textColor = '';
	protected $areaColor = '';
	protected $areaFocusColor = '';

	/**
	 * Construct label control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'label';
		$this->setZ(1);
	}

	/**
	 * Set text
	 *
	 * @param string $text        	
	 * @return \FML\Controls\Label
	 */
	public function setText($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 * Set text prefix
	 *
	 * @param string $textPrefix        	
	 * @return \FML\Controls\Label
	 */
	public function setTextPrefix($textPrefix) {
		$this->textPrefix = $textPrefix;
		return $this;
	}

	/**
	 * Set text emboss
	 *
	 * @param bool $textEmboss        	
	 * @return \FML\Controls\Label
	 */
	public function setTextEmboss($textEmboss) {
		$this->textEmboss = ($textEmboss ? 1 : 0);
		return $this;
	}

	/**
	 * Set max lines
	 *
	 * @param int $maxLines        	
	 * @return \FML\Controls\Label
	 */
	public function setMaxLines($maxLines) {
		$this->maxLines = $maxLines;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Linkable::setUrl()
	 * @return \FML\Controls\Label
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Linkable::setManialink()
	 * @return \FML\Controls\Label
	 */
	public function setManialink($manialink) {
		$this->manialink = $manialink;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\NewLineable::setAutoNewLine()
	 * @return \FML\Controls\Label
	 */
	public function setAutoNewLine($autoNewLine) {
		$this->autoNewLine = ($autoNewLine ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 * @return \FML\Controls\Label
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Styleable::setStyle()
	 * @return \FML\Controls\Label
	 */
	public function setStyle($style) {
		$this->style = $style;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextSize()
	 * @return \FML\Controls\Label
	 */
	public function setTextSize($textSize) {
		$this->textSize = $textSize;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setTextColor()
	 * @return \FML\Controls\Label
	 */
	public function setTextColor($textColor) {
		$this->textColor = $textColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaColor()
	 * @return \FML\Controls\Label
	 */
	public function setAreaColor($areaColor) {
		$this->areaColor = $areaColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\TextFormatable::setAreaFocusColor()
	 * @return \FML\Controls\Label
	 */
	public function setAreaFocusColor($areaFocusColor) {
		$this->areaFocusColor = $areaFocusColor;
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		if ($this->text) {
			$xml->setAttribute('text', $this->text);
		}
		if ($this->textPrefix) {
			$xml->setAttribute('textprefix', $this->textPrefix);
		}
		if ($this->textEmboss) {
			$xml->setAttribute('textemboss', $this->textEmboss);
		}
		if ($this->maxLines) {
			$xml->setAttribute('maxlines', $this->maxLines);
		}
		if ($this->url) {
			$xml->setAttribute('url', $this->url);
		}
		if ($this->manialink) {
			$xml->setAttribute('manialink', $this->manialink);
		}
		if ($this->autoNewLine) {
			$xml->setAttribute('autonewline', $this->autoNewLine);
		}
		if ($this->scriptEvents) {
			$xml->setAttribute('scriptevents', $this->scriptEvents);
		}
		if ($this->style) {
			$xml->setAttribute('style', $this->style);
		}
		if ($this->textSize >= 0) {
			$xml->setAttribute('textsize', $this->textSize);
		}
		if ($this->textColor) {
			$xml->setAttribute('textcolor', $this->textColor);
		}
		if ($this->areaColor) {
			$xml->setAttribute('areacolor', $this->areaColor);
		}
		if ($this->areaFocusColor) {
			$xml->setAttribute('areafocuscolor', $this->areaFocusColor);
		}
		return $xml;
	}
}

?>
