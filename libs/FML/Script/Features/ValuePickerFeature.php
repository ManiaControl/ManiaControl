<?php

namespace FML\Script\Features;

use FML\Controls\Entry;
use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature for creating a ValuePicker behavior
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ValuePickerFeature extends ScriptFeature
{

    /*
     * Constants
     */
    const FUNCTION_UPDATE_PICKER_VALUE = "FML_UpdatePickerValue";
    const VAR_PICKER_VALUES            = "FML_Picker_Values";
    const VAR_PICKER_DEFAULT_VALUE     = "FML_Picker_Default_Value";
    const VAR_PICKER_ENTRY_ID          = "FML_Picker_EntryId";

    /**
     * @var Label $label Label
     */
    protected $label = null;

    /**
     * @var Entry $entry Hidden Entry
     */
    protected $entry = null;

    /**
     * @var string[] $values Possible values
     */
    protected $values = array();

    /**
     * @var string $default Default value
     */
    protected $default = null;

    /**
     * Construct a new ValuePicker Feature
     *
     * @api
     * @param Label    $label   (optional) Label
     * @param Entry    $entry   (optional) Hidden Entry
     * @param string[] $values  (optional) Possible values
     * @param string   $default (optional) Default value
     */
    public function __construct(Label $label = null, Entry $entry = null, array $values = null, $default = null)
    {
        if ($label) {
            $this->setLabel($label);
        }
        if ($entry) {
            $this->setEntry($entry);
        }
        if ($values && !empty($values)) {
            $this->setValues($values);
        }
        if ($default !== null) {
            $this->setDefault($default);
        }
    }

    /**
     * Get the Label
     *
     * @api
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the Label
     *
     * @api
     * @param Label $label Label
     * @return static
     */
    public function setLabel(Label $label)
    {
        $label->checkId();
        $label->setScriptEvents(true);
        $this->label = $label;
        return $this;
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
     * Get the possible values
     *
     * @api
     * @return string[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Add a possible value
     *
     * @api
     * @param string $value Possible value
     * @return static
     */
    public function addValue($value)
    {
        array_push($this->values, (string)$value);
        return $this;
    }

    /**
     * Set the possible values
     *
     * @api
     * @param string[] $values Possible values
     * @return static
     */
    public function setValues(array $values)
    {
        $this->values = array();
        foreach ($values as $value) {
            $this->addValue($value);
        }
        return $this;
    }

    /**
     * Get the default value
     *
     * @api
     * @return string
     */
    public function getDefault()
    {
        if ($this->default) {
            return $this->default;
        }
        if (!empty($this->values)) {
            return reset($this->values);
        }
        return null;
    }

    /**
     * Set the default value
     *
     * @api
     * @param string $default Default value
     * @return static
     */
    public function setDefault($default)
    {
        $this->default = (string)$default;
        if ($this->default && !in_array($this->default, $this->values, true)) {
            $this->addValue($this->default);
        }
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        if ($this->label) {
            $script->setScriptInclude(ScriptInclude::TEXTLIB);
            $script->addScriptFunction(self::FUNCTION_UPDATE_PICKER_VALUE, $this->buildUpdatePickerValueFunction());
            $script->appendGenericScriptLabel(ScriptLabel::ONINIT, $this->buildInitScriptText(), true);
            $script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK, $this->buildClickScriptText());
        }
        return $this;
    }

    /**
     * Build the function text
     *
     * @return string
     */
    protected function buildUpdatePickerValueFunction()
    {
        return "
Void " . self::FUNCTION_UPDATE_PICKER_VALUE . "(CMlLabel _Label) {
	declare " . self::VAR_PICKER_VALUES . " as Values for _Label = Text[Integer];
	declare NewValueIndex = -1;
	if (Values.exists(_Label.Value)) {
		declare ValueIndex = Values.keyof(_Label.Value);
		ValueIndex += 1;
		if (Values.existskey(ValueIndex)) {
			NewValueIndex = ValueIndex;
		} else {
			NewValueIndex = 0;
		}
	}
	declare NewValue = \"\";
	if (Values.existskey(NewValueIndex)) {
		NewValue = Values[NewValueIndex];
	} else {
		declare " . self::VAR_PICKER_DEFAULT_VALUE . " as Default for _Label = \"\";
		NewValue = Default;
	}
	_Label.Value = NewValue;
	declare " . self::VAR_PICKER_ENTRY_ID . " as EntryId for _Label = \"\";
	if (EntryId != \"\") {
		declare Entry <=> (Page.GetFirstChild(EntryId) as CMlEntry);
		Entry.Value = NewValue;
	}
}";
    }

    /**
     * Build the init script text
     *
     * @return string
     */
    protected function buildInitScriptText()
    {
        $labelId = Builder::getId($this->label);
        $entryId = Builder::EMPTY_STRING;
        if ($this->entry) {
            $entryId = Builder::getId($this->entry);
        }

        $values  = Builder::getArray($this->values);
        $default = Builder::escapeText($this->getDefault());

        return "
declare Label_Picker <=> (Page.GetFirstChild(\"{$labelId}\") as CMlLabel);
declare " . self::VAR_PICKER_VALUES . " as Values for Label_Picker = Text[Integer];
Values = {$values};
declare " . self::VAR_PICKER_DEFAULT_VALUE . " as Default for Label_Picker = \"\";
Default = {$default};
declare " . self::VAR_PICKER_ENTRY_ID . " as EntryId for Label_Picker = \"\";
EntryId = \"{$entryId}\";
" . self::FUNCTION_UPDATE_PICKER_VALUE . "(Label_Picker);
";
    }

    /**
     * Build the script text for Label clicks
     *
     * @return string
     */
    protected function buildClickScriptText()
    {
        $labelId = Builder::getId($this->label);
        return "
if (Event.ControlId == \"{$labelId}\") {
	declare Label_Picker <=> (Event.Control as CMlLabel);
	" . self::FUNCTION_UPDATE_PICKER_VALUE . "(Label_Picker);
}";
    }

}
