<?php

namespace FML\Script;

use FML\Controls\Control;
use FML\Script\Sections\Constants;
use FML\Script\Sections\Globals;
use FML\Script\Sections\Includes;
use FML\Script\Sections\Labels;
use FML\Types\Scriptable;
use FML\Controls\Label;

/**
 * ScriptFeature class offering paging
 *
 * @author steeffeen
 */
class Pages implements Constants, Globals, Includes, Labels, ScriptFeature {
	/**
	 * Constants
	 */
	const C_PAGEIDS = 'C_FML_PageIds';
	
	/**
	 * Protected properties
	 */
	protected $pages = array();

	/**
	 * Add paging behavior
	 *
	 * @param array $pageButtons        	
	 * @param array $pages        	
	 * @param Label $counterLabel        	
	 * @return \FML\Script\Pages
	 */
	public function add(array $pageButtons, array $pages, Label $counterLabel = null) {
		$actionIds = array();
		foreach ($pageButtons as $action => $pageButton) {
			if (!($pageButton instanceof Control)) {
				trigger_error('No Control instance given.', E_USER_ERROR);
			}
			$pageButton->assignId();
			if (!($pageButton instanceof Scriptable)) {
				trigger_error('No Scriptable instance given.', E_USER_ERROR);
			}
			$pageButton->setScriptEvents(true);
			
			$actionIds[$pageButton->getId()] = $action;
		}
		
		$pageIds = array();
		foreach ($pages as $page) {
			if (!($page instanceof Control)) {
				trigger_error('No Control instance given.', E_USER_ERROR);
			}
			$page->assignId();
			if (!empty($pageIds)) {
				$page->setVisible(false);
			}
			array_push($pageIds, $page->getId());
		}
		
		if ($counterLabel) {
			$counterLabel->assignId();
			$counterId = $counterLabel->getId();
		}
		else {
			$counterId = uniqid();
		}
		
		array_push($this->pages, array($actionIds, $pageIds, $counterId));
	}

	/**
	 *
	 * @see \FML\Script\Sections\Includes::getIncludes()
	 */
	public function getIncludes() {
		$includes = array();
		$includes["TextLib"] = "TextLib";
		return $includes;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Constants::getConstants()
	 */
	public function getConstants() {
		$constant = '[';
		$index = 0;
		foreach ($this->pages as $page) {
			$constant .= '[';
			$actionIds = $page[0];
			foreach ($actionIds as $actionId => $action) {
				$constant .= '"' . $actionId . '" => ["' . $action . '"], ';
			}
			$constant .= '"__FML__Pages__Id__" => ["' . $page[2] . '"], ';
			$constant .= '"__FML__Pages__Ids__" => [';
			$subIndex = 0;
			foreach ($page[1] as $pageId) {
				$constant .= '"' . $pageId . '"';
				if ($subIndex < count($page[1]) - 1) {
					$constant .= ', ';
				}
				$subIndex++;
			}
			$constant .= ']]';
			if ($index < count($this->pages) - 1) {
				$constant .= ', ';
			}
			$index++;
		}
		$constant .= ']';
		$constants = array();
		$constants[self::C_PAGEIDS] = $constant;
		return $constants;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Globals::getGlobals()
	 */
	public function getGlobals() {
		$globals = array();
		$globals['G_FML_PageIndexes'] = 'Integer[Text]';
		return $globals;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Labels::getLabels()
	 */
	public function getLabels() {
		$labels = array();
		$labelOnInit = file_get_contents(__DIR__ . '/Templates/PageOnInit.txt');
		$labels[Labels::ONINIT] = $labelOnInit;
		$labelMouseClick = file_get_contents(__DIR__ . '/Templates/PageMouseClick.txt');
		$labels[Labels::MOUSECLICK] = $labelMouseClick;
		return $labels;
	}
}

?>
