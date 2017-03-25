<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for opening the map info
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapInfo extends ScriptFeature
{

    /**
     * @var Control $control Map Info Control
     */
    protected $control = null;

    /**
     * @var string $labelName Script Label name
     */
    protected $labelName = null;

    /**
     * Construct a new Map Info
     *
     * @api
     * @param Control $control   (optional) Map Info Control
     * @param string  $labelName (optional) Script Label name
     */
    public function __construct(Control $control = null, $labelName = ScriptLabel::MOUSECLICK)
    {
        if ($control) {
            $this->setControl($control);
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
     * @param Control $control Map Info Control
     * @return static
     */
    public function setControl(Control $control)
    {
        $control->checkId();
        if ($control instanceof Scriptable) {
            $control->setScriptEvents(true);
        }
        $this->control = $control;
        return $this;
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
        if ($this->control) {
            // Control event
            $controlId = Builder::escapeText($this->control->getId());
            return "
if (Event.Control.ControlId == {$controlId}) {
	ShowCurChallengeCard();
}";
        }

        // Other events
        return "
ShowCurChallengeCard();";
    }

}
