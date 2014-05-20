<?php

namespace FML\Script\Features;

use FML\Controls\Entry;
use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature for creating a ValuePicker Behavior
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ValuePickerFeature extends ScriptFeature {
	/*
	 * Constants
	 */
	const FUNCTION_UPDATE_PICKER_VALUE = 'FML_UpdatePickerValue';
	const VAR_PICKER_VALUES            = 'FML_Picker_Values';
	const VAR_PICKER_DEFAULT_VALUE     = 'FML_Picker_Default_Value';
	const VAR_PICKER_ENTRY_ID          = 'FML_Picker_EntryId';

	/*
	 * Protected Properties
	 */
	/** @var Label $label */
	protected $label = null;
	/** @var Entry $entry */
	protected $entry = null;
	protected $values = null;
	protected $default = null;

	/**
	 * Construct a new ValuePicker Feature
	 *
	 * @param Label  $label   (optional) ValuePicker Label
	 * @param Entry  $entry   (optional) Hidden Entry
	 * @param array  $values  (optional) Possible Values
	 * @param string $default (optional) Default Value
	 */
	public function __construct(Label $label = null, Entry $entry = null, array $values = array(), $default = null) {
		$this->setLabel($label);
		$this->setEntry($entry);
		$this->setValues($values);
		$this->setDefault($default);
	}

	/**
	 * Set the ValuePicker Label
	 *
	 * @param Label $label ValuePicker Label
	 * @return \FML\Script\Features\ValuePickerFeature
	 */
	public function setLabel(Label $label = null) {
		if ($label) {
			$label->checkId();
			$label->setScriptEvents(true);
		}
		$this->label = $label;
		return $this;
	}

	/**
	 * Get the ValuePicker Label
	 *
	 * @return \FML\Controls\Label
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Set the hidden Entry
	 *
	 * @param Entry $entry Hidden Entry
	 * @return \FML\Script\Features\ValuePickerFeature
	 */
	public function setEntry(Entry $entry = null) {
		if ($entry) {
			$entry->checkId();
		}
		$this->entry = $entry;
		return $this;
	}

	/**
	 * Get the hidden Entry
	 *
	 * @return \FML\Controls\Entry
	 */
	public function getEntry() {
		return $this->entry;
	}

	/**
	 * Set the possible Values
	 *
	 * @param array $values Possible Values
	 * @return \FML\Script\Features\ValuePickerFeature
	 */
	public function setValues(array $values) {
		$this->values = array();
		foreach ($values as $value) {
			array_push($this->values, (string)$value);
		}
		return $this;
	}

	/**
	 * Set the default Value
	 *
	 * @param string $default Default Value
	 * @return \FML\Script\Features\ValuePickerFeature
	 */
	public function setDefault($default) {
		$this->default = (string)$default;
	}

	/**
	 * Get the default Value
	 *
	 * @return string
	 */
	public function getDefault() {
		if ($this->default) {
			return $this->default;
		}
		if ($this->values) {
			return reset($this->values);
		}
		return null;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		if ($this->label) {
			$script->setScriptInclude(ScriptInclude::TEXTLIB);
			$script->addScriptFunction(self::FUNCTION_UPDATE_PICKER_VALUE, $this->buildUpdatePickerValueFunction());
			$script->appendGenericScriptLabel(ScriptLabel::ONINIT, $this->buildInitScriptText(), true);
			$script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK, $this->buildClickScriptText());
		}
		return $this;
	}

	/**
	 * Build the Function Text
	 *
	 * @return string
	 */
	protected function buildUpdatePickerValueFunction() {
		$functionText = "
Void " . self::FUNCTION_UPDATE_PICKER_VALUE . "(CMlLabel _Label) {
	declare " . self::VAR_PICKER_VALUES . " as Values for _Label = Text[];
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
		return $functionText;
	}

	/**
	 * Build the Init Script Text
	 *
	 * @return string
	 */
	protected function buildInitScriptText() {
		$labelId = $this->label->getId(true);
		$entryId = '';
		if ($this->entry) {
			$entryId = $this->entry->getId(true);
		}
		$values     = Builder::getArray($this->values);
		$default    = $this->getDefault();
		$scriptText = "
declare Label_Picker <=> (Page.GetFirstChild(\"{$labelId}\") as CMlLabel);
declare Text[] " . self::VAR_PICKER_VALUES . " as Values for Label_Picker;
Values = {$values};
declare Text " . self::VAR_PICKER_DEFAULT_VALUE . " as Default for Label_Picker;
Default = \"{$default}\";
declare Text " . self::VAR_PICKER_ENTRY_ID . " as EntryId for Label_Picker;
EntryId = \"{$entryId}\";
" . self::FUNCTION_UPDATE_PICKER_VALUE . "(Label_Picker);
";
		return $scriptText;
	}

	/**
	 * Build the Script Text for Label Clicks
	 *
	 * @return string
	 */
	protected function buildClickScriptText() {
		$labelId    = $this->label->getId(true);
		$scriptText = "
if (Event.ControlId == \"{$labelId}\") {
	declare Label_Picker <=> (Event.Control as CMlLabel);
	" . self::FUNCTION_UPDATE_PICKER_VALUE . "(Label_Picker);
}";
		return $scriptText;
	}
}
