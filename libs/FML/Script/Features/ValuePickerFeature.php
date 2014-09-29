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
	 * Protected properties
	 */
	/** @var Label $label */
	protected $label = null;
	/** @var Entry $entry */
	protected $entry = null;
	protected $values = array();
	protected $default = null;

	/**
	 * Construct a new ValuePicker Feature
	 *
	 * @param Label  $label   (optional) ValuePicker Label
	 * @param Entry  $entry   (optional) Hidden Entry
	 * @param array  $values  (optional) Possible values
	 * @param string $default (optional) Default value
	 */
	public function __construct(Label $label = null, Entry $entry = null, array $values = array(), $default = null) {
		if ($label !== null) {
			$this->setLabel($label);
		}
		if ($entry !== null) {
			$this->setEntry($entry);
		}
		if (!empty($values)) {
			$this->setValues($values);
		}
		if ($default !== null) {
			$this->setDefault($default);
		}
	}

	/**
	 * Set the ValuePicker Label
	 *
	 * @param Label $label ValuePicker Label
	 * @return static
	 */
	public function setLabel(Label $label) {
		$this->label = $label->checkId()->setScriptEvents(true);
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
	 * @return static
	 */
	public function setEntry(Entry $entry) {
		$this->entry = $entry->checkId();
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
	 * Set the possible values
	 *
	 * @param array $values Possible values
	 * @return static
	 */
	public function setValues(array $values) {
		$this->values = array();
		foreach ($values as $value) {
			array_push($this->values, (string)$value);
		}
		return $this;
	}

	/**
	 * Set the default value
	 *
	 * @param string $default Default value
	 * @return static
	 */
	public function setDefault($default) {
		$this->default = (string)$default;
	}

	/**
	 * Get the default value
	 *
	 * @return string
	 */
	public function getDefault() {
		if ($this->default) {
			return $this->default;
		}
		if (!empty($this->values)) {
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
	 * Build the function text
	 *
	 * @return string
	 */
	protected function buildUpdatePickerValueFunction() {
		return "
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
	declare NewValue = " . Builder::EMPTY_STRING . ";
	if (Values.existskey(NewValueIndex)) {
		NewValue = Values[NewValueIndex];
	} else {
		declare " . self::VAR_PICKER_DEFAULT_VALUE . " as Default for _Label = " . Builder::EMPTY_STRING . ";
		NewValue = Default;
	}
	_Label.Value = NewValue;
	declare " . self::VAR_PICKER_ENTRY_ID . " as EntryId for _Label = " . Builder::EMPTY_STRING . ";
	if (EntryId != " . Builder::EMPTY_STRING . ") {
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
	protected function buildInitScriptText() {
		$labelId = $this->label->getId(true, true);
		$entryId = '""';
		if ($this->entry) {
			$entryId = $this->entry->getId(true, true);
		}
		$values  = Builder::getArray($this->values);
		$default = Builder::escapeText($this->getDefault(), true);
		return "
declare Label_Picker <=> (Page.GetFirstChild({$labelId}) as CMlLabel);
declare Text[] " . self::VAR_PICKER_VALUES . " as Values for Label_Picker;
Values = {$values};
declare Text " . self::VAR_PICKER_DEFAULT_VALUE . " as Default for Label_Picker;
Default = {$default};
declare Text " . self::VAR_PICKER_ENTRY_ID . " as EntryId for Label_Picker;
EntryId = {$entryId};
" . self::FUNCTION_UPDATE_PICKER_VALUE . "(Label_Picker);
";
	}

	/**
	 * Build the script text for Label clicks
	 *
	 * @return string
	 */
	protected function buildClickScriptText() {
		$labelId = $this->label->getId(true, true);
		return "
if (Event.ControlId == {$labelId}) {
	declare Label_Picker <=> (Event.Control as CMlLabel);
	" . self::FUNCTION_UPDATE_PICKER_VALUE . "(Label_Picker);
}";
	}
}
