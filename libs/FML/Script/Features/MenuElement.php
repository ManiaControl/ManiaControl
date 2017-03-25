<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Types\Scriptable;

/**
 * Menu Element for the Menu Feature
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MenuElement
{

    /**
     * @var Control $item Menu Item
     */
    protected $item = null;

    /**
     * @var Control $control Menu Control
     */
    protected $control = null;

    /**
     * Create a new Menu Element
     *
     * @api
     * @param Control $item    (optional) Item Control in the Menu bar
     * @param Control $control (optional) Toggled Menu Control
     */
    public function __construct(Control $item = null, Control $control = null)
    {
        if ($item) {
            $this->setItem($item);
        }
        if ($control) {
            $this->setControl($control);
        }
    }

    /**
     * Get the Item Control
     *
     * @api
     * @return Control
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set the Item Control
     *
     * @api
     * @param Control $item Item Control
     * @return static
     */
    public function setItem(Control $item)
    {
        $item->checkId();
        if ($item instanceof Scriptable) {
            $item->setScriptEvents(true);
        }
        $this->item = $item;
        return $this;
    }

    /**
     * Get the Menu Control
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the Menu Control
     *
     * @api
     * @param Control $control Menu Control
     * @return static
     */
    public function setControl(Control $control)
    {
        $control->checkId();
        $this->control = $control;
        return $this;
    }

}
