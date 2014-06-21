<?php

namespace FML\Script\Features;

use FML\Controls\Control;

/**
 * Paging Page
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PagingPage {
	/*
	 * Protected properties
	 */
	/** @var Control $control */
	protected $control = null;
	protected $number = null;

	/**
	 * Construct a new Paging Page
	 *
	 * @param Control $control    (optional) Page Control
	 * @param int     $pageNumber (optional) Number of the Page
	 */
	public function __construct(Control $control = null, $pageNumber = 1) {
		if (!is_null($control)) {
			$this->setControl($control);
		}
		$this->setPageNumber($pageNumber);
	}

	/**
	 * Set the Page Control
	 *
	 * @param Control $control Page Control
	 * @return \FML\Script\Features\PagingPage|static
	 */
	public function setControl(Control $control) {
		$this->control = $control->checkId();
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
	 * Set the Page number
	 *
	 * @param int $pageNumber Number of the Page
	 * @return \FML\Script\Features\PagingPage|static
	 */
	public function setPageNumber($pageNumber) {
		$this->number = (int)$pageNumber;
		return $this;
	}

	/**
	 * Get the Page number
	 *
	 * @return int
	 */
	public function getPageNumber() {
		return $this->number;
	}
}
