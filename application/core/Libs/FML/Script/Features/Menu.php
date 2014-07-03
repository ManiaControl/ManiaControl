<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;

/**
 * Script Feature realising a Menu showing specific Controls for the different items
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Menu extends ScriptFeature {
	/*
	 * Constants
	 */
	const FUNCTION_UPDATE_MENU = 'FML_UpdateMenu';

	/*
	 * Protected properties
	 */
	/** @var MenuElement[] $elements */
	protected $elements = array();
	/** @var MenuElement $startElement */
	protected $startElement = null;

	/**
	 * Construct a new Menu Feature
	 *
	 * @param Control $item    (optional) Item Control in the Menu bar
	 * @param Control $control (optional) Toggled Menu Control
	 */
	public function __construct(Control $item = null, Control $control = null) {
		if ($item && $control) {
			$this->addElement($item, $control);
		}
	}

	/**
	 * Add a new Element to the Menu
	 *
	 * @param Control $item           Item Control in the Menu bar
	 * @param Control $control        Toggled Menu Control
	 * @param bool    $isStartElement (optional) Whether the Menu should start with this Element
	 * @return static
	 */
	public function addElement(Control $item, Control $control, $isStartElement = false) {
		$menuElement = new MenuElement($item, $control);
		$this->appendElement($menuElement, $isStartElement);
		return $this;
	}

	/**
	 * Append an Element to the Menu
	 *
	 * @param MenuElement $menuElement    Menu Element
	 * @param bool        $isStartElement (optional) Whether the Menu should start with this Element
	 * @return static
	 */
	public function appendElement(MenuElement $menuElement, $isStartElement = false) {
		if (!in_array($menuElement, $this->elements, true)) {
			array_push($this->elements, $menuElement);
			if ($isStartElement) {
				$this->setStartElement($menuElement);
			} else if (count($this->elements) > 1) {
				$menuElement->getControl()->setVisible(false);
			}
		}
		return $this;
	}

	/**
	 * Set the Element to start with
	 *
	 * @param MenuElement $startElement Starting Element
	 * @return static
	 */
	public function setStartElement(MenuElement $startElement) {
		$this->startElement = $startElement;
		if (!in_array($startElement, $this->elements, true)) {
			array_push($this->elements, $startElement);
		}
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$updateFunctionName = self::FUNCTION_UPDATE_MENU;
		$elementsArrayText  = $this->getElementsArrayText();

		// OnInit
		if ($this->startElement) {
			$startControlId = $this->startElement->getControl()->getId(true, true);
			$initScriptText = "
{$updateFunctionName}({$elementsArrayText}, {$startControlId});";
			$script->appendGenericScriptLabel(ScriptLabel::ONINIT, $initScriptText, true);
		}

		// MouseClick
		$scriptText = "
declare MenuElements = {$elementsArrayText};
if (MenuElements.existskey(Event.Control.ControlId)) {
	declare ShownControlId = MenuElements[Event.Control.ControlId];
	{$updateFunctionName}(MenuElements, ShownControlId);
}";
		$script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK, $scriptText, true);

		// Update menu function
		$updateFunctionText = "
Void {$updateFunctionName}(Text[Text] _Elements, Text _ShownControlId) {
	foreach (ItemId => ControlId in _Elements) {
		declare Control <=> (Page.GetFirstChild(ControlId));
		Control.Visible = (ControlId == _ShownControlId);
	}
}";
		$script->addScriptFunction($updateFunctionName, $updateFunctionText);

		return $this;
	}

	/**
	 * Build the array text for the Elements
	 *
	 * @return string
	 */
	protected function getElementsArrayText() {
		$elements = array();
		foreach ($this->elements as $element) {
			$elementId            = $element->getItem()->getId();
			$elements[$elementId] = $element->getControl()->getId();
		}
		return Builder::getArray($elements, true);
	}
}
