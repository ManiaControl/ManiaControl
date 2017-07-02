<?php

namespace FML\Script\Features;

use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;

/**
 * Script Feature for toggling the complete ManiaLink via Key Press
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ToggleInterface extends ScriptFeature
{

    /*
     * Constants
     */
    const VAR_STATE = "FML_ToggleInterface_State";

    /**
     * @var string $keyName Key name
     */
    protected $keyName = null;

    /**
     * @var int $keyCode Key code
     */
    protected $keyCode = null;

    /**
     * @var bool $rememberState Remember the current state
     */
    protected $rememberState = true;

    /**
     * Construct a new ToggleInterface
     *
     * @api
     * @param string|int $keyNameOrCode (optional) Key name or code
     * @param bool       $rememberState (optional) Remember the current state
     */
    public function __construct($keyNameOrCode = null, $rememberState = true)
    {
        if (is_string($keyNameOrCode)) {
            $this->setKeyName($keyNameOrCode);
        } else if (is_int($keyNameOrCode)) {
            $this->setKeyCode($keyNameOrCode);
        }
        $this->setRememberState($rememberState);
    }

    /**
     * Get the key name
     *
     * @api
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     * Set the key name
     *
     * @api
     * @param string $keyName Key name
     * @return static
     */
    public function setKeyName($keyName)
    {
        $this->keyName = (string)$keyName;
        $this->keyCode = null;
        return $this;
    }

    /**
     * Get the key code
     *
     * @api
     * @return int
     */
    public function getKeyCode()
    {
        return $this->keyCode;
    }

    /**
     * Set the key code
     *
     * @api
     * @param int $keyCode Key code
     * @return static
     */
    public function setKeyCode($keyCode)
    {
        $this->keyCode = (int)$keyCode;
        $this->keyName = null;
        return $this;
    }

    /**
     * Get if the state should get remembered
     *
     * @api
     * @return bool
     */
    public function getRememberState()
    {
        return $this->rememberState;
    }

    /**
     * Set if the state should get remembered
     *
     * @api
     * @param bool $rememberState Remember the current state
     * @return static
     */
    public function setRememberState($rememberState)
    {
        $this->rememberState = (bool)$rememberState;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->appendGenericScriptLabel(ScriptLabel::KEYPRESS, $this->getKeyPressScriptText());
        if ($this->rememberState) {
            $script->appendGenericScriptLabel(ScriptLabel::ONINIT, $this->getOnInitScriptText());
        }
        return $this;
    }

    /**
     * Get the on init script text
     *
     * @return string
     */
    protected function getOnInitScriptText()
    {
        $stateVariableName = $this::VAR_STATE;
        return "
declare persistent {$stateVariableName} as CurrentState for LocalUser = True;
Page.MainFrame.Visible = CurrentState;
";
    }

    /**
     * Get the key press script text
     *
     * @return string
     */
    protected function getKeyPressScriptText()
    {
        $keyProperty = null;
        $keyValue    = null;
        if ($this->keyName) {
            $keyProperty = "KeyName";
            $keyValue    = Builder::getText($this->keyName);
        } else if ($this->keyCode) {
            $keyProperty = "KeyCode";
            $keyValue    = Builder::getInteger($this->keyCode);
        }
        $scriptText = "
if (Event.{$keyProperty} == {$keyValue}) {
    Page.MainFrame.Visible = !Page.MainFrame.Visible;
";
        if ($this->rememberState) {
            $stateVariableName = $this::VAR_STATE;
            $scriptText        .= "
    declare persistent {$stateVariableName} as CurrentState for LocalUser = True;
    CurrentState = Page.MainFrame.Visible;
";
        }
        return $scriptText . "
}";
    }

}
