<?php

namespace FML\Controls;

use FML\Script\Builder;
use FML\Script\Features\ActionTrigger;
use FML\Script\Features\ControlScript;
use FML\Script\Features\MapInfo;
use FML\Script\Features\PlayerProfile;
use FML\Script\Features\ScriptFeature;
use FML\Script\Features\Toggle;
use FML\Script\Features\Tooltip;
use FML\Script\Features\UISound;
use FML\Script\ScriptLabel;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;
use FML\UniqueID;

/**
 * Base Control
 * (CMlControl)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Control implements Renderable, ScriptFeatureable {
	/*
	 * Constants
	 */
	const CENTER  = 'center';
	const CENTER2 = 'center2';
	const TOP     = 'top';
	const RIGHT   = 'right';
	const BOTTOM  = 'bottom';
	const LEFT    = 'left';

	/*
	 * Protected properties
	 */
	protected $tagName = 'control';
	protected $controlId = null;
	protected $posX = 0.;
	protected $posY = 0.;
	protected $posZ = 0.;
	protected $width = -1.;
	protected $height = -1.;
	protected $hAlign = self::CENTER;
	protected $vAlign = self::CENTER2;
	protected $scale = 1.;
	protected $hidden = null;
	protected $rotation = 0.;
	/** @var string[] $classes */
	protected $classes = array();
	/** @var ScriptFeature[] $scriptFeatures */
	protected $scriptFeatures = array();

	/**
	 * Create a new Control object
	 *
	 * @param string $controlId (optional) Control id
	 * @return static
	 */
	public static function create($controlId = null) {
		return new static($controlId);
	}

	/**
	 * Construct a new Control object
	 *
	 * @param string $controlId (optional) Control id
	 */
	public function __construct($controlId = null) {
		if (!is_null($controlId)) {
			$this->setId($controlId);
		}
	}

	/**
	 * Check Id for dangerous characters and assign a new unique id if necessary
	 *
	 * @param bool $forceNewId (optional) Whether to force setting a newly generated id
	 * @return static
	 */
	public function checkId($forceNewId = false) {
		if ($forceNewId || !$this->getId()) {
			$this->setId(new UniqueID());
			return $this;
		}
		$dangerousCharacters = array(' ', '	', '.', '|', '-', PHP_EOL);
		$idCharacters        = str_split($this->getId());
		$danger              = false;
		foreach ($idCharacters as $character) {
			if (!in_array($character, $dangerousCharacters)) {
				continue;
			}
			$danger = true;
			break;
		}
		if ($danger) {
			trigger_error("Please don't use special characters in ids, they might cause problems! (I stripped them for you.)");
			$controlId = str_ireplace($dangerousCharacters, '', $this->getId());
			$this->setId($controlId);
		}
		return $this;
	}

	/**
	 * Get the Control id
	 *
	 * @param bool $escaped        (optional) Whether the id should be escaped for ManiaScript
	 * @param bool $addApostrophes (optional) Whether to add apostrophes before and after the text
	 * @return string
	 */
	public function getId($escaped = false, $addApostrophes = false) {
		if ($escaped) {
			return Builder::escapeText($this->controlId, $addApostrophes);
		}
		return $this->controlId;
	}

	/**
	 * Set Control id
	 *
	 * @param string $controlId Control id
	 * @return static
	 */
	public function setId($controlId) {
		$this->controlId = (string)$controlId;
		return $this;
	}

	/**
	 * Set Control position
	 *
	 * @param float $posX Horizontal position
	 * @param float $posY Vertical position
	 * @param float $posZ (optional) Depth
	 * @return static
	 */
	public function setPosition($posX, $posY, $posZ = null) {
		$this->setX($posX);
		$this->setY($posY);
		if (!is_null($posZ)) {
			$this->setZ($posZ);
		}
		return $this;
	}

	/**
	 * Set X position
	 *
	 * @param float $posX Horizontal position
	 * @return static
	 */
	public function setX($posX) {
		$this->posX = (float)$posX;
		return $this;
	}

	/**
	 * Set Y position
	 *
	 * @param float $posY Vertical position
	 * @return static
	 */
	public function setY($posY) {
		$this->posY = (float)$posY;
		return $this;
	}

	/**
	 * Set Z position
	 *
	 * @param float $posZ Depth
	 * @return static
	 */
	public function setZ($posZ) {
		$this->posZ = (float)$posZ;
		return $this;
	}

	/**
	 * Set Control size
	 *
	 * @param float $width  Control width
	 * @param float $height Control height
	 * @return static
	 */
	public function setSize($width, $height) {
		$this->setWidth($width);
		$this->setHeight($height);
		return $this;
	}

	/**
	 * Set Control width
	 *
	 * @param float $width Control width
	 * @return static
	 */
	public function setWidth($width) {
		$this->width = (float)$width;
		return $this;
	}

	/**
	 * Set Control height
	 *
	 * @param float $height Control height
	 * @return static
	 */
	public function setHeight($height) {
		$this->height = (float)$height;
		return $this;
	}

	/**
	 * Center alignment
	 *
	 * @return static
	 */
	public function centerAlign() {
		$this->setAlign(self::CENTER, self::CENTER2);
		return $this;
	}

	/**
	 * Set horizontal and vertical alignment
	 *
	 * @param string $hAlign Horizontal alignment
	 * @param string $vAlign Vertical alignment
	 * @return static
	 */
	public function setAlign($hAlign, $vAlign) {
		$this->setHAlign($hAlign);
		$this->setVAlign($vAlign);
		return $this;
	}

	/**
	 * Set horizontal alignment
	 *
	 * @param string $hAlign Horizontal alignment
	 * @return static
	 */
	public function setHAlign($hAlign) {
		$this->hAlign = (string)$hAlign;
		return $this;
	}

	/**
	 * Set vertical alignment
	 *
	 * @param string $vAlign Vertical alignment
	 * @return static
	 */
	public function setVAlign($vAlign) {
		$this->vAlign = (string)$vAlign;
		return $this;
	}

	/**
	 * Reset alignment
	 *
	 * @return static
	 */
	public function resetAlign() {
		$this->setAlign(null, null);
		return $this;
	}

	/**
	 * Set Control scale
	 *
	 * @param float $scale Control scale
	 * @return static
	 */
	public function setScale($scale) {
		$this->scale = (float)$scale;
		return $this;
	}

	/**
	 * Set visibility
	 *
	 * @param bool $visible Whether the Control should be visible
	 * @return static
	 */
	public function setVisible($visible = true) {
		$this->hidden = ($visible ? 0 : 1);
		return $this;
	}

	/**
	 * Set Control rotation
	 *
	 * @param float $rotation Control rotation
	 * @return static
	 */
	public function setRotation($rotation) {
		$this->rotation = (float)$rotation;
		return $this;
	}

	/**
	 * Add a new class name
	 *
	 * @param string $class Class name
	 * @return static
	 */
	public function addClass($class) {
		$class = (string)$class;
		if (!in_array($class, $this->classes)) {
			array_push($this->classes, $class);
		}
		return $this;
	}

	/**
	 * Add a dynamic Action Trigger
	 *
	 * @param string $actionName Action to trigger
	 * @param string $eventLabel (optional) Event on which the action is triggered
	 * @return static
	 */
	public function addActionTriggerFeature($actionName, $eventLabel = ScriptLabel::MOUSECLICK) {
		if (is_object($actionName) && ($actionName instanceof ActionTrigger)) {
			$this->addScriptFeature($actionName);
		} else {
			$actionTrigger = new ActionTrigger($actionName, $this, $eventLabel);
			$this->addScriptFeature($actionTrigger);
		}
		return $this;
	}

	/**
	 * Add a new Script Feature
	 *
	 * @param ScriptFeature $scriptFeature Script Feature
	 * @return static
	 */
	public function addScriptFeature(ScriptFeature $scriptFeature) {
		if (!in_array($scriptFeature, $this->scriptFeatures, true)) {
			array_push($this->scriptFeatures, $scriptFeature);
		}
		return $this;
	}

	/**
	 * Add a dynamic Feature opening the current map info
	 *
	 * @param string $eventLabel (optional) Event on which the map info will be opened
	 * @return static
	 */
	public function addMapInfoFeature($eventLabel = ScriptLabel::MOUSECLICK) {
		$mapInfo = new MapInfo($this, $eventLabel);
		$this->addScriptFeature($mapInfo);
		return $this;
	}

	/**
	 * Add a dynamic Feature to open a specific player profile
	 *
	 * @param string $login      Login of the player
	 * @param string $eventLabel (optional) Event on which the player profile will be opened
	 * @return static
	 */
	public function addPlayerProfileFeature($login, $eventLabel = ScriptLabel::MOUSECLICK) {
		$playerProfile = new PlayerProfile($login, $this, $eventLabel);
		$this->addScriptFeature($playerProfile);
		return $this;
	}

	/**
	 * Add a dynamic Feature playing a UISound
	 *
	 * @param string $soundName  UISound name
	 * @param int    $variant    (optional) Sound variant
	 * @param string $eventLabel (optional) Event on which the sound will be played
	 * @return static
	 */
	public function addUISoundFeature($soundName, $variant = 0, $eventLabel = ScriptLabel::MOUSECLICK) {
		$uiSound = new UISound($soundName, $this, $variant, $eventLabel);
		$this->addScriptFeature($uiSound);
		return $this;
	}

	/**
	 * Add a dynamic Feature toggling another Control
	 *
	 * @param Control $toggledControl Toggled Control
	 * @param string  $labelName      (optional) Script label name
	 * @param bool    $onlyShow       (optional) Whether it should only show the Control but not toggle
	 * @param bool    $onlyHide       (optional) Whether it should only hide the Control but not toggle
	 * @return static
	 */
	public function addToggleFeature(Control $toggledControl, $labelName = Scriptlabel::MOUSECLICK, $onlyShow = false, $onlyHide = false) {
		$toggle = new Toggle($this, $toggledControl, $labelName, $onlyShow, $onlyHide);
		$this->addScriptFeature($toggle);
		return $this;
	}

	/**
	 * Add a dynamic Feature showing a Tooltip on hovering
	 *
	 * @param Control $tooltipControl Tooltip Control
	 * @param bool    $stayOnClick    (optional) Whether the Tooltip should stay on click
	 * @param bool    $invert         (optional) Whether the visibility toggling should be inverted
	 * @return static
	 */
	public function addTooltipFeature(Control $tooltipControl, $stayOnClick = false, $invert = false) {
		$tooltip = new Tooltip($this, $tooltipControl, $stayOnClick, $invert);
		$this->addScriptFeature($tooltip);
		return $this;
	}

	/**
	 * Add a dynamic Feature showing a Tooltip on hovering
	 *
	 * @param Label  $tooltipControl Tooltip Control
	 * @param string $text           Text to display on the Tooltip Label
	 * @param bool   $stayOnClick    (optional) Whether the Tooltip should stay on click
	 * @param bool   $invert         (optional) Whether the visibility toggling should be inverted
	 * @return static
	 */
	public function addTooltipLabelFeature(Label $tooltipControl, $text, $stayOnClick = false, $invert = false) {
		$tooltip = new Tooltip($this, $tooltipControl, $stayOnClick, $invert, $text);
		$this->addScriptFeature($tooltip);
		return $this;
	}

	/**
	 * Add a custom Control Script text part
	 *
	 * @param string $scriptText Script text
	 * @param string $label      (optional) Script label name
	 * @return static
	 */
	public function addScriptText($scriptText, $label = ScriptLabel::MOUSECLICK) {
		$customText = new ControlScript($this, $scriptText, $label);
		$this->addScriptFeature($customText);
		return $this;
	}

	/**
	 * Remove all Script Features
	 *
	 * @return static
	 */
	public function removeScriptFeatures() {
		$this->scriptFeatures = array();
		return $this;
	}

	/**
	 * @see \FML\Types\ScriptFeatureable::getScriptFeatures()
	 */
	public function getScriptFeatures() {
		return $this->scriptFeatures;
	}

	/**
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->controlId) {
			$xmlElement->setAttribute('id', $this->controlId);
		}
		if ($this->posX || $this->posY || $this->posZ) {
			$xmlElement->setAttribute('posn', "{$this->posX} {$this->posY} {$this->posZ}");
		}
		if ($this->width >= 0. || $this->height >= 0.) {
			$xmlElement->setAttribute('sizen', "{$this->width} {$this->height}");
		}
		if ($this->hAlign !== self::LEFT) {
			$xmlElement->setAttribute('halign', $this->hAlign);
		}
		if ($this->vAlign !== self::TOP) {
			$xmlElement->setAttribute('valign', $this->vAlign);
		}
		if ($this->scale != 1.) {
			$xmlElement->setAttribute('scale', $this->scale);
		}
		if ($this->hidden) {
			$xmlElement->setAttribute('hidden', $this->hidden);
		}
		if ($this->rotation) {
			$xmlElement->setAttribute('rot', $this->rotation);
		}
		if (!empty($this->classes)) {
			$classes = implode(' ', $this->classes);
			$xmlElement->setAttribute('class', $classes);
		}
		return $xmlElement;
	}

	/**
	 * Get the ManiaScript class of the Control
	 *
	 * @return string
	 */
	public abstract function getManiaScriptClass();
}
