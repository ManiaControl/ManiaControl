<?php

namespace FML\Script\Features;

use FML\Components\CheckBoxDesign;
use FML\Controls\Entry;
use FML\Controls\Quad;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature for creating a CheckBox behavior
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBoxFeature extends ScriptFeature
{

    /*
     * Constants
     */
    const FUNCTION_UPDATE_QUAD_DESIGN = "FML_UpdateQuadDesign";
    const VAR_CHECKBOX_ENABLED        = "FML_CheckBox_Enabled";
    const VAR_CHECKBOX_DESIGNS        = "FML_CheckBox_Designs";
    const VAR_CHECKBOX_ENTRY_ID       = "FML_CheckBox_EntryId";

    /**
     * @var Quad $quad CheckBox Quad
     */
    protected $quad = null;

    /**
     * @var Entry $entry Hidden Entry for submitting the value
     */
    protected $entry = null;

    /**
     * @var bool $default Default value
     */
    protected $default = null;

    /**
     * @var CheckBoxDesign $enabledDesign Enabled Design
     */
    protected $enabledDesign = null;

    /**
     * @var CheckBoxDesign $disabledDesign Disabled Design
     */
    protected $disabledDesign = null;

    /**
     * Construct a new CheckBox Feature
     *
     * @api
     * @param Quad  $quad    (optional) CheckBox Quad
     * @param Entry $entry   (optional) Hidden Entry
     * @param bool  $default (optional) Default value
     */
    public function __construct(Quad $quad = null, Entry $entry = null, $default = null)
    {
        if ($quad) {
            $this->setQuad($quad);
        }
        if ($entry) {
            $this->setEntry($entry);
        }
        if ($default !== null) {
            $this->setDefault($default);
        }
        $this->setEnabledDesign(CheckBoxDesign::defaultDesign());
        $this->setDisabledDesign(CheckBoxDesign::defaultDesign());
    }

    /**
     * Get the CheckBox Quad
     *
     * @api
     * @return Quad
     */
    public function getQuad()
    {
        return $this->quad;
    }

    /**
     * Set the CheckBox Quad
     *
     * @api
     * @param Quad $quad CheckBox Quad
     * @return static
     */
    public function setQuad(Quad $quad)
    {
        $quad->checkId();
        $quad->setScriptEvents(true);
        $this->quad = $quad;
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
     * Get the default value
     *
     * @api
     * @return bool
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the default value
     *
     * @api
     * @param bool $default Default value
     * @return static
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;
        return $this;
    }

    /**
     * Get the enabled Design
     *
     * @api
     * @return CheckBoxDesign
     */
    public function getEnabledDesign()
    {
        return $this->enabledDesign;
    }

    /**
     * Set the enabled Design
     *
     * @api
     * @param CheckBoxDesign $checkBoxDesign Enabled CheckBox Design
     * @return static
     */
    public function setEnabledDesign(CheckBoxDesign $checkBoxDesign)
    {
        $this->enabledDesign = $checkBoxDesign;
        return $this;
    }

    /**
     * Get the disabled Design
     *
     * @api
     * @return CheckBoxDesign
     */
    public function getDisabledDesign()
    {
        return $this->disabledDesign;
    }

    /**
     * Set the disabled Design
     *
     * @api
     * @param CheckBoxDesign $checkBoxDesign Disabled CheckBox Design
     * @return static
     */
    public function setDisabledDesign(CheckBoxDesign $checkBoxDesign)
    {
        $this->disabledDesign = $checkBoxDesign;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
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
    protected function buildUpdateQuadDesignFunction()
    {
        return "
Void " . self::FUNCTION_UPDATE_QUAD_DESIGN . "(CMlQuad _Quad) {
	declare " . self::VAR_CHECKBOX_ENABLED . " as Enabled for _Quad = True;
	Enabled = !Enabled;
	_Quad.StyleSelected = Enabled;
	declare " . self::VAR_CHECKBOX_DESIGNS . " as Designs for _Quad = Text[Boolean];
	declare Design = Designs[Enabled];
	declare DesignParts = TextLib::Split(\"|\", Design);
	if (DesignParts.count == 2) {
		_Quad.Style = DesignParts[0];
		_Quad.Substyle = DesignParts[1];
	} else {
		_Quad.ImageUrl = Design;
	}
	declare " . self::VAR_CHECKBOX_ENTRY_ID . " as EntryId for _Quad = \"\";
	if (EntryId != \"\") {
		declare Entry <=> (Page.GetFirstChild(EntryId) as CMlEntry);
		if (Entry != Null) {
		    if (Enabled) {
		        Entry.Value = \"1\";
    		} else {
		        Entry.Value = \"0\";
    		}
		}
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
        $quadId  = Builder::getId($this->getQuad());
        $entryId = Builder::EMPTY_STRING;
        if ($this->entry) {
            $entryId = Builder::getId($this->getEntry());
        }

        $default              = Builder::getBoolean($this->default);
        $enabledDesignString  = $this->enabledDesign->getDesignString();
        $disabledDesignString = $this->disabledDesign->getDesignString();

        return "
declare Quad_CheckBox <=> (Page.GetFirstChild(\"{$quadId}\") as CMlQuad);
declare Text[Boolean] " . self::VAR_CHECKBOX_DESIGNS . " as Designs for Quad_CheckBox;
Designs[True] = \"{$enabledDesignString}\";
Designs[False] = \"{$disabledDesignString}\";
declare Boolean " . self::VAR_CHECKBOX_ENABLED . " as Enabled for Quad_CheckBox;
Enabled = !{$default};
declare Text " . self::VAR_CHECKBOX_ENTRY_ID . " as EntryId for Quad_CheckBox;
EntryId = \"{$entryId}\";
" . self::FUNCTION_UPDATE_QUAD_DESIGN . "(Quad_CheckBox);
";
    }

    /**
     * Build the script text for Quad clicks
     *
     * @return string
     */
    protected function buildClickScriptText()
    {
        $quadId = Builder::getId($this->getQuad());
        return "
if (Event.ControlId == \"{$quadId}\") {
	declare Quad_CheckBox <=> (Event.Control as CMlQuad);
	" . self::FUNCTION_UPDATE_QUAD_DESIGN . "(Quad_CheckBox);
}";
    }

}
