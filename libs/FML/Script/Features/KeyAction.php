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
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class KeyAction extends ScriptFeature
{

    /**
     * @var string $actionName Action name
     */
    protected $actionName = null;

    /**
     * @var string $keyName Key name
     */
    protected $keyName = null;

    /**
     * @var int $keyCode Key code
     */
    protected $keyCode = null;

    /**
     * @var string $charPressed Pressed character
     */
    protected $charPressed = null;

    /**
     * Construct a new Key Action
     *
     * @api
     * @param string $actionName (optional) Triggered action
     * @param string $keyName    (optional) Key name
     */
    public function __construct($actionName = null, $keyName = null)
    {
        if ($actionName) {
            $this->setActionName($actionName);
        }
        if ($keyName) {
            $this->setKeyName($keyName);
        }
    }

    /**
     * Get the action to trigger
     *
     * @api
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Set the action to trigger
     *
     * @api
     * @param string $actionName Triggered action
     * @return static
     */
    public function setActionName($actionName)
    {
        $this->actionName = (string)$actionName;
        return $this;
    }

    /**
     * Get the key name for triggering the action
     *
     * @api
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     * Set the key name for triggering the action
     *
     * @api
     * @param string $keyName Key Name
     * @return static
     */
    public function setKeyName($keyName)
    {
        $this->keyName     = (string)$keyName;
        $this->keyCode     = null;
        $this->charPressed = null;
        return $this;
    }

    /**
     * Get the key code for triggering the action
     *
     * @api
     * @return int
     */
    public function getKeyCode()
    {
        return $this->keyCode;
    }

    /**
     * Set the key code for triggering the action
     *
     * @api
     * @param int $keyCode Key Code
     * @return static
     */
    public function setKeyCode($keyCode)
    {
        $this->keyName     = null;
        $this->keyCode     = (int)$keyCode;
        $this->charPressed = null;
        return $this;
    }

    /**
     * Get the character to press for triggering the action
     *
     * @api
     * @return string
     */
    public function getCharPressed()
    {
        return $this->charPressed;
    }

    /**
     * Set the character to press for triggering the action
     *
     * @api
     * @param string $charPressed Pressed character
     * @return static
     */
    public function setCharPressed($charPressed)
    {
        $this->keyName     = null;
        $this->keyCode     = null;
        $this->charPressed = (string)$charPressed;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->appendGenericScriptLabel(ScriptLabel::KEYPRESS, $this->getScriptText());
        return $this;
    }

    /**
     * Get the script text
     *
     * @return string
     */
    protected function getScriptText()
    {
        $actionName = Builder::escapeText($this->actionName);
        $key        = null;
        $value      = null;
        if ($this->keyName !== null) {
            $key   = "KeyName";
            $value = $this->keyName;
        } else if ($this->keyCode !== null) {
            $key   = "KeyCode";
            $value = $this->keyCode;
        } else if ($this->charPressed !== null) {
            $key   = "CharPressed";
            $value = $this->charPressed;
        }
        $value = Builder::escapeText($value);
        return "
if (Event.{$key} == {$value}) {
	TriggerPageAction({$actionName});
}";
    }

}
