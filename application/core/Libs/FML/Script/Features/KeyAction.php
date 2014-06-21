<?php

namespace FML\Script\Features;

use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;

/**
 * Script Feature for triggering a manialink page action on key press
 *
 * @author    steeffeen
 * @link      http://destroflyer.mania-community.de/maniascript/keycharid_table.php
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class KeyAction extends ScriptFeature {
	/*
	 * Protected properties
	 */
	protected $actionName = null;
	protected $keyName = null;
	protected $keyCode = null;
	protected $charPressed = null;

	/**
	 * Construct a new Key Action Feature
	 *
	 * @param string $actionName (optional) Triggered action
	 * @param string $keyName    (optional) Key name
	 */
	public function __construct($actionName = null, $keyName = null) {
		if (!is_null($actionName)) {
			$this->setActionName($actionName);
		}
		if (!is_null($keyName)) {
			$this->setKeyName($keyName);
		}
	}

	/**
	 * Set the action to trigger
	 *
	 * @param string $actionName Triggered action
	 * @return \FML\Script\Features\KeyAction|static
	 */
	public function setActionName($actionName) {
		$this->actionName = (string)$actionName;
		return $this;
	}

	/**
	 * Set the key name for triggering the action
	 *
	 * @param string $keyName Key Name
	 * @return \FML\Script\Features\KeyAction|static
	 */
	public function setKeyName($keyName) {
		$this->keyName     = (string)$keyName;
		$this->keyCode     = null;
		$this->charPressed = null;
		return $this;
	}

	/**
	 * Set the key code for triggering the action
	 *
	 * @param int $keyCode Key Code
	 * @return \FML\Script\Features\KeyAction|static
	 */
	public function setKeyCode($keyCode) {
		$this->keyCode     = (int)$keyCode;
		$this->keyName     = null;
		$this->charPressed = null;
		return $this;
	}

	/**
	 * Set the char to press for triggering the action
	 *
	 * @param string $charPressed Pressed char
	 * @return \FML\Script\Features\KeyAction|static
	 */
	public function setCharPressed($charPressed) {
		$this->charPressed = (string)$charPressed;
		$this->keyName     = null;
		$this->keyCode     = null;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->appendGenericScriptLabel(ScriptLabel::KEYPRESS, $this->getScriptText());
		return $this;
	}

	/**
	 * Get the script text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$actionName = Builder::escapeText($this->actionName, true);
		$key        = null;
		$value      = null;
		if (!is_null($this->keyName)) {
			$key   = 'KeyName';
			$value = $this->keyName;
		} else if (!is_null($this->keyCode)) {
			$key   = 'KeyCode';
			$value = $this->keyCode;
		} else if (!is_null($this->charPressed)) {
			$key   = 'CharPressed';
			$value = $this->charPressed;
		}
		$value = Builder::escapeText($value, true);
		return "
if (Event.{$key} == {$value}) {
	TriggerPageAction({$actionName});
}";
	}
}
