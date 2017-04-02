<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for a Control related script
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ControlScript extends ScriptFeature
{

    /**
     * @var Control $control Control
     */
    protected $control = null;

    /**
     * @var string $labelName Script Label name
     */
    protected $labelName = null;

    /**
     * @var string $scriptText Script text
     */
    protected $scriptText = null;

    /**
     * Construct a new Control Script
     *
     * @api
     * @param Control $control    (optional) Control
     * @param string  $scriptText (optional) Script text
     * @param string  $labelName  (optional) Script Label name
     */
    public function __construct(Control $control = null, $scriptText = null, $labelName = ScriptLabel::MOUSECLICK)
    {
        if ($control) {
            $this->setControl($control);
        }
        if ($scriptText) {
            $this->setScriptText($scriptText);
        }
        if ($labelName) {
            $this->setLabelName($labelName);
        }
    }

    /**
     * Get the Control
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the Control
     *
     * @api
     * @param Control $control Control
     * @return static
     */
    public function setControl(Control $control)
    {
        $control->checkId();
        $control->addScriptFeature($this);
        $this->control = $control;
        $this->updateScriptEvents();
        return $this;
    }

    /**
     * Get the script text
     *
     * @api
     * @return string
     */
    public function getScriptText()
    {
        return $this->scriptText;
    }

    /**
     * Set the script text
     *
     * @api
     * @param string $scriptText Script text
     * @return static
     */
    public function setScriptText($scriptText)
    {
        $this->scriptText = (string)$scriptText;
        return $this;
    }

    /**
     * Set the script text
     *
     * @api
     * @param string $text Text
     * @return static
     * @deprecated Use setScriptText()
     * @see        ControlScript::setScriptText()
     */
    public function setText($text)
    {
        return $this->setScriptText($text);
    }

    /**
     * Get the Script Label name
     *
     * @api
     * @return string
     */
    public function getLabelName()
    {
        return $this->labelName;
    }

    /**
     * Set the Script Label name
     *
     * @api
     * @param string $labelName Script Label name
     * @return static
     */
    public function setLabelName($labelName)
    {
        $this->labelName = (string)$labelName;
        $this->updateScriptEvents();
        return $this;
    }

    /**
     * Enable Script Events on the Control if needed
     *
     * @return static
     */
    protected function updateScriptEvents()
    {
        if (!$this->control || !ScriptLabel::isEventLabel($this->labelName)) {
            return $this;
        }
        if ($this->control instanceof Scriptable) {
            $this->control->setScriptEvents(true);
        }
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $isolated = !ScriptLabel::isEventLabel($this->labelName);
        $script->appendGenericScriptLabel($this->labelName, $this->buildScriptText(), $isolated);
        return $this;
    }

    /**
     * Build the script text for the Control
     *
     * @return string
     */
    protected function buildScriptText()
    {
        $controlId  = Builder::escapeText($this->control->getId());
        $scriptText = '';
        $closeBlock = false;
        if (ScriptLabel::isEventLabel($this->labelName)) {
            $scriptText .= "
if (Event.ControlId == {$controlId}) {
declare Control <=> Event.Control;";
            $closeBlock = true;
        } else {
            $scriptText .= "
declare Control <=> Page.GetFirstChild({$controlId});";
        }
        $class      = $this->control->getManiaScriptClass();
        $name       = preg_replace('/^CMl/', '', $class, 1);
        $scriptText .= "
declare {$name} <=> (Control as {$class});
";
        $scriptText .= $this->scriptText . "
";
        if ($closeBlock) {
            $scriptText .= "}";
        }
        return $scriptText;
    }

}
