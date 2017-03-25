<?php

namespace FML\Script\Features;

use FML\Controls\Control;

/**
 * Paging Page
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PagingPage
{

    /**
     * @var Control $control Page Control
     */
    protected $control = null;

    /**
     * @var int $pageNumber Page number
     */
    protected $pageNumber = null;

    /**
     * Construct a new Paging Page
     *
     * @api
     * @param Control $control    (optional) Page Control
     * @param int     $pageNumber (optional) Number of the Page
     */
    public function __construct(Control $control = null, $pageNumber = null)
    {
        if ($control) {
            $this->setControl($control);
        }
        if ($pageNumber) {
            $this->setPageNumber($pageNumber);
        }
    }

    /**
     * Get the Page Control
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the Page Control
     *
     * @api
     * @param Control $control Page Control
     * @return static
     */
    public function setControl(Control $control)
    {
        $control->checkId();
        $this->control = $control;
        return $this;
    }

    /**
     * Get the Page number
     *
     * @api
     * @return int
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Set the Page number
     *
     * @api
     * @param int $pageNumber Number of the Page
     * @return static
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = (int)$pageNumber;
        return $this;
    }

}
