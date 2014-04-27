<?php

namespace FML\Controls;

use FML\Types\Renderable;
use FML\Script\Features\ActionTrigger;
use FML\Script\ScriptLabel;
use FML\Types\ScriptFeatureable;
use FML\Script\Features\MapInfo;
use FML\Script\Features\PlayerProfile;
use FML\Script\Features\UISound;
use FML\Script\Builder;
use FML\Script\Features\Toggle;
use FML\Script\Features\Tooltip;

/**
 * Base Control
 * (CMlControl)
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Control implements Renderable, ScriptFeatureable {
	/*
	 * Constants
	 */
	const CENTER = 'center';
	const CENTER2 = 'center2';
	const TOP = 'top';
	const RIGHT = 'right';
	const BOTTOM = 'bottom';
	const LEFT = 'left';
	
	/*
	 * Static Properties
	 */
	protected static $currentIndex = 0;
	
	/*
	 * Protected Properties
	 */
	protected $tagName = 'control';
	protected $id = '';
	protected $x = 0.;
	protected $y = 0.;
	protected $z = 0.;
	protected $width = -1.;
	protected $height = -1.;
	protected $hAlign = self::CENTER;
	protected $vAlign = self::CENTER2;
	protected $scale = 1.;
	protected $hidden = 0;
	protected $classes = array();
	protected $scriptFeatures = array();

	/**
	 * Construct a new Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Get Control Id
	 *
	 * @param bool $escaped (optional) Whether the Id should be escaped for ManiaScript
	 * @return string
	 */
	public function getId($escaped = false) {
		if ($escaped) {
			return Builder::escapeText($this->id);
		}
		return $this->id;
	}

	/**
	 * Set Control Id
	 *
	 * @param string $id Control Id
	 * @return \FML\Controls\Control
	 */
	public function setId($id) {
		$this->id = (string) $id;
		return $this;
	}

	/**
	 * Check Id for dangerous Characters and assign a unique Id if necessary
	 *
	 * @return \FML\Controls\Control
	 */
	public function checkId() {
		if (!$this->getId()) {
			$this->setId('FML_ID_' . self::$currentIndex);
			self::$currentIndex++;
			return $this;
		}
		$dangerousCharacters = array(' ', '	', '.', '|', '-', PHP_EOL);
		$idCharacters = str_split($this->getId());
		$danger = false;
		foreach ($idCharacters as $character) {
			if (!in_array($character, $dangerousCharacters)) continue;
			$danger = true;
			break;
		}
		if ($danger) {
			trigger_error("Please don't use special Characters in Ids, they might cause Problems! (I stripped them for You.)");
			$id = str_ireplace($dangerousCharacters, '', $this->getId());
			$this->setId($id);
		}
		return $this;
	}

	/**
	 * Set X Position
	 *
	 * @param float $x Horizontal Position
	 * @return \FML\Controls\Control
	 */
	public function setX($x) {
		$this->x = (float) $x;
		return $this;
	}

	/**
	 * Set Y Position
	 *
	 * @param float $y Vertical Position
	 * @return \FML\Controls\Control
	 */
	public function setY($y) {
		$this->y = (float) $y;
		return $this;
	}

	/**
	 * Set Z Position
	 *
	 * @param float $z Depth
	 * @return \FML\Controls\Control
	 */
	public function setZ($z) {
		$this->z = (float) $z;
		return $this;
	}

	/**
	 * Set Control Position
	 *
	 * @param float $x Horizontal Position
	 * @param float $y Vertical Position
	 * @param float $z (optional) Depth
	 * @return \FML\Controls\Control
	 */
	public function setPosition($x, $y, $z = null) {
		$this->setX($x);
		$this->setY($y);
		if ($z !== null) {
			$this->setZ($z);
		}
		return $this;
	}

	/**
	 * Set Control Width
	 *
	 * @param float $width Control Width
	 * @return \FML\Controls\Control
	 */
	public function setWidth($width) {
		$this->width = (float) $width;
		return $this;
	}

	/**
	 * Set Control Height
	 *
	 * @param float $height Control Height
	 * @return \FML\Controls\Control
	 */
	public function setHeight($height) {
		$this->height = (float) $height;
		return $this;
	}

	/**
	 * Set Control Size
	 *
	 * @param float $width Control Width
	 * @param float $height Control Height
	 * @return \FML\Controls\Control
	 */
	public function setSize($width, $height) {
		$this->setWidth($width);
		$this->setHeight($height);
		return $this;
	}

	/**
	 * Set Horizontal Alignment
	 *
	 * @param string $hAlign Horizontal Alignment
	 * @return \FML\Controls\Control
	 */
	public function setHAlign($hAlign) {
		$this->hAlign = (string) $hAlign;
		return $this;
	}

	/**
	 * Set Vertical Alignment
	 *
	 * @param string $vAlign Vertical Alignment
	 * @return \FML\Controls\Control
	 */
	public function setVAlign($vAlign) {
		$this->vAlign = (string) $vAlign;
		return $this;
	}

	/**
	 * Set Horizontal and Vertical Alignment
	 *
	 * @param string $hAlign Horizontal Alignment
	 * @param string $vAlign Vertical Alignment
	 * @return \FML\Controls\Control
	 */
	public function setAlign($hAlign, $vAlign) {
		$this->setHAlign($hAlign);
		$this->setVAlign($vAlign);
		return $this;
	}

	/**
	 * Set Control Scale
	 *
	 * @param float $scale Control Scale
	 * @return \FML\Controls\Control
	 */
	public function setScale($scale) {
		$this->scale = (float) $scale;
		return $this;
	}

	/**
	 * Set Visibility
	 *
	 * @param bool $visible Whether Control should be visible
	 * @return \FML\Controls\Control
	 */
	public function setVisible($visible) {
		$this->hidden = ($visible ? 0 : 1);
		return $this;
	}

	/**
	 * Add new Class Name
	 *
	 * @param string $class Class Name
	 * @return \FML\Controls\Control
	 */
	public function addClass($class) {
		$class = (string) $class;
		if (!in_array($class, $this->classes)) {
			array_push($this->classes, $class);
		}
		return $this;
	}

	/**
	 * Add a dynamic Action Trigger
	 *
	 * @param string $actionName Action to trigger
	 * @param string $eventLabel (optional) Event on which the Action is triggered
	 * @return \FML\Controls\Control
	 */
	public function addActionTriggerFeature($actionName, $eventLabel = ScriptLabel::MOUSECLICK) {
		$actionTrigger = new ActionTrigger($actionName, $this, $eventLabel);
		array_push($this->scriptFeatures, $actionTrigger);
		return $this;
	}

	/**
	 * Add a dynamic Feature opening the current Map Info
	 *
	 * @param string $eventLabel (optional) Event on which the Map Info will be opened
	 * @return \FML\Controls\Control
	 */
	public function addMapInfoFeature($eventLabel = ScriptLabel::MOUSECLICK) {
		$mapInfo = new MapInfo($this, $eventLabel);
		array_push($this->scriptFeatures, $mapInfo);
		return $this;
	}

	/**
	 * Add a dynamic Feature to open a specific Player Profile
	 *
	 * @param string $login The Login of the Player
	 * @param string $eventLabel (optional) Event on which the Player Profile will be opened
	 * @return \FML\Controls\Control
	 */
	public function addPlayerProfileFeature($login, $eventLabel = ScriptLabel::MOUSECLICK) {
		$playerProfile = new PlayerProfile($login, $this, $eventLabel);
		array_push($this->scriptFeatures, $playerProfile);
		return $this;
	}

	/**
	 * Add a dynamic Feature playing an UISound
	 *
	 * @param string $soundName UISound Name
	 * @param int $variant (optional) Sound Variant
	 * @param string $eventLabel (optional) Event on which the Sound will be played
	 * @return \FML\Controls\Control
	 */
	public function addUISoundFeature($soundName, $variant = 0, $eventLabel = ScriptLabel::MOUSECLICK) {
		$uiSound = new UISound($soundName, $this, $variant, $eventLabel);
		array_push($this->scriptFeatures, $uiSound);
		return $this;
	}

	/**
	 * Add a dynamic Feature toggling another Control
	 *
	 * @param Control $toggledControl Toggled Control
	 * @param string $labelName (optional) Script Label Name
	 * @param bool $onlyShow (optional) Whether it should only Show the Control but not toggle
	 * @param bool $onlyHide (optional) Whether it should only Hide the Control but not toggle
	 * @return \FML\Controls\Control
	 */
	public function addToggleFeature(Control $toggledControl, $labelName = Scriptlabel::MOUSECLICK, $onlyShow = false, $onlyHide = false) {
		$toggle = new Toggle($this, $toggledControl, $labelName, $onlyShow, $onlyHide);
		array_push($this->scriptFeatures, $toggle);
		return $this;
	}

	/**
	 * Add a dynamic Feature showing a Tooltip on hovering
	 *
	 * @param Control $tooltipControl Tooltip Control
	 * @param bool $stayOnClick (optional) Whether the Tooltip should stay on Click
	 * @param bool $invert (optional) Whether the Visibility Toggling should be inverted
	 * @return \FML\Controls\Control
	 */
	public function addTooltipFeature(Control $tooltipControl, $stayOnClick = false, $invert = false) {
		$tooltip = new Tooltip($this, $tooltipControl, $stayOnClick, $invert);
		array_push($this->scriptFeatures, $tooltip);
		return $this;
	}

	/**
	 * Add a dynamic Feature showing a Tooltip on hovering
	 *
	 * @param Label $tooltipControl Tooltip Control
	 * @param string $text The Text to display on the Tooltip Label
	 * @param bool $stayOnClick (optional) Whether the Tooltip should stay on Click
	 * @param bool $invert (optional) Whether the Visibility Toggling should be inverted
	 * @return \FML\Controls\Control
	 */
	public function addTooltipLabelFeature(Label $tooltipControl, $text, $stayOnClick = false, $invert = false) {
		$tooltip = new Tooltip($this, $tooltipControl, $stayOnClick, $invert, $text);
		array_push($this->scriptFeatures, $tooltip);
		return $this;
	}

	/**
	 * Remove all Script Features
	 * 
	 * @return \FML\Controls\Control
	 */
	public function removeScriptFeatures() {
		$this->scriptFeatures = array();
		return $this;
	}
	
	/**
	 *
	 * @see \FML\Types\ScriptFeatureable::getScriptFeatures()
	 */
	public function getScriptFeatures() {
		return $this->scriptFeatures;
	}

	/**
	 *
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->id) {
			$xmlElement->setAttribute('id', $this->id);
		}
		if ($this->x != 0. || $this->y != 0. || $this->z != 0.) {
			$xmlElement->setAttribute('posn', "{$this->x} {$this->y} {$this->z}");
		}
		if ($this->width >= 0. || $this->height >= 0.) {
			$xmlElement->setAttribute('sizen', "{$this->width} {$this->height}");
		}
		if ($this->hAlign) {
			$xmlElement->setAttribute('halign', $this->hAlign);
		}
		if ($this->vAlign) {
			$xmlElement->setAttribute('valign', $this->vAlign);
		}
		if ($this->scale != 1.) {
			$xmlElement->setAttribute('scale', $this->scale);
		}
		if ($this->hidden) {
			$xmlElement->setAttribute('hidden', $this->hidden);
		}
		if (!empty($this->classes)) {
			$classes = implode(' ', $this->classes);
			$xmlElement->setAttribute('class', $classes);
		}
		return $xmlElement;
	}
}
