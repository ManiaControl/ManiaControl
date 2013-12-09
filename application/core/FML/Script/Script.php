<?php

namespace FML\Script;

use FML\Script\Sections\Constants;
use FML\Script\Sections\Functions;
use FML\Script\Sections\Globals;
use FML\Script\Sections\Includes;
use FML\Script\Sections\Labels;

/**
 * Class representing the Manialink Script
 *
 * @author steeffeen
 */
class Script {
	/**
	 * Protected properties
	 */
	protected $features = array();

	/**
	 * Add a script feature
	 *
	 * @param ScriptFeature $scriptFeature        	
	 * @return \FML\Script\Script
	 */
	public function addFeature(ScriptFeature $scriptFeature) {
		array_push($this->features, $scriptFeature);
		return $this;
	}

	/**
	 * Remove all script features
	 *
	 * @return \FML\Script\Script
	 */
	public function removeFeatures() {
		$this->features = array();
		return $this;
	}

	/**
	 * Create the script xml tag
	 *
	 * @param \DOMDocument $domDocument        	
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$scriptXml = $domDocument->createElement('script');
		$scriptText = $this->buildScriptText();
		$scriptComment = $domDocument->createComment($scriptText);
		$scriptXml->appendChild($scriptComment);
		return $scriptXml;
	}

	/**
	 * Build the complete script text based on all script items
	 *
	 * @return string
	 */
	private function buildScriptText() {
		$scriptText = "";
		$scriptText = $this->addHeaderPart($scriptText);
		$scriptText = $this->addIncludesPart($scriptText);
		$scriptText = $this->addConstantsPart($scriptText);
		$scriptText = $this->addGlobalsPart($scriptText);
		$scriptText = $this->addLabelsPart($scriptText);
		$scriptText = $this->addFunctionsPart($scriptText);
		$scriptText = $this->addMainPart($scriptText);
		return $scriptText;
	}

	/**
	 * Add the header comment to the script
	 * 
	 * @param string $scriptText
	 * @return string
	 */
	private function addHeaderPart($scriptText) {
		$headerPart = file_get_contents(__DIR__ . '/Templates/Header.txt');
		return $scriptText . $headerPart;
	}

	/**
	 * Add the includes to the script
	 *
	 * @param string $scriptText        	
	 * @return string
	 */
	private function addIncludesPart($scriptText) {
		$includes = array();
		foreach ($this->features as $feature) {
			if (!($feature instanceof Includes)) {
				continue;
			}
			$featureIncludes = $feature->getIncludes();
			foreach ($featureIncludes as $namespace => $fileName) {
				$includes[$namespace] = $fileName;
			}
		}
		$includesPart = PHP_EOL;
		foreach ($includes as $namespace => $fileName) {
			$includesPart .= "#Include \"{$fileName}\" as {$namespace}" . PHP_EOL;
		}
		return $scriptText . $includesPart;
	}

	/**
	 * Add the declared constants to the script
	 *
	 * @param string $scriptText        	
	 * @return string
	 */
	private function addConstantsPart($scriptText) {
		$constants = array();
		foreach ($this->features as $feature) {
			if (!($feature instanceof Constants)) {
				continue;
			}
			$featureConstants = $feature->getConstants();
			foreach ($featureConstants as $name => $value) {
				$constants[$name] = $value;
			}
		}
		$constantsPart = PHP_EOL;
		foreach ($constants as $name => $value) {
			$constantsPart .= "#Const {$name} {$value}" . PHP_EOL;
		}
		return $scriptText . $constantsPart;
	}

	/**
	 * Add the declared global variables to the script
	 *
	 * @param string $scriptText        	
	 * @return string
	 */
	private function addGlobalsPart($scriptText) {
		$globals = array();
		foreach ($this->features as $feature) {
			if (!($feature instanceof Globals)) {
				continue;
			}
			$featureGlobals = $feature->getGlobals();
			foreach ($featureGlobals as $name => $type) {
				$globals[$name] = $type;
			}
		}
		$globalsPart = PHP_EOL;
		foreach ($globals as $name => $type) {
			$globalsPart .= "declare {$type} {$name};" . PHP_EOL;
		}
		return $scriptText . $globalsPart;
	}

	/**
	 * Add the implemented labels to the script
	 *
	 * @param string $scriptText        	
	 * @return string
	 */
	private function addLabelsPart($scriptText) {
		$labels = array();
		foreach ($this->features as $feature) {
			if (!($feature instanceof Labels)) {
				continue;
			}
			$featureLabels = $feature->getLabels();
			foreach ($featureLabels as $name => $implementation) {
				$label = array($name, $implementation);
				array_push($labels, $label);
			}
		}
		$labelsPart = PHP_EOL;
		foreach ($labels as $label) {
			$labelsPart .= '***' . $label[0] . '***' . PHP_EOL . '***' . PHP_EOL . $label[1] . PHP_EOL . '***' . PHP_EOL;
		}
		return $scriptText . $labelsPart;
	}

	/**
	 * Add the declared functions to the script
	 *
	 * @param string $scriptText        	
	 * @return string
	 */
	private function addFunctionsPart($scriptText) {
		$functions = array();
		foreach ($this->features as $feature) {
			if (!($feature instanceof Functions)) {
				continue;
			}
			$featureFunctions = $feature->getFunctions();
			foreach ($featureFunctions as $signature => $implementation) {
				$functions[$signature] = $implementation;
			}
		}
		$functionsPart = PHP_EOL;
		foreach ($functions as $signature => $implementation) {
			$functionsPart .= $signature . '{' . PHP_EOL . $implementation . PHP_EOL . '}' . PHP_EOL;
		}
		return $scriptText . $functionsPart;
	}

	/**
	 * Add the main function to the script
	 *
	 * @param string $scriptText        	
	 * @return string
	 */
	private function addMainPart($scriptText) {
		$mainPart = file_get_contents(__DIR__ . '/Templates/Main.txt');
		return $scriptText . $mainPart;
	}
}
