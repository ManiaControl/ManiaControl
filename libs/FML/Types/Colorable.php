<?php

namespace FML\Types;

/**
 * Interface for Elements with color attribute
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Colorable
{

    /**
     * Get the color
     *
     * @api
     * @return string
     */
    public function getColor();

    /**
     * Set the color
     *
     * @api
     * @param string $color Color
     * @return static
     */
    public function setColor($color);

}
