<?php

namespace FML\Script\Features;

use FML\Controls\Control;

/**
 * A Page Control
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PagingPage {
	/*
	 * Protected Properties
	 */
	protected $control = null;
	protected $number = null;

	/**
	 * Construct a new Paging Page
	 *
	 * @param Control $control    (optional) Page Control
	 * @param int     $pageNumber (optional) Number of the Page
	 */
	public function __construct(Control $control = null, $pageNumber = 1) {
		$this->setControl($control);
		$this->setPageNumber($pageNumber);
	}

	/**
	 * Set the Page Control
	 *
	 * @param Control $control Page Control
	 * @return \FML\Script\Features\PagingPage
	 */
	public function setControl(Control $control) {
		$control->checkId();
		$this->control = $control;
		return $this;
	}

	/**
	 * Get the Page Control
	 *
	 * @return \FML\Controls\Control
	 */
	public function getControl() {
		return $this->control;
	}

	/**
	 * Set the Page Number
	 *
	 * @param int $pageNumber Number of the Page
	 * @return \FML\Script\Features\PagingPage
	 */
	public function setPageNumber($pageNumber) {
		$this->number = (int)$pageNumber;
		return $this;
	}

	/**
	 * Get the Page Number
	 *
	 * @return int
	 */
	public function getPageNumber() {
		return $this->number;
	}
}
