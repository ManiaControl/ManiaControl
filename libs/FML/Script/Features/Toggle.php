<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for toggling Controls
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Toggle extends ScriptFeature
{

    /**
     * @var Control $togglingControl Toggling Control
     */
    protected $togglingControl = null;

    /**
     * @var Control $toggledControl Toggled Control
     */
    protected $toggledControl = null;

    /**
     * @var $labelName Script Label name
     */
    protected $labelName = null;

    /**
     * @var bool $onlyShow Show only
     */
    protected $onlyShow = null;

    /**
     * @var bool $onlyHide Hide only
     */
    protected $onlyHide = null;

    /**
     * Construct a new Toggle
     *
     * @api
     * @param Control $togglingControl (optional) Toggling Control
     * @param Control $toggledControl  (optional) Toggled Control
     * @param string  $labelName       (optional) Script Label name
     * @param bool    $onlyShow        (optional) If it should only show the Control but not toggle
     * @param bool    $onlyHide        (optional) If it should only hide the Control but not toggle
     */
    public function __construct(
        Control $togglingControl = null,
        Control $toggledControl = null,
        $labelName = ScriptLabel::MOUSECLICK,
        $onlyShow = false,
        $onlyHide = false
    ) {
        if ($togglingControl) {
            $this->setTogglingControl($togglingControl);
        }
        if ($toggledControl) {
            $this->setToggledControl($toggledControl);
        }
        if ($labelName) {
            $this->setLabelName($labelName);
        }
        if ($onlyShow) {
            $this->setOnlyShow($onlyShow);
        }
        if ($onlyHide) {
            $this->setOnlyHide($onlyHide);
        }
    }

    /**
     * Get the toggling Control
     *
     * @api
     * @return Control
     */
    public function getTogglingControl()
    {
        return $this->togglingControl;
    }

    /**
     * Set the toggling Control
     *
     * @api
     * @param Control $control Toggling Control
     * @return static
     */
    public function setTogglingControl(Control $control)
    {
        $control->checkId();
        if ($control instanceof Scriptable) {
            $control->setScriptEvents(true);
        }
        $this->togglingControl = $control;
        return $this;
    }

    /**
     * Get the toggled Control
     *
     * @api
     * @return Control
     */
    public function getToggledControl()
    {
        return $this->toggledControl;
    }

    /**
     * Set the toggled Control
     *
     * @api
     * @param Control $control Toggled Control
     * @return static
     */
    public function setToggledControl(Control $control)
    {
        $control->checkId();
        $this->toggledControl = $control;
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
     * @param string $labelName Script Label Name
     * @return static
     */
    public function setLabelName($labelName)
    {
        $this->labelName = (string)$labelName;
        return $this;
    }

    /**
     * Get Show Only
     *
     * @api
     * @return bool
     */
    public function getOnlyShow()
    {
        return $this->onlyShow;
    }

    /**
     * Set Show Only
     *
     * @api
     * @param bool $onlyShow If it should only show the Control but not toggle
     * @return static
     */
    public function setOnlyShow($onlyShow)
    {
        $this->onlyShow = (bool)$onlyShow;
        if ($this->onlyShow) {
            $this->onlyHide = null;
        }
        return $this;
    }

    /**
     * Get Hide Only
     *
     * @api
     * @return bool
     */
    public function getOnlyHide()
    {
        return $this->onlyHide;
    }

    /**
     * Set Hide Only
     *
     * @api
     * @param bool $onlyHide If it should only hide the Control but not toggle
     * @return static
     */
    public function setOnlyHide($onlyHide)
    {
        $this->onlyHide = (bool)$onlyHide;
        if ($this->onlyHide) {
            $this->onlyShow = null;
        }
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
        $togglingControlId = Builder::escapeText($this->togglingControl->getId());
        $toggledControlId  = Builder::escapeText($this->toggledControl->getId());
        $visibility        = "!ToggleControl.Visible";
        if ($this->onlyShow) {
            $visibility = "True";
        } else if ($this->onlyHide) {
            $visibility = "False";
        }
        return "
if (Event.Control.ControlId == {$togglingControlId}) {
	declare ToggleControl = Page.GetFirstChild({$toggledControlId});
	ToggleControl.Visible = {$visibility};
}";
    }

}
