<?php

namespace FML\Script\Features;

use FML\Controls\Entry;
use FML\Controls\Quad;
use FML\Models\CheckBoxDesign;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature for creating a CheckBox behavior
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBoxFeature extends ScriptFeature {
	/*
	 * Constants
	 */
	const FUNCTION_UPDATE_QUAD_DESIGN = 'FML_UpdateQuadDesign';
	const VAR_CHECKBOX_ENABLED        = 'FML_CheckBox_Enabled';
	const VAR_CHECKBOX_DESIGNS        = 'FML_CheckBox_Designs';
	const VAR_CHECKBOX_ENTRY_ID       = 'FML_CheckBox_EntryId';

	/*
	 * Protected properties
	 */
	/** @var Quad $quad */
	protected $quad = null;
	/** @var Entry $entry */
	protected $entry = null;
	protected $default = null;
	/** @var CheckBoxDesign $enabledDesign */
	protected $enabledDesign = null;
	/** @var CheckBoxDesign $disabledDesign */
	protected $disabledDesign = null;

	/**
	 * Construct a new CheckBox Feature
	 *
	 * @param Quad  $quad    (optional) CheckBox Quad
	 * @param Entry $entry   (optional) Hidden Entry
	 * @param bool  $default (optional) Default value
	 */
	public function __construct(Quad $quad = null, Entry $entry = null, $default = null) {
		if (!is_null($quad)) {
			$this->setQuad($quad);
		}
		if (!is_null($entry)) {
			$this->setEntry($entry);
		}
		if (!is_null($default)) {
			$this->setDefault($default);
		}
		$this->setEnabledDesign(CheckBoxDesign::defaultEnabledDesign());
		$this->setDisabledDesign(CheckBoxDesign::defaultDisabledDesign());
	}

	/**
	 * Set the CheckBox Quad
	 *
	 * @param Quad $quad CheckBox Quad
	 * @return \FML\Script\Features\CheckBoxFeature|static
	 */
	public function setQuad(Quad $quad) {
		$this->quad = $quad->checkId()->setScriptEvents(true);
		return $this;
	}

	/**
	 * Get the CheckBox Quad
	 *
	 * @return \FML\Controls\Quad
	 */
	public function getQuad() {
		return $this->quad;
	}

	/**
	 * Set the CheckBox Entry
	 *
	 * @param Entry $entry CheckBox Entry
	 * @return \FML\Script\Features\CheckBoxFeature|static
	 */
	public function setEntry(Entry $entry) {
		$this->entry = $entry->checkId();
		return $this;
	}

	/**
	 * Get the managed Entry
	 *
	 * @return \FML\Controls\Entry
	 */
	public function getEntry() {
		return $this->entry;
	}

	/**
	 * Set the default value
	 *
	 * @param bool $default Default value
	 * @return \FML\Script\Features\CheckBoxFeature|static
	 */
	public function setDefault($default) {
		$this->default = (bool)$default;
		return $this;
	}

	/**
	 * Set the enabled Design
	 *
	 * @param CheckBoxDesign $checkBoxDesign Enabled CheckBox Design
	 * @return \FML\Script\Features\CheckBoxFeature|static
	 */
	public function setEnabledDesign(CheckBoxDesign $checkBoxDesign) {
		$this->enabledDesign = $checkBoxDesign;
		return $this;
	}

	/**
	 * Set the disabled Design
	 *
	 * @param CheckBoxDesign $checkBoxDesign Disabled CheckBox Design
	 * @return \FML\Script\Features\CheckBoxFeature|static
	 */
	public function setDisabledDesign(CheckBoxDesign $checkBoxDesign) {
		$this->disabledDesign = $checkBoxDesign;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		if ($this->getQuad()) {
			$script->setScriptInclude(ScriptInclude::TEXTLIB);
			$script->addScriptFunction(self::FUNCTION_UPDATE_QUAD_DESIGN, $this->buildUpdateQuadDesignFunction());
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
	protected function buildUpdateQuadDesignFunction() {
		return "
Void " . self::FUNCTION_UPDATE_QUAD_DESIGN . "(CMlQuad _Quad) {
	declare " . self::VAR_CHECKBOX_ENABLED . " as Enabled for _Quad = True;
	Enabled = !Enabled;
	_Quad.StyleSelected = Enabled;
	declare " . self::VAR_CHECKBOX_DESIGNS . " as Designs for _Quad = Text[Boolean];
	declare Design = Designs[Enabled];
	declare DesignParts = TextLib::Split(\"|\", Design);
	if (DesignParts.count > 1) {
		_Quad.Style = DesignParts[0];
		_Quad.Substyle = DesignParts[1];
	} else {
		_Quad.ImageUrl = Design;
	}
	declare " . self::VAR_CHECKBOX_ENTRY_ID . " as EntryId for _Quad = " . Builder::EMPTY_STRING . ";
	if (EntryId != " . Builder::EMPTY_STRING . ") {
		declare Value = \"0\";
		if (Enabled) {
			Value = \"1\";
		}
		declare Entry <=> (Page.GetFirstChild(EntryId) as CMlEntry);
		Entry.Value = Value;
	}
}";
	}

	/**
	 * Build the init script text
	 *
	 * @return string
	 */
	protected function buildInitScriptText() {
		$quadId  = $this->getQuad()->getId(true, true);
		$entryId = '""';
		if ($this->entry) {
			$entryId = $this->entry->getId(true, true);
		}
		$default              = Builder::getBoolean($this->default);
		$enabledDesignString  = $this->enabledDesign->getDesignString();
		$disabledDesignString = $this->disabledDesign->getDesignString();
		return "
declare Quad_CheckBox <=> (Page.GetFirstChild({$quadId}) as CMlQuad);
declare Text[Boolean] " . self::VAR_CHECKBOX_DESIGNS . " as Designs for Quad_CheckBox;
Designs[True] = {$enabledDesignString};
Designs[False] = {$disabledDesignString};
declare Boolean " . self::VAR_CHECKBOX_ENABLED . " as Enabled for Quad_CheckBox;
Enabled = !{$default};
declare Text " . self::VAR_CHECKBOX_ENTRY_ID . " as EntryId for Quad_CheckBox;
EntryId = {$entryId};
" . self::FUNCTION_UPDATE_QUAD_DESIGN . "(Quad_CheckBox);
";
	}

	/**
	 * Build the script text for Quad clicks
	 *
	 * @return string
	 */
	protected function buildClickScriptText() {
		$quadId = $this->getQuad()->getId(true, true);
		return "
if (Event.ControlId == {$quadId}) {
	declare Quad_CheckBox <=> (Event.Control as CMlQuad);
	" . self::FUNCTION_UPDATE_QUAD_DESIGN . "(Quad_CheckBox);
}";
	}
}
