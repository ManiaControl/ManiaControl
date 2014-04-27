<?php

namespace FML\Script;

use FML\Script\Features\ScriptFeature;

/**
 * Class representing the ManiaLink Script
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Script {
	/*
	 * Constants
	 */
	const TICKINTERVAL = 250;
	const VAR_ScriptStart = 'FML_ScriptStart';
	const VAR_LoopCounter = 'FML_LoopCounter';
	const VAR_LastTick = 'FML_LastTick';
	
	/*
	 * Protected Properties
	 */
	protected $tagName = 'script';
	protected $features = array();
	protected $includes = array();
	protected $constants = array();
	protected $functions = array();
	protected $customLabels = array();
	protected $genericLabels = array();

	/**
	 * Set a Script Include
	 *
	 * @param string $file Include File
	 * @param string $namespace Include Namespace
	 * @return \FML\Script\Script
	 */
	public function setScriptInclude($file, $namespace = null) {
		if (is_object($file) && ($file instanceof ScriptInclude)) {
			$scriptInclude = $file;
		}
		else {
			$scriptInclude = new ScriptInclude($file, $namespace);
		}
		$this->includes[$scriptInclude->getNamespace()] = $scriptInclude;
		return $this;
	}

	/**
	 * Add a Script Constant
	 *
	 * @param string $name Constant Name
	 * @param string $value Constant Value
	 * @return \FML\Script\Script
	 */
	public function addScriptConstant($name, $value = null) {
		if (is_object($name) && ($name instanceof ScriptConstant)) {
			$scriptConstant = $name;
		}
		else {
			$scriptConstant = new ScriptConstant($name, $value);
		}
		array_push($this->constants, $scriptConstant);
		return $this;
	}

	/**
	 * Add a Script Function
	 *
	 * @param string $name Function Name
	 * @param string $text Function Text
	 * @return \FML\Script\Script
	 */
	public function addScriptFunction($name, $text = null) {
		if (is_object($name) && ($name instanceof ScriptFunction)) {
			$scriptFunction = $name;
		}
		else {
			$scriptFunction = new ScriptFunction($name, $text);
		}
		$this->functions[$scriptFunction->getName()] = $scriptFunction;
		return $this;
	}

	/**
	 * Add a custom Script Text
	 *
	 * @param string $name Label Name
	 * @param string $text Script Text
	 * @return \FML\Script\Script
	 */
	public function addCustomScriptLabel($name, $text = null) {
		if (is_object($name) && ($name instanceof ScriptLabel)) {
			$scriptLabel = $name;
		}
		else {
			$scriptLabel = new ScriptLabel($name, $text);
		}
		array_push($this->customLabels, $scriptLabel);
		return $this;
	}

	/**
	 * Append a generic Script Text for the next Rendering
	 *
	 * @param string $name Label Name
	 * @param string $text Script Text
	 * @param bool $isolated (optional) Whether to isolate the Label Script
	 * @return \FML\Script\Script
	 */
	public function appendGenericScriptLabel($name, $text = null, $isolated = false) {
		if (is_object($name) && ($name instanceof ScriptLabel)) {
			$scriptLabel = $name;
		}
		else {
			$scriptLabel = new ScriptLabel($name, $text, $isolated);
		}
		array_push($this->genericLabels, $scriptLabel);
		return $this;
	}

	/**
	 * Remove all generic Script Texts
	 *
	 * @return \FML\Script\Script
	 */
	public function resetGenericScriptLabels() {
		$this->genericLabels = array();
		return $this;
	}

	/**
	 * Add an own Script Feature
	 *
	 * @param ScriptFeature $feature Script Feature
	 * @return \FML\Script\Script
	 */
	public function addFeature(ScriptFeature $feature) {
		array_push($this->features, $feature);
		return $this;
	}

	/**
	 * Load the given Script Feature
	 *
	 * @param ScriptFeature $scriptFeature Script Feature to load
	 * @return \FML\Script\Script
	 */
	public function loadFeature(ScriptFeature $scriptFeature) {
		$scriptFeature->prepare($this);
		return $this;
	}

	/**
	 * Load the given Script Features
	 *
	 * @param array $scriptFeatures Script Features to load
	 * @return \FML\Script\Script
	 */
	public function loadFeatures(array $scriptFeatures) {
		foreach ($scriptFeatures as $scriptFeature) {
			$this->loadFeature($scriptFeature);
		}
		return $this;
	}

	/**
	 * Check if the Script has Stuff so that it needs to be rendered
	 *
	 * @return bool
	 */
	public function needsRendering() {
		if ($this->features || $this->customLabels || $this->genericLabels) {
			return true;
		}
		return false;
	}

	/**
	 * Build the complete Script Text
	 *
	 * @return string
	 */
	public function buildScriptText() {
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
	 * Create the Script XML Tag
	 *
	 * @param \DOMDocument $domDocument DOMDocument for which the XML Element should be created
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$this->loadFeatures($this->features);
		$scriptXml = $domDocument->createElement($this->tagName);
		$scriptText = $this->buildScriptText();
		$scriptComment = $domDocument->createComment($scriptText);
		$scriptXml->appendChild($scriptComment);
		return $scriptXml;
	}

	/**
	 * Get the Header Comment
	 *
	 * @return string
	 */
	protected function getHeaderComment() {
		$headerComment = '/****************************************************
*		FancyManiaLinks v' . FML_VERSION . ' by steeffeen	 		*
*	http://github.com/steeffeen/FancyManiaLinks		*
****************************************************/
';
		return $headerComment;
	}

	/**
	 * Get the Includes
	 *
	 * @return string
	 */
	protected function getIncludes() {
		$includesText = implode(PHP_EOL, $this->includes);
		return $includesText;
	}

	/**
	 * Get the Constants
	 *
	 * @return string
	 */
	protected function getConstants() {
		$constantsText = implode(PHP_EOL, $this->constants);
		return $constantsText;
	}

	/**
	 * Get the Functions
	 *
	 * @return string
	 */
	protected function getFunctions() {
		$functionsText = implode(PHP_EOL, $this->functions);
		return $functionsText;
	}

	/**
	 * Get the Labels
	 *
	 * @return string
	 */
	protected function getLabels() {
		$customLabelsText = implode(PHP_EOL, $this->customLabels);
		$genericLabelsText = implode(PHP_EOL, $this->genericLabels);
		return $customLabelsText . $genericLabelsText;
	}

	/**
	 * Get the Main Function
	 *
	 * @return string
	 */
	protected function getMainFunction() {
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
}
