<?php

namespace FML\Script;

use FML\Controls\Control;
use FML\Script\Sections\Constants;
use FML\Script\Sections\Labels;
use FML\Types\Scriptable;

/**
 * ScriptFeature class offering menu behaviors
 *
 * @author steeffeen
 */
class Menus implements Constants, Labels, ScriptFeature {
	/**
	 * Constants
	 */
	const C_MENUIDS = 'C_FML_MenuIds';
	
	/**
	 * Protected properties
	 */
	protected $menus = array();

	/**
	 * Add menu behavior defined by the given relationships
	 *
	 * @param array $menuRelationships        	
	 * @return \FML\Script\Menus
	 */
	public function add(array $menuRelationships) {
		$menuIndex = count($this->menus);
		$menus = array();
		$submenus = array();
		foreach ($menuRelationships as $relationship) {
			$menuItemControl = $relationship[0];
			$subMenuControl = $relationship[1];
			
			if (!($menuItemControl instanceof Scriptable)) {
				trigger_error('No Scriptable instance given as menu item.', E_USER_ERROR);
			}
			if (!($subMenuControl instanceof Control)) {
				trigger_error('No Control instance given as submenu.', E_USER_ERROR);
			}
			
			$menuItemControl->assignId();
			$menuItemControl->setScriptEvents(true);
			$subMenuControl->assignId();
			
			array_push($menus, array($menuItemControl->getId(), $subMenuControl->getId()));
			array_push($submenus, $subMenuControl->getId());
		}
		array_push($this->menus, array($menus, $submenus));
		return $this;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Constants::getConstants()
	 */
	public function getConstants() {
		$constant = '[';
		$index = 0;
		foreach ($this->menus as $menu) {
			$constant .= '[';
			foreach ($menu[0] as $menuRel) {
				$constant .= '"' . $menuRel[0] . '" => ["' . $menuRel[1] . '"], ';
			}
			$constant .= '"__FML__Sub__Menus__" => [';
			$subIndex = 0;
			foreach ($menu[1] as $subMenu) {
				$constant .= '"' . $subMenu . '"';
				if ($subIndex < count($menu[1]) - 1) {
					$constant .= ', ';
				}
				$subIndex++;
			}
			$constant .= ']]';
			if ($index < count($this->menus) - 1) {
				$constant .= ', ';
			}
			$index++;
		}
		$constant .= ']';
		$constants = array();
		$constants[self::C_MENUIDS] = $constant;
		return $constants;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Labels::getLabels()
	 */
	public function getLabels() {
		$labels = array();
		$labelMouseClick = file_get_contents(__DIR__ . '/Templates/MenuMouseClick.txt');
		$labels[Labels::MOUSECLICK] = $labelMouseClick;
		return $labels;
	}
}

?>
