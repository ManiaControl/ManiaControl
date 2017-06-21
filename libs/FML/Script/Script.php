<?php

namespace FML\Script;

use FML\Script\Features\ScriptFeature;

/**
 * Class representing the ManiaLink Script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Script
{

    /*
     * Constants
     */
    const TICKINTERVAL    = 250;
    const VAR_ScriptStart = "FML_ScriptStart";
    const VAR_LoopCounter = "FML_LoopCounter";
    const VAR_LastTick    = "FML_LastTick";

    /**
     * @var ScriptFeature[] $features Script Features
     */
    protected $features = array();

    /**
     * @var ScriptInclude[] $includes Script Includes
     */
    protected $includes = array();

    /**
     * @var ScriptConstant[] $constants Script Constants
     */
    protected $constants = array();

    /**
     * @var ScriptFunction[] $functions Script Functions
     */
    protected $functions = array();

    /**
     * @var ScriptLabel[] $customLabels Custom Script Labels
     */
    protected $customLabels = array();

    /**
     * @var ScriptLabel[] $genericLabels Generic Script Labels
     */
    protected $genericLabels = array();

    /**
     * Set a Script Include
     *
     * @api
     * @param string|ScriptInclude $file      Include file or ScriptInclude
     * @param string               $namespace Include namespace
     * @return static
     */
    public function setScriptInclude($file, $namespace = null)
    {
        if ($file instanceof ScriptInclude) {
            $scriptInclude = $file;
        } else {
            $scriptInclude = new ScriptInclude($file, $namespace);
        }
        $this->includes[$scriptInclude->getNamespace()] = $scriptInclude;
        return $this;
    }

    /**
     * Add a Script Constant
     *
     * @api
     * @param string|ScriptConstant $name  Constant name or ScriptConstant
     * @param string                $value Constant value
     * @return static
     */
    public function addScriptConstant($name, $value = null)
    {
        if ($name instanceof ScriptConstant) {
            $scriptConstant = $name;
        } else {
            $scriptConstant = new ScriptConstant($name, $value);
        }
        if (!in_array($scriptConstant, $this->constants)) {
            array_push($this->constants, $scriptConstant);
        }
        return $this;
    }

    /**
     * Add a Script Function
     *
     * @api
     * @param string|ScriptFunction $name Function name or ScriptFunction
     * @param string                $text Function text
     * @return static
     */
    public function addScriptFunction($name, $text = null)
    {
        if ($name instanceof ScriptFunction) {
            $scriptFunction = $name;
        } else {
            $scriptFunction = new ScriptFunction($name, $text);
        }
        if (!in_array($scriptFunction, $this->functions)) {
            array_push($this->functions, $scriptFunction);
        }
        return $this;
    }

    /**
     * Add a custom Script text
     *
     * @api
     * @param string|ScriptLabel $name Label name or ScriptLabel
     * @param string             $text Script text
     * @return static
     */
    public function addCustomScriptLabel($name, $text = null)
    {
        if ($name instanceof ScriptLabel) {
            $scriptLabel = $name;
        } else {
            $scriptLabel = new ScriptLabel($name, $text);
        }
        if (!in_array($scriptLabel, $this->customLabels)) {
            array_push($this->customLabels, $scriptLabel);
        }
        return $this;
    }

    /**
     * Append a generic Script text for the next rendering
     *
     * @TODO: get rid of generic script labels approach
     * @param string|ScriptLabel $name     Label name or ScriptLabel
     * @param string             $text     Script text
     * @param bool               $isolated (optional) Whether to isolate the Label Script
     * @return static
     */
    public function appendGenericScriptLabel($name, $text = null, $isolated = false)
    {
        if ($name instanceof ScriptLabel) {
            $scriptLabel = $name;
        } else {
            $scriptLabel = new ScriptLabel($name, $text, $isolated);
        }
        if (!in_array($scriptLabel, $this->genericLabels)) {
            array_push($this->genericLabels, $scriptLabel);
        }
        return $this;
    }

    /**
     * Remove all generic Script texts
     *
     * @TODO: get rid of generic script labels approach
     * @return static
     */
    public function resetGenericScriptLabels()
    {
        $this->genericLabels = array();
        return $this;
    }

    /**
     * Add a Script Feature
     *
     * @api
     * @param ScriptFeature $feature Script Feature
     * @return static
     */
    public function addFeature(ScriptFeature $feature)
    {
        if (!in_array($feature, $this->features, true)) {
            array_push($this->features, $feature);
        }
        return $this;
    }

    /**
     * Load the given Script Feature
     *
     * @param ScriptFeature $scriptFeature Script Feature to load
     * @return static
     */
    public function loadFeature(ScriptFeature $scriptFeature)
    {
        $scriptFeature->prepare($this);
        return $this;
    }

    /**
     * Load the given Script Features
     *
     * @param ScriptFeature[] $scriptFeatures Script Features to load
     * @return static
     */
    public function loadFeatures(array $scriptFeatures)
    {
        foreach ($scriptFeatures as $scriptFeature) {
            $this->loadFeature($scriptFeature);
        }
        return $this;
    }

    /**
     * Check if the Script has content so that it needs to be rendered
     *
     * @return bool
     */
    public function needsRendering()
    {
        if ($this->features || $this->customLabels || $this->genericLabels) {
            return true;
        }
        return false;
    }

    /**
     * Build the complete Script text
     *
     * @return string
     */
    public function buildScriptText()
    {
        $scriptText = PHP_EOL;
        $scriptText .= $this->getHeaderComment();
        $scriptText .= $this->getIncludes();
        $scriptText .= $this->getConstants();
        $scriptText .= $this->getFunctions();
        $scriptText .= $this->getLabels();
        $scriptText .= $this->getMainFunction();
        return $scriptText;
    }

    /**
     * Render the Script
     *
     * @param \DOMDocument $domDocument DOMDocument for which the Script should be created
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument)
    {
        $this->loadFeatures($this->features);

        $scriptXml  = $domDocument->createElement("script");
        $scriptText = $this->buildScriptText();

        $scriptComment = $domDocument->createComment($scriptText);
        $scriptXml->appendChild($scriptComment);

        return $scriptXml;
    }

    /**
     * Get the header comment
     *
     * @return string
     */
    protected function getHeaderComment()
    {
        $headerComment = '/**************************************************
*     FancyManiaLinks';
        if (defined('FML_VERSION')) {
            $headerComment .= ' v' . FML_VERSION;
        }
        $headerComment .= ' by steeffeen     *
*   http://github.com/steeffeen/FancyManiaLinks   *
**************************************************/

';
        return $headerComment;
    }

    /**
     * Get the Includes text
     *
     * @return string
     */
    protected function getIncludes()
    {
        $includesText = implode(PHP_EOL, $this->includes);
        return $includesText;
    }

    /**
     * Get the Constants text
     *
     * @return string
     */
    protected function getConstants()
    {
        $constantsText = implode(PHP_EOL, $this->constants);
        return $constantsText;
    }

    /**
     * Get the Functions text
     *
     * @return string
     */
    protected function getFunctions()
    {
        $functionsText = implode(PHP_EOL, $this->functions);
        return $functionsText;
    }

    /**
     * Get the Labels text
     *
     * @return string
     */
    protected function getLabels()
    {
        $customLabelsText  = implode(PHP_EOL, $this->customLabels);
        $genericLabelsText = implode(PHP_EOL, $this->genericLabels);
        return $customLabelsText . $genericLabelsText;
    }

    /**
     * Get the main function text
     *
     * @return string
     */
    protected function getMainFunction()
    {
        $mainFunction = '
Void FML_Dummy() {}
main() {
	declare ' . self::VAR_ScriptStart . ' = Now;
	+++' . ScriptLabel::ONINIT . '+++
	declare ' . self::VAR_LoopCounter . ' = 0;
	declare ' . self::VAR_LastTick . ' = 0;
	while (True) {
		yield;
		foreach (Event in PendingEvents) {
			switch (Event.Type) {
				case CMlEvent::Type::EntrySubmit: {
					+++' . ScriptLabel::ENTRYSUBMIT . '+++
				}
				case CMlEvent::Type::KeyPress: {
					+++' . ScriptLabel::KEYPRESS . '+++
				}
				case CMlEvent::Type::MouseClick: {
					+++' . ScriptLabel::MOUSECLICK . '+++
					+++' . ScriptLabel::MOUSECLICK2 . '+++
				}
				case CMlEvent::Type::MouseOut: {
					+++' . ScriptLabel::MOUSEOUT . '+++
				}
				case CMlEvent::Type::MouseOver: {
					+++' . ScriptLabel::MOUSEOVER . '+++
				}
			}
		}
		+++' . ScriptLabel::LOOP . '+++
		' . self::VAR_LoopCounter . ' += 1;
		if (' . self::VAR_LastTick . ' + ' . self::TICKINTERVAL . ' > Now) continue;
		+++' . ScriptLabel::TICK . '+++ 
		' . self::VAR_LastTick . ' = Now;
	}
}';
        return $mainFunction;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->buildScriptText();
    }

}
