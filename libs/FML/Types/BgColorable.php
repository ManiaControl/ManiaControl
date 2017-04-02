<?php

namespace FML\Types;

/**
 * Interface for Elements with background color attribute
 *
 * @author     steeffeen
 * @copyright  FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license    http://www.gnu.org/licenses/ GNU General Public License, Version 3
 * @deprecated Use BackgroundColorable
 * @see        BackgroundColorable
 */
interface BgColorable
{

    /**
     * Set the background color
     *
     * @api
     * @param string $bgColor Background color
     * @return static
     * @deprecated Use BackgroundColorable::setBackgroundColor()
     * @see        BackgroundColorable::setBackgroundColor()
     */
    public function setBgColor($bgColor);

}
