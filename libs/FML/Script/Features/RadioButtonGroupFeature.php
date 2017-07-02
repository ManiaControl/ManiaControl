<?php

namespace FML\Script\Features;

use FML\Components\CheckBox;
use FML\Controls\Entry;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;

/**
 * Script Feature for creating a RadioButtonGroup behavior
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RadioButtonGroupFeature extends ScriptFeature
{

    /*
     * Constants
     */
    const FUNCTION_ON_RADIO_BUTTON_CLICK   = "FML_OnRadioButtonClick";
    const CONSTANT_RADIO_BUTTON_IDS_PREFIX = "FML_RadioButtonGroup_";

    /**
     * @var Entry $entry Hidden Entry for submitting the value
     */
    protected $entry = null;

    /**
     * @var CheckBox[] $radioButtons RadioButtons
     */
    protected $radioButtons = array();

    /**
     * Construct a new RadioButtonGroup Feature
     *
     * @api
     * @param Entry $entry (optional) Hidden Entry
     */
    public function __construct(Entry $entry = null)
    {
        if ($entry) {
            $this->setEntry($entry);
        }
    }

    /**
     * Get the hidden Entry
     *
     * @api
     * @return Entry
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * Set the hidden Entry
     *
     * @api
     * @param Entry $entry Hidden Entry
     * @return static
     */
    public function setEntry(Entry $entry)
    {
        $entry->checkId();
        $this->entry = $entry;
        return $this;
    }

    /**
     * Get RadioButtons
     *
     * @api
     * @return CheckBox[]
     */
    public function getRadioButtons()
    {
        return $this->radioButtons;
    }

    /**
     * Set RadioButtons
     *
     * @api
     * @param CheckBox[] $radioButtons RadioButtons
     * @return static
     */
    public function setRadioButtons(array $radioButtons)
    {
        $this->radioButtons = $radioButtons;
        return $this;
    }

    /**
     * Add a new RadioButton
     *
     * @api
     * @param CheckBox $radioButton RadioButton
     * @return static
     */
    public function addRadioButton(CheckBox $radioButton)
    {
        if (!in_array($radioButton, $this->radioButtons, true)) {
            array_push($this->radioButtons, $radioButton);
        }
        return $this;
    }

    /**
     * Add new RadioButtons
     *
     * @api
     * @param CheckBox[] $radioButtons RadioButtons
     * @return static
     */
    public function addRadioButtons(array $radioButtons)
    {
        foreach ($radioButtons as $radioButton) {
            $this->addRadioButton($radioButton);
        }
        return $this;
    }

    /**
     * Remove all RadioButtons
     *
     * @api
     * @return static
     */
    public function removeAllRadioButtons()
    {
        $this->radioButtons = array();
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        if (!$this->entry || !$this->getRadioButtons()) {
            return $this;
        }
        $this->prepareRadioButtonIdsConstant($script);
        $this->prepareOnRadioButtonClickFunction($script);
        $this->prepareRadioButtonClickScript($script);
        return $this;
    }

    /**
     * Build the name of the Constant contain the RadioButton Ids
     *
     * @return string
     */
    protected function getRadioButtonIdsConstantName()
    {
        return self::CONSTANT_RADIO_BUTTON_IDS_PREFIX . $this->entry->checkId();
    }

    /**
     * Prepare the Constant containing the RadioButton Ids
     *
     * @param Script $script Script
     * @return static
     */
    protected function prepareRadioButtonIdsConstant(Script $script)
    {
        $radioButtonIds = array();
        foreach ($this->radioButtons as $radioButton) {
            $radioButtonIds[$radioButton->getName()] = Builder::getId($radioButton->getQuad());
        }
        $script->addScriptConstant($this->getRadioButtonIdsConstantName(), $radioButtonIds);
        return $this;
    }

    /**
     * Build the RadioButton click handler function
     *
     * @param Script $script Script
     * @return static
     */
    protected function prepareOnRadioButtonClickFunction(Script $script)
    {
        $script->addScriptFunction(self::FUNCTION_ON_RADIO_BUTTON_CLICK, "
Void " . self::FUNCTION_ON_RADIO_BUTTON_CLICK . "(CMlQuad _RadioButtonQuad, CMlEntry _RadioButtonGroupEntry, Text[Text] _RadioButtonIds) {
    // update group entry with name of selected radio button
	declare " . CheckBoxFeature::VAR_CHECKBOX_ENABLED . " as RadioButtonEnabled for _RadioButtonQuad = False;
	if (_RadioButtonGroupEntry != Null) {
	    declare RadioButtonGroupValue = \"\";
	    if (RadioButtonEnabled && _RadioButtonIds.exists(_RadioButtonQuad.ControlId)) {
	        RadioButtonGroupValue = _RadioButtonIds.keyof(_RadioButtonQuad.ControlId);
	    }
        _RadioButtonGroupEntry.Value = RadioButtonGroupValue;
	}
	// disable other radio buttons
	if (RadioButtonEnabled) {
        foreach (OtherRadioButtonId in _RadioButtonIds) {
            declare OtherRadioButtonQuad <=> (Page.GetFirstChild(OtherRadioButtonId) as CMlQuad);
            if (OtherRadioButtonQuad != Null && OtherRadioButtonQuad != _RadioButtonQuad) {
                declare " . CheckBoxFeature::VAR_CHECKBOX_ENABLED . " as OtherRadioButtonEnabled for OtherRadioButtonQuad = False;
                if (OtherRadioButtonEnabled) {
                    " . CheckBoxFeature::FUNCTION_UPDATE_QUAD_DESIGN . "(OtherRadioButtonQuad);
                }
            }
        }
	}
}");
        return $this;
    }

    /**
     * Prepare the script for RadioButton clicks
     *
     * @param Script $script Script
     * @return static
     */
    protected function prepareRadioButtonClickScript(Script $script)
    {
        $script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK2, "
if (" . $this->getRadioButtonIdsConstantName() . ".exists(Event.ControlId)) {
	declare RadioButtonQuad <=> (Event.Control as CMlQuad);
	declare RadioButtonGroupEntry <=> (Page.GetFirstChild(\"" . Builder::getId($this->entry) . "\") as CMlEntry);
	" . self::FUNCTION_ON_RADIO_BUTTON_CLICK . "(RadioButtonQuad, RadioButtonGroupEntry, " . $this->getRadioButtonIdsConstantName() . ");
}");
        return $this;
    }

}
