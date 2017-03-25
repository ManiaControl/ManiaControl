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
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Menu extends ScriptFeature
{

    /*
     * Constants
     */
    const FUNCTION_UPDATE_MENU = "FML_UpdateMenu";

    /**
     * @var MenuElement[] $elements Menu Elements
     */
    protected $elements = array();

    /**
     * @var MenuElement $startElement Start Element
     */
    protected $startElement = null;

    /**
     * Construct a new Menu
     *
     * @api
     * @param Control $item           (optional) Item Control in the Menu bar
     * @param Control $control        (optional) Toggled Menu Control
     * @param bool    $isStartElement (optional) Whether the Menu should start with the given Element
     */
    public function __construct(Control $item = null, Control $control = null, $isStartElement = true)
    {
        if ($item && $control) {
            $this->addItem($item, $control, $isStartElement);
        }
    }

    /**
     * Get the Menu Elements
     *
     * @api
     * @return MenuElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Add a Menu item
     *
     * @api
     * @param Control $item           Item Control in the Menu bar
     * @param Control $control        Toggled Menu Control
     * @param bool    $isStartElement (optional) Whether the Menu should start with this Element
     * @return static
     */
    public function addItem(Control $item, Control $control, $isStartElement = false)
    {
        $menuElement = new MenuElement($item, $control);
        $this->addElement($menuElement, $isStartElement);
        return $this;
    }

    /**
     * Add a Menu Element
     *
     * @api
     * @param MenuElement $menuElement    Menu Element
     * @param bool        $isStartElement (optional) Whether the Menu should start with this Element
     * @return static
     */
    public function addElement(MenuElement $menuElement, $isStartElement = false)
    {
        if (!in_array($menuElement, $this->elements, true)) {
            array_push($this->elements, $menuElement);
            if ($isStartElement) {
                // new start element
                $this->setStartElement($menuElement);
            } else {
                // additional element - set invisible
                $menuElement->getControl()
                            ->setVisible(false);
            }
        }
        return $this;
    }

    /**
     * Get the Element to start with
     *
     * @api
     * @return MenuElement
     */
    public function getStartElement()
    {
        return $this->startElement;
    }

    /**
     * Set the Element to start with
     *
     * @api
     * @param MenuElement $startElement Start Element
     * @return static
     */
    public function setStartElement(MenuElement $startElement = null)
    {
        $this->startElement = $startElement;
        if ($startElement && !in_array($startElement, $this->elements, true)) {
            array_push($this->elements, $startElement);
        }
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $updateFunctionName = self::FUNCTION_UPDATE_MENU;
        $elementsArrayText  = $this->getElementsArrayText();

        // OnInit
        if ($this->startElement) {
            $startControlId  = Builder::escapeText($this->startElement->getControl()->getId());
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
    protected function getElementsArrayText()
    {
        $elements = array();
        foreach ($this->elements as $element) {
            $elementId            = $element->getItem()
                                            ->getId();
            $elements[$elementId] = $element->getControl()
                                            ->getId();
        }
        return Builder::getArray($elements, true);
    }

}
