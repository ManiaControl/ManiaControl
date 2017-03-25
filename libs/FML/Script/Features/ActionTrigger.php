<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for triggering a ManiaLink page action
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ActionTrigger extends ScriptFeature
{

    /**
     * @var string $actionName Triggered action
     */
    protected $actionName = null;

    /**
     * @var Control $control Action Control
     */
    protected $control = null;

    /**
     * @var string $labelName Script label name
     */
    protected $labelName = null;

    /**
     * Construct a new Action Trigger
     *
     * @api
     * @param string  $actionName (optional) Triggered action
     * @param Control $control    (optional) Action Control
     * @param string  $labelName  (optional) Script label name
     */
    public function __construct($actionName = null, Control $control = null, $labelName = ScriptLabel::MOUSECLICK)
    {
        if ($actionName) {
            $this->setActionName($actionName);
        }
        if ($control) {
            $this->setControl($control);
        }
        if ($labelName) {
            $this->setLabelName($labelName);
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
     * @param string $actionName Action name
     * @return static
     */
    public function setActionName($actionName)
    {
        $this->actionName = (string)$actionName;
        return $this;
    }

    /**
     * Get the Control that should trigger the action
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the Control that should trigger the action
     *
     * @api
     * @param Control $control Action Control
     * @return static
     */
    public function setControl(Control $control = null)
    {
        if ($control) {
            $control->checkId();
            if ($control instanceof Scriptable) {
                $control->setScriptEvents(true);
            }
        }
        $this->control = $control;
        return $this;
    }

    /**
     * Get the script label name
     *
     * @api
     * @return string
     */
    public function getLabelName()
    {
        return $this->labelName;
    }

    /**
     * Set the script label name
     *
     * @api
     * @param string $labelName Script Label name
     * @return static
     */
    public function setLabelName($labelName)
    {
        $this->labelName = (string)$labelName;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->appendGenericScriptLabel($this->labelName, $this->getScriptText());
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
        if ($this->control) {
            // Control event
            $controlId  = Builder::escapeText($this->control->getId());
            $scriptText = "
if (Event.Control.ControlId == {$controlId}) {
	TriggerPageAction({$actionName});
}";
        } else {
            // Other
            $scriptText = "
TriggerPageAction({$actionName});";
        }
        return $scriptText;
    }

}
