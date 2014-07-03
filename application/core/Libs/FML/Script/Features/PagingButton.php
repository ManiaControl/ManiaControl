<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Types\Scriptable;

/**
 * Paging Button for browsing through Pages
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PagingButton {
	/*
	 * Protected properties
	 */
	/** @var Control $control */
	protected $control = null;
	protected $browseAction = null;

	/**
	 * Construct a new Paging Button
	 *
	 * @param Control $control      (optional) Browse Control
	 * @param int     $browseAction (optional) Number of browsed Pages per Click
	 */
	public function __construct(Control $control = null, $browseAction = null) {
		if (!is_null($control)) {
			$this->setControl($control);
		}
		if (!is_null($browseAction)) {
			$this->setBrowseAction($browseAction);
		}
	}

	/**
	 * Set the Button Control
	 *
	 * @param Control $control Browse Control
	 * @return static
	 */
	public function setControl(Control $control) {
		$control->checkId();
		if ($control instanceof Scriptable) {
			$control->setScriptEvents(true);
		}
		$this->control = $control;
		return $this;
	}

	/**
	 * Get the Button Control
	 *
	 * @return \FML\Controls\Control
	 */
	public function getControl() {
		return $this->control;
	}

	/**
	 * Set the browse action
	 *
	 * @param int $browseAction Number of browsed Pages per click
	 * @return static
	 */
	public function setBrowseAction($browseAction) {
		$this->browseAction = (int)$browseAction;
		return $this;
	}

	/**
	 * Get the browse action
	 *
	 * @return int
	 */
	public function getBrowseAction() {
		return $this->browseAction;
	}
}
