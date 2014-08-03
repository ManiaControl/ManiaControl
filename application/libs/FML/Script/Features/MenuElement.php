<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Types\Scriptable;

/**
 * Menu Element for the Menu Feature
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MenuElement {
	/*
	 * Protected properties
	 */
	protected $item = null;
	protected $control = null;

	/**
	 * Create a new Menu Element
	 *
	 * @param Control $item    (optional) Item Control in the Menu bar
	 * @param Control $control (optional) Toggled Menu Control
	 */
	public function __construct(Control $item = null, Control $control = null) {
		if (!is_null($item)) {
			$this->setItem($item);
		}
		if (!is_null($control)) {
			$this->setControl($control);
		}
	}

	/**
	 * Set the Item Control
	 *
	 * @param Control $item Item Control in the Menu bar
	 * @return static
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
	 * @return static
	 */
	public function setControl(Control $control) {
		$this->control = $control->checkId();
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
