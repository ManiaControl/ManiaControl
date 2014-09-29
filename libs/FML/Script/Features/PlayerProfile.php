<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for opening a player profile
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerProfile extends ScriptFeature {
	/*
	 * Protected properties
	 */
	protected $login = null;
	/** @var Control $control */
	protected $control = null;
	protected $labelName = null;

	/**
	 * Construct a new Player Profile Feature
	 *
	 * @param string  $login     (optional) Player login
	 * @param Control $control   (optional) Action Control
	 * @param string  $labelName (optional) Script Label name
	 */
	public function __construct($login = null, Control $control = null, $labelName = ScriptLabel::MOUSECLICK) {
		$this->setLogin($login);
		if ($control !== null) {
			$this->setControl($control);
		}
		$this->setLabelName($labelName);
	}

	/**
	 * Set the login of the opened player
	 *
	 * @param string $login Player login
	 * @return static
	 */
	public function setLogin($login) {
		$this->login = $login;
		return $this;
	}

	/**
	 * Set the Control
	 *
	 * @param Control $control Profile Control
	 * @return static
	 */
	public function setControl(Control $control) {
		$control->checkId();
		if ($control instanceof Scriptable) {
			$control->setScriptEvents(true);
		}
		$this->control = $control;
		return $this;
	}

	/**
	 * Set the label name
	 *
	 * @param string $labelName Script Label name
	 * @return static
	 */
	public function setLabelName($labelName) {
		$this->labelName = (string)$labelName;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->appendGenericScriptLabel($this->labelName, $this->getScriptText());
		return $this;
	}

	/**
	 * Get the script text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$login = Builder::escapeText($this->login, true);
		if ($this->control) {
			// Control event
			$controlId  = Builder::escapeText($this->control->getId(), true);
			$scriptText = "
if (Event.Control.ControlId == {$controlId}) {
	ShowProfile({$login});
}";
		} else {
			// Other
			$scriptText = "
ShowProfile({$login});";
		}
		return $scriptText;
	}
}
