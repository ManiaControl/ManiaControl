<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Types\Scriptable;

/**
 * An Element for the Menu Feature
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MenuElement {
	/*
	 * Protected Properties
	 */
	protected $item = null;
	protected $control = null;

	/**
	 * Create a new Menu Element
	 *
	 * @param Control $item    (optional) Item Control in the Menu Bar
	 * @param Control $control (optional) Toggled Menu Control
	 */
	public function __construct(Control $item = null, Control $control = null) {
		$this->setItem($item);
		$this->setControl($control);
	}

	/**
	 * Set the Item Control
	 *
	 * @param Control $item Item Control in the Menu Bar
	 * @return \FML\Script\Features\MenuElement
	 */
	public function setItem(Control $item) {
		$item->checkId();
		if ($item instanceof Scriptable) {
			$item->setScriptEvents(true);
		}
		$this->item = $item;
		return $this;
	}

	/**
	 * Get the Item Control
	 *
	 * @return \FML\Controls\Control
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * Set the Menu Control
	 *
	 * @param Control $control Toggled Menu Control
	 * @return \FML\Script\Features\MenuElement
	 */
	public function setControl(Control $control) {
		$control->checkId();
		$this->control = $control;
		return $this;
	}

	/**
	 * Get the Menu Control
	 *
	 * @return \FML\Controls\Control
	 */
	public function getControl() {
		return $this->control;
	}
}
