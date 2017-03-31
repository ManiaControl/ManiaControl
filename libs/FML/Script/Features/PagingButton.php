<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Types\Scriptable;

/**
 * Paging Button for browsing through Pages
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PagingButton
{

    /**
     * @var Control $control Paging Control
     */
    protected $control = null;

    /**
     * @var int Paging count
     */
    protected $pagingCount = 1;

    /**
     * Construct a new Paging Button
     *
     * @api
     * @param Control $control     (optional) Paging Control
     * @param int     $pagingCount (optional) Number of browsed pages per click
     */
    public function __construct(Control $control = null, $pagingCount = 1)
    {
        if ($control) {
            $this->setControl($control);
        }
        if ($pagingCount) {
            $this->setPagingCount($pagingCount);
        }
    }

    /**
     * Get the paging Control
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the paging Control
     *
     * @api
     * @param Control $control Paging Control
     * @return static
     */
    public function setControl(Control $control)
    {
        $control->checkId();
        if ($control instanceof Scriptable) {
            $control->setScriptEvents(true);
        }
        $this->control = $control;
        return $this;
    }

    /**
     * Get the paging count
     *
     * @api
     * @return int
     */
    public function getPagingCount()
    {
        return $this->pagingCount;
    }

    /**
     * Set the paging count
     *
     * @api
     * @param int $pagingCount Number of browsed pages per click
     * @return static
     */
    public function setPagingCount($pagingCount)
    {
        $this->pagingCount = (int)$pagingCount;
        return $this;
    }

}
