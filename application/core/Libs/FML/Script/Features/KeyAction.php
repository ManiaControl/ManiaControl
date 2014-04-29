<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Script\Builder;

/**
 * Script Feature for triggering a Page Action on Key Press
 * 
 * @author steeffeen
 * @link http://destroflyer.mania-community.de/maniascript/keycharid_table.php
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class KeyAction extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	protected $actionName = null;
	protected $keyName = null;
	protected $keyCode = null;
	protected $charPressed = null;

	/**
	 * Construct a new Key Action Feature
	 * 
	 * @param string $actionName (optional) Triggered Action
	 * @param string $keyName (optional) Key Name
	 * @param int $keyCode (optional) Key Code
	 * @param string $charPressed (optional) Pressed Char
	 */
	public function __construct($actionName = null, $keyName = null, $keyCode = null, $charPressed = null) {
		$this->setActionName($actionName);
		$this->setKeyName($keyName);
		$this->setKeyCode($keyCode);
		$this->setCharPressed($charPressed);
	}

	/**
	 * Set the Action to trigger
	 * 
	 * @param string $actionName Triggered Action
	 * @return \FML\Script\Features\KeyAction
	 */
	public function setActionName($actionName) {
		$this->actionName = (string) $actionName;
		return $this;
	}

	/**
	 * Set the Key Name for triggering the Action
	 * 
	 * @param string $keyName Key Name
	 * @return \FML\Script\Features\KeyAction
	 */
	public function setKeyName($keyName) {
		$this->keyName = (string) $keyName;
		return $this;
	}

	/**
	 * Set the Key Code for triggering the Action
	 * 
	 * @param int $keyCode Key Code
	 * @return \FML\Script\Features\KeyAction
	 */
	public function setKeyCode($keyCode) {
		$this->keyCode = $keyCode;
		return $this;
	}

	/**
	 * Set the Char to press for triggering the Action
	 * 
	 * @param string $charPressed Pressed Char
	 * @return \FML\Script\Features\KeyAction
	 */
	public function setCharPressed($charPressed) {
		$this->charPressed = $charPressed;
		return $this;
	}

	/**
	 *
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->appendGenericScriptLabel(ScriptLabel::KEYPRESS, $this->getScriptText());
		return $this;
	}

	/**
	 * Get the Script Text
	 * 
	 * @return string
	 */
	protected function getScriptText() {
		$actionName = Builder::escapeText($this->actionName);
		$key = 'KeyName';
		$value = $this->keyName;
		if ($this->keyCode !== null) {
			$key = 'KeyCode';
			$value = (int) $this->keyCode;
		}
		else if ($this->charPressed !== null) {
			$key = 'CharPressed';
			$value = (string) $this->charPressed;
		}
		$scriptText = "
if (Event.{$key} == \"{$value}\") {
	TriggerPageAction(\"{$actionName}\");
}";
		return $scriptText;
	}
}
